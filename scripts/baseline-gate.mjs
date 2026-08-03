#!/usr/bin/env node
/**
 * Baseline_Gate runner and reporter.
 *
 * Runs every Verification_Command_Set command in its own child process with a
 * bounded 300 second timeout and reports, per command: the command line, exit
 * status, passed/failed/skipped counts, the first actionable failure and the
 * failing test identifier. A timeout is reported as a failure together with the
 * last available output. Success is only reported when every command exits zero.
 *
 * Usage: `npm run baseline:gate` or `composer baseline:gate`.
 *
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.9, 1.10, 1.11, 16.7, 16.8
 */
import { spawn } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const PROJECT_ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const TIMEOUT_MS = 300_000;
const TAIL_LINES = 25;

/** Verification_Command_Set, executed in order, each in a separate process. */
const STEPS = [
    { id: 'optimize-clear', command: 'php artisan optimize:clear', parser: 'plain' },
    { id: 'php-tests', command: 'php artisan test', parser: 'phpunit' },
    { id: 'js-tests', command: 'npm run test:js', parser: 'node-test' },
    { id: 'build', command: 'npm run build', parser: 'plain' },
];

const ANSI_PATTERN = /\u001B\[[0-9;]*[A-Za-z]/g;

const stripAnsi = (value) => value.replace(ANSI_PATTERN, '');

const toLines = (output) =>
    stripAnsi(output)
        .split(/\r?\n/)
        .map((line) => line.replace(/\s+$/, ''));

const tail = (output) =>
    toLines(output)
        .filter((line) => line.trim() !== '')
        .slice(-TAIL_LINES);

/** Kills the child and, on Windows, its process tree so no runner is orphaned. */
const killProcessTree = (child) => {
    if (process.platform === 'win32' && typeof child.pid === 'number') {
        spawn('taskkill', ['/pid', String(child.pid), '/T', '/F'], { stdio: 'ignore' });

        return;
    }

    child.kill('SIGKILL');
};

/**
 * Runs one command in a dedicated process. Resolves with the exit status, the
 * captured output and whether the bounded timeout was reached.
 */
const runCommand = (command) =>
    new Promise((resolvePromise, rejectPromise) => {
        const startedAt = Date.now();
        const child = spawn(command, {
            cwd: PROJECT_ROOT,
            shell: true,
            env: {
                ...process.env,
                NO_COLOR: '1',
                FORCE_COLOR: '0',
                TERM: 'dumb',
                COLUMNS: '200',
            },
        });

        let output = '';
        let timedOut = false;

        const timer = setTimeout(() => {
            timedOut = true;
            killProcessTree(child);
        }, TIMEOUT_MS);

        child.stdout.on('data', (chunk) => {
            output += chunk.toString();
        });

        child.stderr.on('data', (chunk) => {
            output += chunk.toString();
        });

        child.on('error', (error) => {
            clearTimeout(timer);
            rejectPromise(error);
        });

        child.on('close', (code, signal) => {
            clearTimeout(timer);

            resolvePromise({
                exitStatus: timedOut ? null : code,
                signal,
                timedOut,
                output,
                durationMs: Date.now() - startedAt,
            });
        });
    });

/** Parses the `Tests: 1 failed, 187 passed (...)` summary produced by artisan test. */
const parsePhpunit = (output) => {
    const lines = toLines(output);
    const counts = { passed: null, failed: null, skipped: null };
    const summaryLine = [...lines].reverse().find((line) => /^\s*Tests:\s/.test(line));

    if (summaryLine) {
        const summary = summaryLine.replace(/^\s*Tests:\s*/, '');

        for (const [, amount, label] of summary.matchAll(/(\d+)\s+([a-z]+)/g)) {
            const value = Number(amount);

            if (label === 'passed') {
                counts.passed = value;
            } else if (label === 'failed' || label === 'errored' || label === 'errors') {
                counts.failed = (counts.failed ?? 0) + value;
            } else if (label === 'skipped' || label === 'incomplete' || label === 'todo') {
                counts.skipped = (counts.skipped ?? 0) + value;
            }
        }
    }

    let identifier = null;
    let detail = null;

    const suiteIndex = lines.findIndex((line) => /^\s*(FAILED|FAIL)\s+\S/.test(line));

    if (suiteIndex !== -1) {
        const suite = lines[suiteIndex]
            .replace(/^\s*(FAILED|FAIL)\s+/, '')
            .split(/\s{2,}/)[0]
            .trim();
        const markerLine = lines.slice(suiteIndex + 1).find((line) => /^\s*(⨯|x)\s+\S/.test(line));
        const testName = markerLine
            ? markerLine
                  .replace(/^\s*(⨯|x)\s+/, '')
                  .split(/\s{2,}/)[0]
                  .replace(/\s+[\d.]+s$/, '')
                  .trim()
            : null;

        identifier = testName ? `${suite} > ${testName}` : suite;
    }

    // The detailed failure block starts with `FAILED  <test>   <ExceptionClass>`
    // and the actionable message is the first content line after it.
    const detailIndex = lines.findIndex((line) => /^\s*FAILED\s+\S/.test(line));
    const isNoise = (line) =>
        line === '' || /^─+$/.test(line) || /^at\s/.test(line) || /^\d+\s{2,}\S/.test(line);

    if (detailIndex !== -1) {
        detail =
            lines
                .slice(detailIndex + 1)
                .map((line) => line.trim())
                .find((line) => !isNoise(line)) ?? null;
    }

    if (detail === null) {
        detail =
            lines
                .map((line) => line.trim())
                .find((line) => /(SQLSTATE|Failed asserting|Expected .* but)/.test(line)) ?? null;
    }

    if (detail !== null && detail.length > 300) {
        detail = `${detail.slice(0, 300)}…`;
    }

    return { counts, identifier, detail };
};

/** Parses the `node --test` spec reporter summary and failing test markers. */
const parseNodeTest = (output) => {
    const lines = toLines(output);
    const counts = { passed: null, failed: null, skipped: null };
    const readCount = (label) => {
        const line = [...lines].reverse().find((candidate) =>
            new RegExp(`^\\s*(?:ℹ\\s*)?${label}\\s+\\d+$`).test(candidate)
        );

        return line ? Number(line.trim().split(/\s+/).pop()) : null;
    };

    counts.passed = readCount('pass');
    counts.failed = readCount('fail');
    counts.skipped = readCount('skipped');

    const failureLine = lines.find((line) => /^\s*(✖|not ok)\s+\S/.test(line));
    const identifier = failureLine
        ? failureLine
              .replace(/^\s*(✖|not ok)\s*/, '')
              .replace(/^\d+\s*-\s*/, '')
              .replace(/\s+\([\d.]+ms\)$/, '')
              .trim()
        : null;

    let detail = null;

    if (failureLine) {
        const start = lines.indexOf(failureLine);
        detail =
            lines
                .slice(start + 1, start + 15)
                .map((line) => line.trim())
                .find((line) => /^(Error|AssertionError|TypeError|[a-z]+Error)\b|^error:/i.test(line)) ?? null;
    }

    return { counts, identifier, detail };
};

/** Fallback parser for commands without a test summary (optimize:clear, build). */
const parsePlain = (output) => {
    const detail =
        toLines(output)
            .map((line) => line.trim())
            .find((line) => /^(error|Error:|\[vite\].*error|Build failed)/i.test(line)) ?? null;

    return { counts: { passed: null, failed: null, skipped: null }, identifier: null, detail };
};

const parseOutput = (parser, output) => {
    if (parser === 'phpunit') {
        return parsePhpunit(output);
    }

    if (parser === 'node-test') {
        return parseNodeTest(output);
    }

    return parsePlain(output);
};

const formatCounts = ({ passed, failed, skipped }) => {
    const parts = [];

    if (passed !== null) {
        parts.push(`passed=${passed}`);
    }

    parts.push(`failed=${failed ?? 0}`);
    parts.push(`skipped=${skipped ?? 0}`);

    return parts.join(' ');
};

const formatDuration = (durationMs) => `${(durationMs / 1000).toFixed(1)}s`;

const main = async () => {
    const results = [];

    process.stdout.write(`Baseline_Gate (timeout ${TIMEOUT_MS / 1000}s per command)\n`);

    for (const [index, step] of STEPS.entries()) {
        const position = `[${index + 1}/${STEPS.length}]`;

        process.stdout.write(`\n${position} ${step.command}\n`);

        let run;

        try {
            run = await runCommand(step.command);
        } catch (error) {
            results.push({
                ...step,
                status: 'failed',
                exitStatus: null,
                durationMs: 0,
                counts: { passed: null, failed: null, skipped: null },
                identifier: null,
                detail: `command could not be started: ${error.message}`,
                tailLines: [],
            });

            process.stdout.write(`        status: failed (not started) - ${error.message}\n`);
            continue;
        }

        const parsed = parseOutput(step.parser, run.output);
        const failedCount = parsed.counts.failed ?? 0;
        const status = run.timedOut || run.exitStatus !== 0 || failedCount > 0 ? 'failed' : 'passed';

        results.push({
            ...step,
            status,
            exitStatus: run.exitStatus,
            timedOut: run.timedOut,
            durationMs: run.durationMs,
            counts: parsed.counts,
            identifier: parsed.identifier,
            detail: parsed.detail,
            tailLines: tail(run.output),
        });

        process.stdout.write(
            `        status: ${status}  exit: ${run.timedOut ? 'timeout' : run.exitStatus}  duration: ${formatDuration(run.durationMs)}\n`
        );
        process.stdout.write(`        counts: ${formatCounts(parsed.counts)}\n`);

        if (status === 'failed') {
            if (run.timedOut) {
                process.stdout.write(`        timeout: reached ${TIMEOUT_MS / 1000}s, process terminated\n`);
            }

            if (parsed.identifier) {
                process.stdout.write(`        first failing test: ${parsed.identifier}\n`);
            }

            if (parsed.detail) {
                process.stdout.write(`        first failure: ${parsed.detail}\n`);
            }
        }
    }

    const failures = results.filter((result) => result.status === 'failed');

    process.stdout.write('\nBaseline_Gate summary\n');

    for (const result of results) {
        process.stdout.write(
            `  ${result.status === 'passed' ? 'PASS' : 'FAIL'}  ${result.command}  (exit ${result.timedOut ? 'timeout' : result.exitStatus}, ${formatCounts(result.counts)})\n`
        );
    }

    if (failures.length === 0) {
        process.stdout.write('\nBaseline_Gate: PASSED\n');
        process.exitCode = 0;

        return;
    }

    const first = failures[0];

    process.stdout.write(`\nBaseline_Gate: FAILED (${failures.length} of ${results.length} commands failed)\n`);
    process.stdout.write(`First actionable failure: ${first.command}\n`);

    if (first.identifier) {
        process.stdout.write(`Test identifier: ${first.identifier}\n`);
    }

    if (first.detail) {
        process.stdout.write(`Failure detail: ${first.detail}\n`);
    }

    if (first.tailLines.length > 0) {
        process.stdout.write(`Last output of ${first.command}:\n`);

        for (const line of first.tailLines) {
            process.stdout.write(`  ${line}\n`);
        }
    }

    process.exitCode = 1;
};

await main();
