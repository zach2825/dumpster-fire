<?php

namespace App\Services;


use App\Contracts\TaskService;
use App\Models\AzureTask;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Azure implements TaskService
{
    private string $token;
    private string $baseUrl;
    private string $api_version;
    private string $username;
    private string $email;

    /**
     * Azure constructor.
     * @param        $organization
     * @param        $username
     * @param        $token
     * @param null   $project
     * @param string $api_version
     */
    public function __construct(
        $organization,
        $username = null,
        $token = null,
        $project = null,
        string $api_version = '5.0'
    ) {
        $this->username = $username ?? config('df.username');
        $this->email    = config('df.email');
        $this->token    = $token ?? config('df.personal_access_token');

        $this->baseUrl     = "https://dev.azure.com/{$organization}/_apis";
        $this->api_version = $api_version;
    }

    public function getUrl($path = '/'): string
    {
        return $this->baseUrl . '/' . $path . "?api-version={$this->api_version}";
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

    public function formatRequest($path)
    {
        $username = $this->username;
        $password = $this->token;
        $basic    = base64_encode("{$username}:{$password}");
        $url      = $this->getUrl($path);

        return [$url, $basic];
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

    public function update($path, $data)
    {
        [$url, $basic] = $this->formatRequest($path);
        $response = Http::withToken($basic, 'basic')
            ->contentType('application/json-patch+json')
            ->patch($url, $data);

//        $test = $response->body();

        return $response->json() ?? [];
    }

    public function transition($task_id, $columnName, $status_name)
    {
        return $this->taskUpdate($task_id, ['/fields/' . $columnName => $status_name], 'add');
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

    /**
     * Update the task on the board
     * @param $id
     * @param $columns
     * @return bool
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

        $response = $this->update("wit/workitems/{$id}", $updates);

        return $response;
    }

    public function mapStatusToBranchType($status): string
    {
        switch (strtolower($status)) {
            case "hotfix":
            case "hot":
            case "hot fix":
                return 'hotfix';
            case "bug":
            case "issue":
                return 'bugfix';
            case "tech debt":
            case "user story":
            case "feature":
            default:
                return 'feature';
        }
    }
}
