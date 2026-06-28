<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // Route parameter {admin_user} resolves to a User restricted to admin accounts.
        // Non-admin IDs yield 404 at binding time, removing the need for abort_unless() in controllers.
        Route::bind('admin_user', fn (string $value): User => User::query()->admins()->findOrFail($value));

        // Strong password policy applied wherever Password::defaults() is referenced
        // (login is excluded — login validates against a stored hash, not a new password).
        Password::defaults(fn () => Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('admin-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                $request->ip().'|'.(string) $request->input('email')
            );
        });

        RateLimiter::for('admin-forgot', function (Request $request): Limit {
            return Limit::perMinute(3)->by(
                $request->ip().'|'.(string) $request->input('email')
            );
        });

        RateLimiter::for('admin-reset', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                $request->ip().'|'.(string) $request->input('email')
            );
        });
    }
}
