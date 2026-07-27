<?php
/**
 * Creates a test admin user for JS property tests.
 * Run: php tests/js/support/create-test-admin.php
 */

require __DIR__ . '/../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::updateOrCreate(
    ['phone' => '09999999999'],
    [
        'full_name' => 'Test Admin',
        'password' => Hash::make('testpass123'),
        'role' => 'admin',
        'is_active' => true,
        'force_password_change' => false,
    ]
);

echo "Test admin created: phone={$user->phone}\n";
