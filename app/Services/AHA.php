<?php

namespace App\Services;


use App\Contracts\TaskService;
use App\Models\AHATask;
use App\Models\AzureTask;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\Types\Boolean;

class AHA implements TaskService
{
    private string $token;
    private string $baseUrl;
    private string $api_version;
    private string $username;
    private $company;
    private $task_key;

    /**
     * AHA constructor.
     * @param        $token
     * @param        $company
     * @param        $task_key
     */
    public function __construct(
        $token,
        $company,
        $task_key,
    ) {
        $this->baseUrl     = "https://{$company}.aha.io/api";
        $this->api_version = 'v1';
        $this->token       = $token;
        $this->task_key    = $task_key;
    }

    public function getUrl($path = '/'): string
    {
        return $this->baseUrl . "/{$this->api_version}/$path";
    }

    public function formatTask($task = []): AHATask
    {
        if (!$task) {
            return [];
        }

        $collection = [];

        // fields
        foreach ($task['feature'] as $key => $field) {
            $key = strtolower($key);
            Arr::set($collection, $key, $field);
        }

        return new AHATask(['id' => $task['id']] + $collection ?? []);
    }

    /**
     * @throws RequestException
     */
    public function get($path): array
    {
        $token = $this->token;
        $url      = $this->getUrl($path);

        $response = Http::withToken($token)->acceptJson()->get($url);
        $response->throw(); // if error stop here

        return $response->json() ?? [];
    }

    /**
     * @throws RequestException
     */
    public function taskGet($id, $withFormat = true): mixed
    {
        $task = $this->get("features/{$this->task_key}{$id}") + compact('id');

        if ($withFormat) {
            return $this->formatTask($task);
        }

        return $task;
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
            case "Bug fix":
            case "bug":
            case "issue":
                return 'bugfix';
            case "new":
            case "tech debt":
            case "user story":
            case "feature":
            default:
                return 'feature';
        }
    }
}
