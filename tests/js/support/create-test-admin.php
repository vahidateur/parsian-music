<?php
/**
 * Compatibility entry point for the JS property-test environment.
 * Run: php tests/js/support/create-test-admin.php
 *
 * The canonical fixture is owned by TestDataSeeder; this wrapper intentionally
 * does not contain credentials or a second authentication mechanism.
 */

require __DIR__ . '/../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

app(\Database\Seeders\TestDataSeeder::class)->seedE2EAdmin();
