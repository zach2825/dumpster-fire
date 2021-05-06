<?php

namespace App\Providers;

use App\Contracts\GitStuff;
use App\Contracts\PullTaskNumber;
use App\Services\AHA;
use App\Services\Azure;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PullTaskNumber::class, fn() => new PullTaskNumber());

        $this->app->singleton(GitStuff::class, fn($app) => new GitStuff($app[PullTaskNumber::class]));

        $this->app->singleton(
            Azure::class,
            fn($app) => new Azure(
                config('df.organization'),
                config('df.username'),
                config('df.personal_access_token'),
                config('df.project'),
            )
        );

        $this->app->singleton(
            AHA::class,
            fn($app) => new AHA(
                config('aha.api_key'),
                config('aha.company'),
                config('aha.task_key'),
            )
        );
    }
}
