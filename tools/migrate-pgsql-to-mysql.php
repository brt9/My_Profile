<?php

declare(strict_types=1);

function read_dotenv(string $path): array
{
    if (! is_file($path)) {
        return [];
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $values[$key] = $value;
    }

    return $values;
}

function config_value(array $dotenv, string $name, ?string $default = null): ?string
{
    $value = getenv($name);

    if ($value !== false) {
        return $value;
    }

    return $dotenv[$name] ?? $default;
}

function pg_ident(string $identifier): string
{
    return '"'.str_replace('"', '""', $identifier).'"';
}

function mysql_ident(string $identifier): string
{
    return '`'.str_replace('`', '``', $identifier).'`';
}

function normalize_value(mixed $value, string $type): mixed
{
    if ($value === null) {
        return null;
    }

    if ($type === 'boolean') {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return in_array(strtolower((string) $value), ['1', 't', 'true', 'yes'], true) ? 1 : 0;
    }

    if (str_starts_with($type, 'timestamp')) {
        return (new DateTimeImmutable((string) $value))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    return $value;
}

$root = dirname(__DIR__);
$dotenv = read_dotenv($root.DIRECTORY_SEPARATOR.'.env');

$source = [
    'host' => config_value($dotenv, 'SOURCE_DB_HOST', '127.0.0.1'),
    'port' => config_value($dotenv, 'SOURCE_DB_PORT', '5433'),
    'database' => config_value($dotenv, 'SOURCE_DB_DATABASE', 'myprofile'),
    'username' => config_value($dotenv, 'SOURCE_DB_USERNAME', 'myprofile'),
    'password' => config_value($dotenv, 'SOURCE_DB_PASSWORD', config_value($dotenv, 'DB_PASSWORD', '')),
];

$target = [
    'host' => config_value($dotenv, 'TARGET_DB_HOST', '127.0.0.1'),
    'port' => config_value($dotenv, 'TARGET_DB_PORT', '3308'),
    'database' => config_value($dotenv, 'TARGET_DB_DATABASE', 'myprofile'),
    'username' => config_value($dotenv, 'TARGET_DB_USERNAME', 'myprofile'),
    'password' => config_value($dotenv, 'TARGET_DB_PASSWORD', config_value($dotenv, 'DB_PASSWORD', '')),
];

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pgsql = new PDO(
    sprintf('pgsql:host=%s;port=%s;dbname=%s', $source['host'], $source['port'], $source['database']),
    $source['username'],
    $source['password'],
    $options,
);

$mysql = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $target['host'], $target['port'], $target['database']),
    $target['username'],
    $target['password'],
    $options + [PDO::ATTR_EMULATE_PREPARES => false],
);

$sourceTables = $pgsql->query(
    "select table_name from information_schema.tables where table_schema = current_schema() and table_type = 'BASE TABLE' order by table_name"
)->fetchAll(PDO::FETCH_COLUMN);

$targetTables = $mysql->query('show full tables where Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
$tables = array_values(array_intersect($sourceTables, $targetTables));

$mysql->exec('set foreign_key_checks = 0');

try {
    foreach ($tables as $table) {
        $mysql->exec('delete from '.mysql_ident($table));
    }

    foreach ($tables as $table) {
        $typeRows = $pgsql->prepare(
            'select column_name, data_type from information_schema.columns where table_schema = current_schema() and table_name = :table order by ordinal_position'
        );
        $typeRows->execute(['table' => $table]);
        $sourceTypes = [];
        foreach ($typeRows->fetchAll() as $row) {
            $sourceTypes[$row['column_name']] = $row['data_type'];
        }

        $targetColumns = array_column($mysql->query('show columns from '.mysql_ident($table))->fetchAll(), 'Field');
        $columns = array_values(array_intersect(array_keys($sourceTypes), $targetColumns));

        if ($columns === []) {
            continue;
        }

        $selectColumns = implode(', ', array_map(pg_ident(...), $columns));
        $insertColumns = implode(', ', array_map(mysql_ident(...), $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $reader = $pgsql->query('select '.$selectColumns.' from '.pg_ident($table));
        $writer = $mysql->prepare(
            'insert into '.mysql_ident($table).' ('.$insertColumns.') values ('.$placeholders.')'
        );

        $count = 0;
        $mysql->beginTransaction();
        while ($row = $reader->fetch()) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = normalize_value($row[$column], $sourceTypes[$column]);
            }

            $writer->execute($values);
            $count++;
        }
        $mysql->commit();

        $autoColumn = null;
        foreach ($mysql->query('show columns from '.mysql_ident($table))->fetchAll() as $column) {
            if (str_contains((string) $column['Extra'], 'auto_increment')) {
                $autoColumn = $column['Field'];
                break;
            }
        }

        if ($autoColumn !== null) {
            $next = (int) $mysql->query(
                'select coalesce(max('.mysql_ident($autoColumn).'), 0) + 1 from '.mysql_ident($table)
            )->fetchColumn();
            $mysql->exec('alter table '.mysql_ident($table).' auto_increment = '.$next);
        }

        echo $table.': '.$count.PHP_EOL;
    }
} finally {
    $mysql->exec('set foreign_key_checks = 1');
}

echo 'Migration finished.'.PHP_EOL;
