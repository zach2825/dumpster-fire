<?php

namespace App\Commands;

use App\Contracts\GitStuff;
use App\Models\AHATask;
use App\Services\AHA;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class AHATaskGet extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'aha:task {task_id : task_id to fetch from aha boards without the key only the number like 1234}
                                        {--f|from= : Which branch to generate from}
                                        {--g|guess : branch from develop or master depending if the task is hotfix or not}
                                        {--d|dump : only output the task details}
                                        {--b|dumpBranchName : only output the task branch name}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Read task details from AHA and generate the branch name from those details.';

    /**
     * Execute the console command.
     *
     * @param AHA      $aha
     * @param GitStuff $gitStuff
     * @return bool
     */
    public function handle(AHA $aha, GitStuff $gitStuff): bool
    {
        /** @var AHATask $task */
        $task    = null;
        $task_id = $this->argument('task_id');
        $guess   = $this->option('guess');

        $this->task(
            'Getting task from aha',
            function () use (&$task, $aha, $task_id) {
                $task = $aha->taskGet($task_id);
            }
        );

        if ($this->option('dump')) {
            $this->info(print_r($task->toArray(), true));
            return 0;
        }

        $from       = $this->option('from');
        $branchName = '';
        $this->task(
            'Creating the branch name',
            function () use ($gitStuff, $task, $aha, &$branchName, &$from, $guess) {
                $branchName = $gitStuff->branchNameTemplate(
                    $task->title,
                    $task->id,
                    $branchType = $aha->mapStatusToBranchType($task->workType),
                    config('aha.task_key')
                );

                if ($guess) {
                    if ($branchType == 'hotfix') {
                        $from = 'main';
                    } else {
                        $from = 'develop';
                    }
                }
            }
        );

        if ($this->option('dumpBranchName')) {
            $this->info('');
            $this->info('The branch name would be "' . $branchName . '"');
            $this->info("git checkout '$branchName' 2> /dev/null || git checkout -b '$branchName");
            $this->info('');
            return 0;
        }

        if ($from) {
            $this->info(sprintf('Generating the new branch "%s" from "%s"', $branchName, $from));
            $gitStuff->checkout($from);
        }

        $gitStuff->checkout($branchName);

        return false;
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
