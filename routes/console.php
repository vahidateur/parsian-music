<?php

use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('e2e:seed-admin', function (): void {
    app(TestDataSeeder::class)->seedE2EAdmin();
    $this->info('E2E admin fixture is ready.');
})->purpose('Create the local/testing browser-suite admin fixture without demo data');
