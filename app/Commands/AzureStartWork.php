<?php

namespace App\Commands;

use App\Contracts\GitStuff;
use App\Services\Azure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\RequestException;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class AzureStartWork extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'azure:task-start';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Move an azure task from ready or discovery to in progress';

    /**
     * Execute the console command.
     *
     * @param Azure    $azure
     * @param GitStuff $gitStuff
     * @return int
     *
     * @throws RequestException
     * @throws Throwable
     */
    public function handle(Azure $azure, GitStuff $gitStuff): int
    {
        $task_id        = $gitStuff->pullTaskID();
        $task           = $azure->taskGet($task_id);
        $movableColumns = [
            config('df.transition.ready')     => true,
            config('df.transition.discovery') => true,
        ];

        // if the task is ready or in discovery its ok to move the task
        if (isset($movableColumns[$task->boardColumn])) {
            $this->call(
                'azure:transition',
                [
                    '--task-id'     => $task->task_id,
                    '--development' => true,
                ]
            );

            $this->info('Task is not in the ' . config('df.transition.development') . ' column');
        } else {
            $output = sprintf(
                'Task could not be moved to the "%s" column from "%s".',
                config('df.transition.development'),
                $task->boardColumn
            );
            $this->info($output);
        }

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
