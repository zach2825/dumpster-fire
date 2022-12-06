<?php

namespace App\Providers;

use App\Contracts\GitStuff;
use App\Contracts\PullTaskNumber;
use App\Contracts\TaskService;
use App\enums\ImportTypesEnums;
use App\Services\AHA;
use App\Services\Azure;
use App\Services\JIRA;
use Exception;
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
                $task_service_id = GitStuff::getOrgService();

                return match ($task_service_id) {
                    ImportTypesEnums::AHA->name => $this->app->make(AHA::class),
                    ImportTypesEnums::AZURE->name => $this->app->make(AZURE::class),
                    ImportTypesEnums::JIRA->name => $this->app->make(JIRA::class),
                    default => throw new Exception('Unexpected value'),
                };
            }
        );
    }
}
