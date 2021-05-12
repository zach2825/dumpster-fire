<?php

namespace App\Commands;

use App\Contracts\GitStuff;
use App\Models\AzureTask;
use App\Services\Azure;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class AzureTransition extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'azure:transition
                                {boardColumnName? : column from the task where the name has something like "_Kanban.Column" in it}
                                {--id= : number of the task. If not provided try to pull it from the branch name}
                                {--task-id= : number of the task. If not provided try to pull it from the branch name}
                                {--discovery}
                                {--development}
                                {--review}
                                {--dev-complete}
     ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Transition the task on the board';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Azure $azure, GitStuff $gitStuff)
    {
        $task_id         = $this->option('task-id');
        $boardColumnName = $this->argument('boardColumnName');
        $task            = null;

        if (($id = $this->option('id')) !== null) {
            $task    = AzureTask::findOrFail($id);
            $task_id = $task->task_id;
        }

        if (!$task_id) {
            $task_id = $gitStuff->pullTaskID();
        }

        throw_if(!$task_id, 'missing-task_id');

        if (!$boardColumnName) {
            if (!$task) {
                $task = AzureTask::where('task_id', $task_id)->firstOrFail();
            }

            $boardColumnName = $task->transitionColumnName;
        }

        //column name = WEF_EA0EE6A310354DF1B7016083EBF959DF_Kanban.Column

        $statusName = null;

        if ($this->option('discovery')) {
            $statusName = config('df.transition.discovery');
        }
        if ($this->option('development')) {
            $statusName = config('df.transition.development');
        }
        if ($this->option('review')) {
            $statusName = config('df.transition.review');
        }

        throw_if(!$statusName, 'missing-status-name');

        $azure->transition($task_id, $boardColumnName, $statusName);

        return 0;
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
