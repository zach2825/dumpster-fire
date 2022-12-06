<?php

namespace App\Commands;

use App\Contracts\GitStuff;
use App\enums\ImportTypesEnums;
use App\Services\JIRA;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class TaskrRepoInitCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'taskr:repo-init {--e|empties : only ask empty values} {--j|jira : settings for jira}';

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
     * @throws Throwable
     */
    public function handle(GitStuff $gitStuff)
    {
        $service_name = ImportTypesEnums::UNKNOWN;
        $service_setting = $this->option('jira');

        $available_settings = [];

        if ($this->option('jira')) {
            $available_settings += JIRA::$available_settings;
            $service_name       = ImportTypesEnums::JIRA;
        }

        throw_if($service_name === ImportTypesEnums::UNKNOWN, 'missing-service');

        $this->title('Service information');
        $gitStuff->setConfig([
            'service' => $service_name->name,
        ]);

        $settings = [];

        array_walk(
            $available_settings,
            function ($s) use (&$settings, $gitStuff) {
                return $settings[$s] = $gitStuff->getConfig($s) ?? '';
            }
        );

        if ($this->option('empties') && !$settings['token_type']) {
            $token_type = $this->askWithCompletion('Token Type', ['basic', 'bearer'], $settings['token_type']);
            $gitStuff->setConfig(compact('token_type'));
        }

        $questions = $available_settings;
        foreach ($questions as $question) {
            if ($this->option('empties') && $settings[$question]) {
                continue;
            }

            $answer = $this->askQuestion($question, $question === 'password' ? 'password' : 'text');
            $gitStuff->setConfig([$question => $answer]);
        }

        $this->info('Taskr', 'Init all done, run `taskr` to see a list of commands');

        return 0;
    }

    public function askQuestion($question, $type)
    {
        if ($question == 'password') {
            return $this->secret(sprintf("What is '%s'?", $question));
        }

        return $this->ask(sprintf("What is '%s'?", $question), $question);
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
