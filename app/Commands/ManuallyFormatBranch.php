<?php

namespace App\Commands;

use App\Contracts\GitStuff;
use LaravelZero\Framework\Commands\Command;

class ManuallyFormatBranch extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'branch:name {task_name : typically the subject line from the task}
                                    {task_key=TASK : task_key}
                                    {--k|key=TASK : task key}
                                    {--from=develop : branch to checkout from}
                                    {--t|type=feature : like hotfix or bug or feature}
                                    {--g|generate : checkout to the new branch}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Format the branch and checkout if need';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(GitStuff $gitStuff)
    {
        $branch = $gitStuff->branchNameTemplate(
            $this->argument('task_name'),
            $this->argument('task_key'),
            $this->option('type'),
            ''
        );

        if (($from = $this->option('from')) != null) {
            $title = sprintf('Checking out to "%s" before checking out to "%s".', $from, $branch);
            $this->task($title, fn() => $gitStuff->checkout($from));
        }

        if ($this->option('generate')) {
            $title = sprintf('Checking out to "%s"', $branch);

            $this->task(
                $title,
                function () use ($gitStuff, $branch) {
                    $gitStuff->checkout($branch);
                    $gitStuff->pull("origin", $branch);
                }
            );
        }

        $this->newLine();
        $this->info(sprintf('Done checking out to "%s".', $branch));
        $this->newLine();

        return 0;
    }
}
