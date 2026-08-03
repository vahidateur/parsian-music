<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;

/**
 * Shared test isolation contract.
 *
 * Every test starts from the same known state: a migrated SQLite in-memory
 * database, faked temporary disks, a clean session/cookie jar, unfrozen time
 * and the process environment the suite booted with. Nothing here runs a
 * destructive migration command; database reset uses RefreshDatabase only.
 *
 * Requirements: 1.6, 1.7, 1.8, 3.7
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Disks replaced with a temporary fake so no test writes into real storage.
     *
     * @var array<int, string>
     */
    protected array $isolatedDisks = ['local', 'public'];

    /** @var array<string, mixed> */
    private array $envSnapshot = [];

    /** @var array<string, mixed> */
    private array $serverSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->envSnapshot = $_ENV;
        $this->serverSnapshot = $_SERVER;

        foreach ($this->isolatedDisks as $disk) {
            Storage::fake($disk);
        }

        $this->defaultCookies = [];
        $this->unencryptedCookies = [];
        Cookie::flushQueuedCookies();

        $this->flushSessionState();
        Carbon::setTestNow();
    }

    protected function tearDown(): void
    {
        if ($this->app !== null) {
            Cookie::flushQueuedCookies();
            $this->flushSessionState();
        }

        Carbon::setTestNow();
        $this->restoreSuperGlobals();

        parent::tearDown();
    }

    /**
     * Drop session data without forcing an early session start, so a value
     * written by one test cannot be observed by the next one.
     */
    private function flushSessionState(): void
    {
        if ($this->app === null || ! $this->app->bound('session')) {
            return;
        }

        $session = $this->app['session'];

        if ($session->isStarted()) {
            $session->flush();
        }
    }

    /**
     * Restore $_ENV/$_SERVER (and the real environment) to the pre-test state
     * so an environment variable set by one test cannot leak into another.
     */
    private function restoreSuperGlobals(): void
    {
        foreach (array_keys($_ENV) as $key) {
            if (! array_key_exists($key, $this->envSnapshot)) {
                unset($_ENV[$key]);
                putenv((string) $key);
            }
        }

        foreach ($this->envSnapshot as $key => $value) {
            if (array_key_exists($key, $_ENV) && $_ENV[$key] === $value) {
                continue;
            }

            $_ENV[$key] = $value;

            if (is_scalar($value)) {
                putenv($key.'='.$value);
            }
        }

        foreach (array_keys($_SERVER) as $key) {
            if (! array_key_exists($key, $this->serverSnapshot)) {
                unset($_SERVER[$key]);
            }
        }

        foreach ($this->serverSnapshot as $key => $value) {
            if (! array_key_exists($key, $_SERVER) || $_SERVER[$key] !== $value) {
                $_SERVER[$key] = $value;
            }
        }
    }
}
