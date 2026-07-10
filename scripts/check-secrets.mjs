import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const files = execFileSync('git', ['ls-files', '--cached', '--others', '--exclude-standard'], {
    encoding: 'utf8',
}).split(/\r?\n/).filter(Boolean);

const ignored = [
    /^package-lock\.json$/,
    /^composer\.lock$/,
    /^storage\//,
    /(^|\/)vendor\//,
    /(^|\/)node_modules\//,
    /(^|\/)(bin|obj|dist|public\/build)\//,
];

const patterns = [
    { name: 'Steam API key', regex: /STEAM_API_KEY\s*=\s*[A-Fa-f0-9]{32}/g },
    { name: 'GitHub token', regex: /(?:ghp|github_pat)_[A-Za-z0-9_]{20,}/g },
    { name: 'AWS access key', regex: /AKIA[0-9A-Z]{16}/g },
    { name: 'Google API key', regex: /AIza[0-9A-Za-z_-]{35}/g },
    { name: 'Google OAuth client secret', regex: /GOCSPX-[0-9A-Za-z_-]{20,}/g },
    { name: 'Google OAuth access token', regex: /ya29\.[0-9A-Za-z_-]{20,}/g },
    { name: 'Bearer token', regex: /Bearer\s+[A-Za-z0-9._-]{32,}/g },
    { name: 'Private key', regex: /-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/g },
];

const findings = [];

for (const file of files) {
    if (ignored.some((pattern) => pattern.test(file))) continue;

    let contents;
    try {
        contents = readFileSync(file, 'utf8');
    } catch {
        continue;
    }

    for (const pattern of patterns) {
        pattern.regex.lastIndex = 0;
        if (pattern.regex.test(contents)) findings.push(`${file}: ${pattern.name}`);
    }
}

if (findings.length > 0) {
    console.error('Possíveis segredos encontrados:');
    findings.forEach((finding) => console.error(`- ${finding}`));
    process.exitCode = 1;
} else {
    console.log(`PASS secret scan (${files.length} arquivos verificados)`);
}
