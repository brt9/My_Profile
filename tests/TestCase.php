<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $testingEnvironment = [
            'APP_ENV' => 'testing',
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
        ];

        foreach ($testingEnvironment as $name => $value) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }

        return parent::createApplication();
    }
}
