<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = \App\Models\ClassSession::whereDate('session_date', '2026-07-27')
    ->orderBy('start_time')
    ->get(['id', 'start_time', 'duration_minutes', 'room', 'status', 'teacher_id', 'student_id']);

$groups = [];
foreach ($rows as $row) {
    $key = $row->start_time->format('H:i');
    $groups[$key] = ($groups[$key] ?? 0) + 1;
}
ksort($groups);
foreach ($groups as $time => $count) {
    echo "$time => $count\n";
}
echo "total: " . $rows->count() . "\n";
echo "rooms: " . $rows->pluck('room')->unique()->implode(',') . "\n";
echo "durations: " . $rows->pluck('duration_minutes')->unique()->implode(',') . "\n";
