<?php

/** @noinspection PhpPropertyOnlyWrittenInspection */

namespace App\Services;

use App\Contracts\TaskService;
use App\Models\AHATask;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class AHA implements TaskService
{
    private string $baseUrl;

    /**
     * @param string      $token
     * @param string      $company
     * @param string      $task_key
     * @param string|null $username
     * @param string      $api_version
     */
    public function __construct(
        public string $token,
        private string $company,
        private string $task_key,
        private string|null $username = null,
        private string $api_version = 'v1',
    ) {
        $this->baseUrl = "https://{$company}.aha.io/api";
    }

    /**
     * @throws RequestException
     */
    public function taskGet($id, $withFormat = true): AHATask|array
    {
        $task = $this->get("features/{$this->task_key}{$id}") + compact('id');

        if ($withFormat) {
            return $this->formatTask($task);
        }

        return $task;
    }

    /**
     * @throws RequestException
     */
    public function get($path): array
    {
        $token = $this->token;
        $url   = $this->getUrl($path);

        $response = Http::withToken($token)->acceptJson()->get($url);
        $response->throw(); // if error stop here

        return $response->json() ?? [];
    }

    public function getUrl($path = '/'): string
    {
        return $this->baseUrl . "/{$this->api_version}/$path";
    }

    public function formatTask($task = []): AHATask|array
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
}
