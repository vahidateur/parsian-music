<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verifies the shared isolation contract of Tests\TestCase: database rows,
 * temporary files, environment variables, session values, queued cookies and
 * frozen time from one test are never visible to another test.
 *
 * Requirements: 1.6, 1.7, 1.8, 3.7
 */
class TestStateIsolationTest extends TestCase
{
    private const LEAKY_ENV_KEY = 'PM_ISOLATION_PROBE';

    private const LEAKY_FILE = 'isolation-probe.txt';

    public function test_a_test_can_dirty_every_isolated_state_holder(): void
    {
        User::factory()->create();

        Storage::disk('public')->put(self::LEAKY_FILE, 'probe');

        $_ENV[self::LEAKY_ENV_KEY] = 'leaked';
        $_SERVER[self::LEAKY_ENV_KEY] = 'leaked';
        putenv(self::LEAKY_ENV_KEY.'=leaked');

        $this->withSession(['isolation_probe' => 'leaked']);
        $this->withCookie('isolation_probe', 'leaked');

        Carbon::setTestNow('2001-02-03 04:05:06');

        $this->assertSame(1, User::query()->count());
        $this->assertTrue(Storage::disk('public')->exists(self::LEAKY_FILE));
        $this->assertSame('leaked', getenv(self::LEAKY_ENV_KEY));
    }

    public function test_the_next_test_starts_from_a_clean_state(): void
    {
        $this->assertSame(0, User::query()->count(), 'Database rows leaked between tests.');
        $this->assertFalse(
            Storage::disk('public')->exists(self::LEAKY_FILE),
            'Temporary filesystem state leaked between tests.'
        );

        $this->assertArrayNotHasKey(self::LEAKY_ENV_KEY, $_ENV, 'Environment variable leaked between tests.');
        $this->assertArrayNotHasKey(self::LEAKY_ENV_KEY, $_SERVER, 'Server variable leaked between tests.');
        $this->assertFalse(getenv(self::LEAKY_ENV_KEY), 'Process environment variable leaked between tests.');

        $this->assertNull(session('isolation_probe'), 'Session value leaked between tests.');
        $this->assertSame([], $this->defaultCookies, 'Cookies leaked between tests.');
        $this->assertFalse(Carbon::hasTestNow(), 'Frozen time leaked between tests.');
    }
}
