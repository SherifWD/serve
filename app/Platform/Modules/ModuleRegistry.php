<?php

namespace App\Platform\Modules;

use App\Platform\Modules\Models\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ModuleRegistry
{
    /**
     * Cache of loaded modules keyed by module key.
     *
     * @var Collection<string, Module>
     */
    protected ?Collection $modules = null;

    public function all(): Collection
    {
        $this->ensureLoaded();

        return $this->modules;
    }

    public function get(string $key): ?Module
    {
        $this->ensureLoaded();

        return $this->modules->get($key);
    }

    public function has(string $key): bool
    {
        $this->ensureLoaded();

        return $this->modules->has($key);
    }

    protected function ensureLoaded(): void
    {
        if ($this->modules !== null) {
            return;
        }

        if (!Schema::hasTable('modules')) {
            $this->modules = collect();

            return;
        }

        $this->modules = Module::query()->get()->keyBy('key');
    }
}
