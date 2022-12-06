<?php

namespace App\Commands;

use App\Contracts\TaskService;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class TaskBranchGenerate extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'task:branch {task_number : Just the number from the task, I don\'t need the key description}
                        {--t|test : Just output the would be new branch name}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @param TaskService $taskService
     * @return int
     */
    public function handle(TaskService $taskService)
    {
        $task_number = $this->argument('task_number');

        $jiraTask = null;
        $branch_name = null;

        $this->task(
            title: "Collecting the task details",
            task: function () use (&$jiraTask, $taskService, $task_number) {
                $jiraTask = $taskService->taskGet($task_number);
            }
        );

        $this->task(
            title: "Generating the branch name",
            task: function () use ($jiraTask, &$branch_name) {
                $branch_name = $jiraTask->formatBranch();
            }
        );


        $this->newLine();
        $this->alert('Branch name is "' . $branch_name . '"');

        if (!$this->option('test')) {
            $this->task(
                title: "Generating the branch \"{$branch_name}\"",
                task: function () use ($jiraTask) {
                    $jiraTask->checkoutOrCreateBranch();
                }
            );
        }

        $this->info("All done thank you");
        return Command::SUCCESS;
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
