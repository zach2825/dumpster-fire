<?php

namespace App\Providers;

use App\Contracts\GitStuff;
use App\Contracts\PullTaskNumber;
use App\Contracts\TaskService;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
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
        $this->app->singleton(GitStuff::class, fn($app) => new GitStuff($app[PullTaskNumber::class]));

        $this->app->singleton(
            TaskService::class,
            concrete: function () {
                /**
                 * react the repo settings and decide which task service to use
                 */
                $task_service_name = GitStuff::getOrgService();

                try {
                    return $this->app->make("App\\Services\\{$task_service_name}");
                } catch (BindingResolutionException $exception) {
                    throw new Exception("Unexpected value: {$task_service_name}");
                }
            }
        );
    }
}
