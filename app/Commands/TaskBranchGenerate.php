<?php

namespace App\Commands;

use App\Contracts\GitStuff;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class TaskBranchGenerate extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'task:branch';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(GitStuff $gitStuff)
    {
        // TODO :: use a global service piece of code to talk to jira
        // TODO :: collect this task summary
        // TODO :: generate the branch in a standard format

        $branch = $gitStuff->branchNameTemplate(
            $this->argument('task_name'),
            $this->argument('task_key'),
            $this->option('type'),
            ''
        );
    }

    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
