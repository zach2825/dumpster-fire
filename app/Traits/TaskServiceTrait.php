<?php

namespace App\Traits;

use App\Contracts\GitStuff;
use Illuminate\Support\Arr;

trait TaskServiceTrait
{
    public function __construct(
        public GitStuff $gitStuff,
        public array $settings = [],
        public string $service_name = '',
    ) {
        $this->service_name = self::class;
        $this->settings     += $this->gitStuff->getConfig(self::$available_settings);
    }

    public function __get(string $name)
    {
        if (Arr::has($this->settings, $name)) {
            return Arr::get($this->settings, $name);
        }

        return null;
    }
}
