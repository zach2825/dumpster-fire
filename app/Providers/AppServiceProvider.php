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
        $this->app->singleton(PullTaskNumber::class, fn() => new PullTaskNumber());

        $this->app->singleton(GitStuff::class, fn($app) => new GitStuff($app[PullTaskNumber::class]));

        $this->app->singleton(
            Azure::class,
            fn($app) => new Azure(
                organization: config('df.organization'),
                token: config('df.personal_access_token'),
                username: config('df.username'),
                project: config('df.project'),
            )
        );

        $this->app->singleton(
            AHA::class,
            concrete: fn() => new AHA(
                token: config('aha.api_key'),
                company: config('aha.company'),
                task_key: config('aha.task_key'),
            )
        );

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
