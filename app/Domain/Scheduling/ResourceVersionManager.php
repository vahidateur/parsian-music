<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use Illuminate\Support\Facades\DB;

/** Advances cache-relevant resource versions only within an accepted transaction. */
final class ResourceVersionManager
{
    /** @param list<string> $resourceKeys @return array<string, int> */
    public function advance(array $resourceKeys): array
    {
        $versions = [];
        sort($resourceKeys, SORT_STRING);
        $now = now();

        foreach ($resourceKeys as $key) {
            [$type, $id] = explode(':', $key, 2);
            $query = DB::table('scheduling_resource_versions')->where('resource_type', $type)->where('resource_id', $id);
            $record = $query->lockForUpdate()->first();
            if ($record === null) {
                DB::table('scheduling_resource_versions')->insert([
                    'resource_type' => $type, 'resource_id' => $id, 'version' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $record = $query->lockForUpdate()->firstOrFail();
            }

            $next = ((int) $record->version) + 1;
            $query->update(['version' => $next, 'updated_at' => $now]);
            $versions[$key] = $next;
        }

        return $versions;
    }
}
