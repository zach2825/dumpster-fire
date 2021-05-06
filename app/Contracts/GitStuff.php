<?php


namespace App\Contracts;

use App\Models\Tasks;
use Github\Api\PullRequest;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Support\Str;

class GitStuff
{
    /**
     * @var string
     */
    public $organization;
    /**
     * @var string
     */
    public $repo;
    public $branchPrefix = null;
    /**
     * @var PullTaskNumber
     */
    private $task;
    /**
     * @var string
     */
    private $task_key;

    /**
     * GitStuff constructor.
     * @param PullTaskNumber $taskNumber
     * @param string         $task_key
     */
    public function __construct(PullTaskNumber $taskNumber, string $task_key = 'AB#')
    {
        $this->task = $taskNumber;

        $this->organization = config('github.default_organization');
        $this->repo         = config('github.default_repo');

        $this->branchPrefix = config('branching.branch_prefix');
        $this->task_key     = $task_key;
    }

    public function makePR($branch_to, $description, $in_progress = false)
    {
        /** @var Tasks $task */
        $task    = $this->task->getTask();
        $prepend = $in_progress ? '[WIP] - ' : '';

        $task_link_host = config('atlassian.jira.host');

        $settings = [
            'base'  => $branch_to,
            'head'  => $task->task_branch,
            'title' => $prepend . "ST-{$task->task_id} - {$task->title}",
            'body'  => $description . "\r\n - {$task_link_host}/browse/ST-{$task->task_id}",
        ];

        /** @var PullRequest $github_pull_request */
        $github_pull_request = GitHub::pullRequest();

        return $github_pull_request->create($this->organization, $this->repo, $settings);
    }

    public function getPRS()
    {
        /** @var PullRequest $github_pull_request */
        $github_pull_request = GitHub::pullRequest();

        return $github_pull_request->all($this->organization, $this->repo);
    }

    public function push($branch_name, $remote = 'origin')
    {
        exec("git push {$remote} {$branch_name}", $lines);

        return $lines;
    }

    public function checkoutDevelop($remote = 'origin', $branch = 'develop')
    {
        $this->checkout($branch);      // check out to the branch
        $this->pull($remote, $branch); // update from remove
    }

    public function checkout($branch)
    {
        $output = null;

        exec("git checkout '$branch' 2> /dev/null || git checkout -b '$branch' 2> /dev/null", $output);

        return $output;
    }

    public function pull($remote, $branch)
    {
        exec("git pull $remote '$branch'");
    }

    public function makeBranch($name)
    {
        exec("git checkout '{$name}' 2> /dev/null || git checkout -b '{$name}' 2> /dev/null");

        return true;
    }

    public function getBranchMessages()
    {
        $branch_prefix = config('branching.branch_prefix');
        $task          = $this->task->getTask();

        exec("git log develop..'{$branch_prefix}/{$task->task_branch}'  --format=%s --no-merges", $output);

        return $output;
    }

    public function formatLogMessage($messages)
    {
        $task_key     = $this->task->pullTaskKey();
        $comment_body = preg_replace("/^{$task_key}\s?-?\s?/", '', $messages);

        if (is_array($messages)) {
            $comment_body = implode(PHP_EOL, $comment_body);
        }

        $comment_body = str_replace('  ', ' ', $comment_body);

        return $comment_body;
    }

    public function branchNameTemplate($subject, $task_id, $type = 'feature', $task_key = null)
    {
        $key = ($task_key ?? $this->task_key) . $task_id;

        // Build new branch name
        return sprintf(
            '%s/%s/%s%s',
            $type, // task type bugfix or feature usually
            $key,  // project key #SM-2345 or whatever
            // sluggable description
            preg_replace('/-$/', '', Str::limit(Str::slug($subject), 22, '')),
            config('git.branch_append', '')
        );
    }
}
