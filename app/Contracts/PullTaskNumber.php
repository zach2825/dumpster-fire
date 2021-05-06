<?php


namespace App\Contracts;


use App\Models\Tasks;
use Atlassian\JiraRest\Requests\Issue\Parameters\Comment\AddParameters;

class PullTaskNumber
{
    public $task_id = null;
    public $branch  = null;

    public function setCommandOptions($task_id = null, $force = false)
    {
        if ($force || !empty($task_id)) {
            if ($task_id) {
                $this->task_id = $task_id;
            } else {
                exec('git branch', $lines);
                $branch = '';
                foreach ($lines as $line) {
                    if (strpos($line, '*') === 0) {
                        $branch       = ltrim($line, '* ');
                        $this->branch = $branch;
                        break;
                    }
                }

                preg_match_all('/st-([^-]+)/', $branch, $matches);
                $this->task_id = array_get($matches, '1.0');

                if (!$this->task_id) {
                    die('Can\'t get task id from the branch name. Try pass in the task number');
                }
            }
        }

        return $this;
    }

    public function addTaskComment($comment_body)
    {
        $task_key     = $this->pullTaskKey();
        $comment_body = preg_replace("/^{$task_key}\s?-?\s?/", '', $comment_body);

        if (is_array($comment_body)) {
            $comment_body = implode(PHP_EOL, $comment_body);
        }

        $jira = new Jira($this);
        die($comment_body);
        $comment       = new AddParameters();
        $comment->body = $comment_body;

        return $jira->issueAddComment($task_key, $comment);
    }

    public function pullTaskKey()
    {
        $jira = new Jira($this);

        return "{$jira->issue_prefix}{$this->task_id}";
    }

    public function pullTaskID()
    {
        return $this->task_id;
    }

    public function getTask()
    {
        return Tasks::task_id($this->task_id);
    }
}
