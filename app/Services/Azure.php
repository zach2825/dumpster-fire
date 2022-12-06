<?php
/** @noinspection PhpPropertyOnlyWrittenInspection */

namespace App\Services;

use App\Contracts\TaskService;
use App\Models\AzureTask;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Azure implements TaskService
{
    private string $baseUrl;

    /**
     * @param             $organization
     * @param string|null $token
     * @param string      $api_version
     * @param string|null $email
     * @param string|null $username
     * @param string|null $project
     */
    public function __construct(
        $organization,
        private string|null $token = null,
        private string $api_version = '5.0',
        private string|null $email = null,
        private string|null $username = null,
        private string|null $project = null,
    ) {
        $this->baseUrl = "https://dev.azure.com/{$organization}/_apis";
    }

    public function transition($task_id, $columnName, $status_name)
    {
        return $this->taskUpdate($task_id, ['/fields/' . $columnName => $status_name], 'add');
    }

    /**
     * Update the task on the board
     * @param $id
     * @param $columns
     * @param $op
     * @return mixed
     */
    public function taskUpdate($id, $columns, $op = 'replace'): mixed
    {
        $updates = [];

        foreach ($columns as $key => $value) {
            $updates[] = [
                'op'    => $op,
                'path'  => $key,
                'value' => $value,
            ];
        }

        return $this->update("wit/workitems/{$id}", $updates);
    }

    public function update($path, $data)
    {
        [$url, $basic] = $this->formatRequest($path);
        $response = Http::withToken($basic, 'basic')
            ->contentType('application/json-patch+json')
            ->patch($url, $data);

        return $response->json() ?? [];
    }

    public function formatRequest($path)
    {
        $username = $this->username;
        $password = $this->token;
        $basic    = base64_encode("{$username}:{$password}");
        $url      = $this->getUrl($path);

        return [$url, $basic];
    }

    public function getUrl($path = '/'): string
    {
        return $this->baseUrl . '/' . $path . "?api-version={$this->api_version}";
    }

    public function assignMe($task_id)
    {
        return $this->taskUpdate($task_id, ['/fields/System.AssignedTo' => $this->email]);
    }

    /**
     * @throws RequestException
     */
    public function taskGet($id): array|AzureTask
    {
        return $this->formatTask($this->get("wit/workitems/{$id}", ['expand' => 'all']) + compact('id'));
    }

    public function formatTask($task = []): array|AzureTask
    {
        if (!$task) {
            return [];
        }

        $collection = ['system' => ['id' => $task['id']]];

        // fields
        foreach ($task['fields'] as $key => $field) {
            $lowerKey = strtolower($key);
            if (str_starts_with($lowerKey, 'system')) {
                Arr::set($collection, $lowerKey, $field);
            }

            if (Str::endsWith($key, '_Kanban.Column')) {
                $collection['system']['transitionColumnName'] = $key;
            }
        }

        $collection['system']['originalJson'] = $task;
        $collection['system']['url']          = $task['url'];

        $status = $this->mapStatusToBranchType($collection['system']['workitemtype']);

        $collection = [
            'task_id'              => $collection['system']['id'],
            'url'                  => $collection['system']['url'],
            'assignedTo'           => Arr::get($collection, 'system.assignedto.displayName'),
            'creatorName'          => Arr::get($collection, 'system.createdby.displayName'),
            'itemType'             => $collection['system']['workitemtype'],
            'itemStatus'           => $status,
            'title'                => $collection['system']['title'],
            'transitionColumnName' => $collection['system']['transitionColumnName'],
            'originalJson'         => $collection['system']['originalJson'],
        ];

        return AzureTask::updateOrCreate(['task_id' => $collection['task_id']], $collection);
    }

    public function mapStatusToBranchType($status): string
    {
        return match (strtolower($status)) {
            'hotfix', 'hot', 'hot fix' => 'hotfix',
            'bug', 'issue' => 'bugfix',
            default => 'feature',
        };
    }

    /**
     * @throws RequestException
     */
    public function get($path, $query = []): array
    {
        [$url, $basic] = $this->formatRequest($path);

        $response = Http::withToken($basic, 'basic')->acceptJson()->get($url, $query);
        $response->throw(); // if error stop here

        return $response->json() ?? [];
    }
}
