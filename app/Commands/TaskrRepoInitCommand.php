<?php

namespace App\Commands;

use App\Contracts\GitStuff;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class TaskrRepoInitCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'taskr:repo-init {--e|empties : only ask empty values}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Basically manage git config repo variables and settings';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(GitStuff $gitStuff)
    {
//        $this->notify("Hello Web Artisan", "Love beautiful..", "icon.png");
        $availableSettings = [
            'service',
            'token_type',
            'username',
            'password',
            'endpoint',
            'project',
            'branch_append',
            'api_key',
        ];

        $settings = [];

        array_walk(
            $availableSettings,
            function ($s) use (&$settings, $gitStuff) {
                return $settings[$s] = $gitStuff->getConfig($s) ?? '';
            }
        );

        $services = ['azure', 'aha'];

        $this->title('Service information');

        if ($this->option('empties') && !$settings['service']) {
            $this->info('Available services: ' . implode(' or ', $services));
            $service = $this->askWithCompletion('Which service', $services, $settings['service']);
            $gitStuff->setConfig(compact('service'));
        }


        unset($availableSettings[0]);

        if ($this->option('empties') && !$settings['token_type']) {
            $token_type = $this->askWithCompletion('Token Type', ['basic', 'bearer'], $settings['token_type']);
            $gitStuff->setConfig(compact('token_type'));
        }

        unset($availableSettings[1]);

        $questions = $availableSettings;
        foreach ($questions as $question) {
            if ($this->option('empties') && $settings[$question]) {
                continue;
            }

            if ($question == 'password') {
                $answer = $this->secret(sprintf("What is '%s'?", $question));
                if ($answer) {
                    $gitStuff->setConfig($question, $answer);
                }
            } else {
                $answer = $this->ask(sprintf("What is '%s'?", $question), $settings[$question]);
                $gitStuff->setConfig($question, $answer);
            }
        }

        $this->notify("Taskr", "Init all done, run `taskr` to see a list of commands");
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
