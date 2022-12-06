<?php

/** @noinspection PhpPropertyOnlyWrittenInspection */

namespace App\Services;

use App\Contracts\TaskService;
use App\Models\AHATask;
use App\Traits\TaskServiceTrait;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

use function throw_if;

/**
 * @property string token
 * @property string company
 * @property string taskKey
 * @property string branchNameTemplate
 * @property string tokenType
 * @property string apiUrl
 * @link https://id.atlassian.com/manage-profile/security/api-tokens
 */
class JIRA implements TaskService
{
    use TaskServiceTrait;

    public static array $available_settings = [
        'token',
        'company',
        'taskKey',
        'branchNameTemplate',
        'tokenType',
        'apiUrl',
    ];
    public array $task;
    public $id; // task id without key instead of ABC-12 this is just 12

    /**
     * @throws RequestException
     */
    public function taskGet($id, $withFormat = true): mixed
    {
        $this->id = $id;
        $task     = $this->get("issue/{$this->taskKey}{$id}");

        if ($withFormat) {
            $task = $this->formatTask($task);
        }

        $this->task = $task;

        return $this;
    }

    /**
     * @throws RequestException
     */
    public function get($path): array
    {
        $token = $this->token;
        $url   = $this->getUrl($path);

        $response = Http::withBasicAuth('zrobichaud@onetooneplus.com', $token)->acceptJson()->get($url);
        $response->throw(); // if error stop here

        return $response->json() ?? [];
    }

    public function getUrl($path = '/'): string
    {
        return $this->apiUrl . "/$path";
    }

    public function formatTask($task = []): AHATask|array
    {
        if (!$task) {
            return [];
        }

        $collection = [];

        // fields
        foreach ($task['fields'] as $key => $field) {
            $key = strtolower($key);
            Arr::set($collection, $key, $field);
        }

        return ['id' => $task['id']] + $collection ?? [];
    }

    /**
     * Update the task on the board
     *
     * @param $id
     * @param $columns
     * @return bool
     */
    public function taskUpdate($id, $columns): bool
    {
        return false;
    }

    public function mapStatusToBranchType($status): string
    {
        return match (strtolower($status)) {
            'Bug fix', 'bug', 'issue' => 'bugfix',
            default => 'feature',
        };
    }

    /**
     * @throws Throwable
     */
    public function checkoutOrCreateBranch(): string
    {
        throw_if(!$this->task, 'missing task you need ->getTask first');

        $branch_name = $this->formatBranch();

        $this->gitStuff->checkout($branch_name);

        return $branch_name;
    }

    public function formatBranch($id = null, $task_description = null): string
    {
        if (!$id) {
            $id = $this->id;
        }

        if (!$task_description) {
            $task_description = $this->task['summary'];
        }

        $branch_template = $this->branchNameTemplate;

        $search = [
            '_TASK_KEY_',
            '_TASK_DESCRIPTION_',
        ];

        $replace = [
            "{$this->taskKey}{$id}",
            preg_replace('/-$/', '', Str::limit(Str::slug($task_description), 22, '')),
        ];

        return str_replace($search, $replace, $branch_template);
    }
}
