<?php

namespace App\Services;


use App\Contracts\TaskService;
use App\Models\AzureTask;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\Types\Boolean;

class Azure implements TaskService
{
    private string $token;
    private string $baseUrl;
    private string $api_version;
    private string $username;

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
            $key = strtolower($key);
            if (str_starts_with($key, 'system')) {
                Arr::set($collection, $key, $field);
            }
        }

        return new AzureTask($collection['system'] ?? []);
    }

    /**
     * @throws RequestException
     */
    public function get($path): array
    {
        $username = $this->username;
        $password = $this->token;
        $basic    = base64_encode("{$username}:{$password}");
        $url      = $this->getUrl($path);

        $response = Http::withToken($basic, 'basic')->acceptJson()->get($url);
        $response->throw(); // if error stop here

        return $response->json() ?? [];
    }

    /**
     * @throws RequestException
     */
    public function taskGet($id): array|AzureTask
    {
        return $this->formatTask($this->get("wit/workItems/{$id}") + compact('id'));
    }

    /**
     * Update the task on the board
     * @param $id
     * @param $columns
     * @return bool
     */
    public function taskUpdate($id, $columns): Boolean
    {
        return false;
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
