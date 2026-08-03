import { defineConfig } from 'playwright/test';
import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = dirname(fileURLToPath(import.meta.url));
const rootEnvPath = resolve(projectRoot, '.env');
const inheritedEnvironment = Object.fromEntries(Object.entries(process.env));

if (typeof process.loadEnvFile !== 'function') {
    throw new Error('This Playwright configuration requires Node native process.loadEnvFile support.');
}

if (existsSync(rootEnvPath)) {
    process.loadEnvFile(rootEnvPath);
}

for (const [name, value] of Object.entries(inheritedEnvironment)) {
    process.env[name] = value;
}

export default defineConfig({
    testDir: './tests/browser',
    fullyParallel: false,
    workers: 1,
    timeout: 120_000,
    expect: {
        timeout: 10_000,
    },
    use: {
        ...(process.env.TEST_BASE_URL ? { baseURL: process.env.TEST_BASE_URL } : {}),
    },
});
