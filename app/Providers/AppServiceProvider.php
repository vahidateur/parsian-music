<?php

namespace App\Providers;

use App\Enums\NotificationChannelEnum;
use App\Enums\RoleEnum;
use App\Models\ClassSession;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\Drivers\DatabaseDriver;
use App\Notifications\Drivers\NullDriver;
use App\Policies\EnrollmentPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\SessionPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\UserPolicy;
use App\Services\InstituteSettings;
use App\Services\SettingsManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Policy map — model => policy class. */
    protected array $policies = [
        Student::class          => StudentPolicy::class,
        Teacher::class          => TeacherPolicy::class,
        StudentEnrollment::class => EnrollmentPolicy::class,
        ClassSession::class     => SessionPolicy::class,
        Invoice::class          => InvoicePolicy::class,
        Lead::class             => LeadPolicy::class,
        User::class             => UserPolicy::class,
    ];

    public function register(): void
    {
        // ── Settings singletons ───────────────────────────────────────────
        $this->app->singleton(InstituteSettings::class);
        $this->app->singleton(SettingsManager::class);

        // ── Notification channel drivers ──────────────────────────────────
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::InApp)}",    DatabaseDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::Telegram)}", NullDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::Email)}",    NullDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::SMS)}",      NullDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::Push)}",     NullDriver::class);
    }

    public function boot(): void
    {
        View::share('locale', session('locale', config('app.locale', 'en')));

        // ── Policies ──────────────────────────────────────────────────────
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Super Admin bypasses every Gate check automatically
        Gate::before(function (User $user) {
            if ($user->role === RoleEnum::SUPER_ADMIN) {
                return true;
            }
        });
    }

    private function ch(NotificationChannelEnum $channel): string
    {
        return $channel->value;
    }
}
