<?php

namespace App\Contracts;

interface TaskService
{
    public function getUrl($path = '/'): string;

    public function get($path): array;

    public function taskGet($id): mixed;

    public function formatBranch($id, $task_description): string;

    public function mapStatusToBranchType($status): string;

    public function checkoutOrCreateBranch(): string;
}
