<?php

namespace App\Providers;

use App\Models\Disposal;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
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
        date_default_timezone_set(config('app.timezone'));
        DB::statement("SET TIME ZONE 'Asia/Jakarta'");
        RateLimiter::for('ldap-login', function (Request $request) {
            $username = strtolower((string) $request->input('username'));
            $ip = $request->ip();

            return [
                // 5 attempts/minute per IP+username
                Limit::perMinute(5)->by("ldap:$username|$ip"),

                // 20 attempts/minute per IP (catch username rotation)
                Limit::perMinute(20)->by("ldap-ip:$ip"),

                // Optional global bucket to be extra safe
                Limit::perMinute(200)->by('ldap-global'),
            ];
        });
        RateLimiter::for('api', function (Request $request) {
            return [
                // per-minute limit; key by user id if present, otherwise IP
                Limit::perMinute(60)->by($request->user()?->id ?? $request->ip()),
            ];
        });


        Relation::enforceMorphMap([
            'user'     => User::class,
            'transfer' => Transfer::class,
            'disposal' => Disposal::class,
        ]);

        Blade::if('canAction', function (string $menuKode, string $action) {
            $user = auth()->user();
            return $user && $user->hasAction($menuKode, $action);
        });
    }
}
