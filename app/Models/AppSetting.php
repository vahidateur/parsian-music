<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * DB-backed settings store.
 *
 * One row per settings group.  The `payload` column holds the full key-value
 * map as JSON so new fields can be added without schema migrations.
 *
 * Request-level cache: the static $cache map is populated on the first DB read
 * per group and reused for the lifetime of the PHP process (one HTTP request).
 * In a queue worker, call AppSetting::flushCache() between jobs if needed.
 *
 * @property string     $group
 * @property array|null $payload
 */
class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = ['group', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    /** Request-level in-memory cache — avoids repeated DB reads per group. */
    private static array $cache = [];

    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Return all key-value pairs for a settings group.
     * Returns an empty array if the group has never been saved.
     */
    public static function getGroup(string $group): array
    {
        if (! array_key_exists($group, static::$cache)) {
            static::$cache[$group] = static::where('group', $group)->first()?->payload ?? [];
        }

        return static::$cache[$group];
    }

    /**
     * Return a single setting value within a group.
     */
    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        return static::getGroup($group)[$key] ?? $default;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Persist the full payload for a settings group (upsert).
     * Merges with any existing data so partial updates don't wipe other keys.
     */
    public static function setGroup(string $group, array $data): void
    {
        $existing = static::getGroup($group);
        $merged   = array_merge($existing, $data);

        static::updateOrCreate(
            ['group'   => $group],
            ['payload' => $merged]
        );

        // Bust the cache for this group so the next read reflects the new values.
        static::$cache[$group] = $merged;
    }

    // ── Cache management ─────────────────────────────────────────────────────

    /**
     * Flush the in-memory cache (useful in tests or long-running workers).
     */
    public static function flushCache(?string $group = null): void
    {
        if ($group !== null) {
            unset(static::$cache[$group]);
        } else {
            static::$cache = [];
        }
    }
}
