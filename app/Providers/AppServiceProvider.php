<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\Health\Checks\Checks;
use Spatie\Health\Facades\Health;
use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // @codeCoverageIgnoreStart
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Health::checks([
            Checks\CacheCheck::new(),
            CpuLoadCheck::new()
                ->failWhenLoadIsHigherInTheLastMinute(10.0)
                ->failWhenLoadIsHigherInTheLast5Minutes(5.0)
                ->failWhenLoadIsHigherInTheLast15Minutes(3.0),
            Checks\DatabaseCheck::new(),
            Checks\DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),
            Checks\DatabaseSizeCheck::new()
                ->failWhenSizeAboveGb(5.0),
            Checks\DatabaseTableSizeCheck::new()
                ->table('observations', 1024),
            Checks\DebugModeCheck::new(),
            Checks\EnvironmentCheck::new(),
            Checks\OptimizedAppCheck::new(),
            // Checks\ScheduleCheck::new(),
            SecurityAdvisoriesCheck::new(),
            Checks\UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(60)
                ->failWhenUsedSpaceIsAbovePercentage(80),
        ]);

        RateLimiter::for('send', function (Request $request) {
            // Each protocol identifies the site differently: WOW uses `siteid`,
            // Ecowitt uses `PASSKEY` and Weather Underground uses `ID`.
            $siteId = $request->input('siteid')
                ?? $request->input('PASSKEY')
                ?? $request->input('ID')
                ?? $request->ip();

            return Limit::perMinute(20)->by('site:'.$siteId);
        });

        RateLimiter::for('submission', function (Request $request) {
            return Limit::perMinute(5);
        });

        Scramble::registerApi('v1', [
            'api_path' => 'api/v1',
            'info' => ['version' => '1.0'],
        ])
            ->expose(
                ui: '/docs/api/v1',
                document: '/docs/api/v1/openapi.json'
            );
    }
}
