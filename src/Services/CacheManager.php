<?php

namespace Rcv\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Rcv\Core\Models\ModuleState;

class CacheManager
{
    const CACHE_VERSION = '1.0.0';
    const CACHE_TAG = 'modules';
    const CACHE_PREFIX = 'module_';

    protected $ttl;
    protected $isEnabled;

    public function __construct()
    {
        $this->ttl = Config::get('marketplace.cache.ttl', 3600);
        $this->isEnabled = Config::get('marketplace.cache.enabled', true);
    }

    public function remember(string $key, \Closure $callback)
    {
        if (!$this->isEnabled) {
            return $callback();
        }

        $versionedKey = $this->getVersionedKey($key);
        return Cache::tags([self::CACHE_TAG])->remember($versionedKey, $this->ttl, $callback);
    }

    public function put(string $key, $value, $ttl = null)
    {
        if (!$this->isEnabled) {
            return $value;
        }

        $versionedKey = $this->getVersionedKey($key);
        Cache::tags([self::CACHE_TAG])->put($versionedKey, $value, $ttl ?? $this->ttl);
        return $value;
    }

    public function get(string $key, $default = null)
    {
        if (!$this->isEnabled) {
            return $default;
        }

        $versionedKey = $this->getVersionedKey($key);
        return Cache::tags([self::CACHE_TAG])->get($versionedKey, $default);
    }

    public function forget(string $key)
    {
        if (!$this->isEnabled) {
            return;
        }

        $versionedKey = $this->getVersionedKey($key);
        Cache::tags([self::CACHE_TAG])->forget($versionedKey);
    }

    public function flush()
    {
        if (!$this->isEnabled) {
            return;
        }

        Cache::tags([self::CACHE_TAG])->flush();
    }

    public function warmUp()
    {
        if (!$this->isEnabled) {
            return;
        }

        // Cache module states
        $states = ModuleState::all();
        foreach ($states as $state) {
            $this->put(
                $this->getModuleKey($state->name),
                [
                    'name' => $state->name,
                    'version' => $state->version,
                    'status' => $state->status,
                    'last_enabled' => $state->last_enabled_at,
                    'last_disabled' => $state->last_disabled_at,
                    'applied_migrations' => $state->applied_migrations,
                    'failed_migrations' => $state->failed_migrations
                ]
            );
        }

        // Cache module list
        $this->put('modules.list', $states->pluck('name')->toArray());
    }

    protected function getVersionedKey(string $key): string
    {
        return self::CACHE_PREFIX . self::CACHE_VERSION . ':' . $key;
    }

    protected function getModuleKey(string $moduleName): string
    {
        return 'module.' . strtolower($moduleName);
    }

    public function invalidateModule(string $moduleName)
    {
        if (!$this->isEnabled) {
            return;
        }

        $this->forget($this->getModuleKey($moduleName));
        $this->forget('modules.list');
    }

    public function invalidateAll()
    {
        if (!$this->isEnabled) {
            return;
        }

        $this->flush();
        $this->warmUp();
    }
} 