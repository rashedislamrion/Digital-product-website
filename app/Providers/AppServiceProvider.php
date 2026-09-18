<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Implicitly grant 'Super Admin' role all permissions
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('Super Admin') || $user->hasRole('super_admin') ? true : null;
        });

        // Ensure s3_secure disk generates a valid pre-signed URL in local/test environments
        if (config('filesystems.disks.s3_secure.driver') === 'local') {
            \Illuminate\Support\Facades\Storage::disk('s3_secure')->buildTemporaryUrlsUsing(
                function ($path, $expiration, $options = []) {
                    return \Illuminate\Support\Facades\URL::temporarySignedRoute(
                        'files.secure_serve',
                        $expiration,
                        ['path' => $path]
                    );
                }
            );
        }

        // Configure Rate Limiters (@research.md §5.2 & §8.2)
        \Illuminate\Support\Facades\RateLimiter::for('licenses', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip() ?: 'anonymous');
        });

        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip() ?: 'anonymous');
        });

        \Illuminate\Support\Facades\RateLimiter::for('checkout', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip() ?: 'anonymous');
        });
    }
}
