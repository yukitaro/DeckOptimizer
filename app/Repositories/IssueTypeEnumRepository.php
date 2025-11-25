<?php

namespace App\Repositories;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\IssueType;

class IssueTypeEnumRepository
{
    protected int $ttl = 3600;
    protected string $tag = 'enums:issue_types';

    // cache helpers
    protected function cacheSupportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }

    protected function listCacheKey(string $domain, bool $activeOnly): string
    {
        $version = Cache::get("enums:{$domain}:version", 1);
        $active = $activeOnly ? '1' : '0';
        return "enums:{$domain}:v{$version}:active{$active}:list";
    }

    protected function rememberList(string $domain, int $ttl, callable $loader, bool $activeOnly = true)
    {
        $tag = "enums:{$domain}";
        if ($this->cacheSupportsTags()) {
            return Cache::tags([$tag])->remember("{$tag}:list:".($activeOnly?1:0), $ttl, $loader);
        }

        $key = $this->listCacheKey($domain, $activeOnly);
        return Cache::remember($key, $ttl, $loader);
    }

    protected function flushListCache(string $domain): void
    {
        $tag = "enums:{$domain}";
        if ($this->cacheSupportsTags()) {
            Cache::tags([$tag])->flush();
            return;
        }

        // non-tag fallback: delete the list key and increment version so other derived keys become stale
        Cache::forget($this->listCacheKey($domain, true));
        Cache::forget($this->listCacheKey($domain, false));
        Cache::increment("enums:{$domain}:version");
    }    

    // core methods

    public function list(bool $activeOnly = true)
    {
        return $this->rememberList('issue_types', $this->ttl, function () use ($activeOnly) {
            $q = IssueType::orderBy('sort');
            if ($activeOnly) $q->where('active', true);
            return $q->get();
        }, $activeOnly);
    }

    public function findBySlug(string $slug): ?IssueType
    {
        return $this->list()->firstWhere('slug', $slug) ?: null;
    }

    public function findById(int $id): ?IssueType
    {
        return $this->list(false)->firstWhere('id', $id) ?: null;
    }

   public function upsert(array $payload, int $actorId)
    {
        $row = \DB::transaction(function () use ($payload, $actorId) {
            $row = IssueType::updateOrCreate(
                ['slug' => $payload['slug']],
                array_merge($payload, [
                    'updated_by' => $actorId,
                    'version' => \DB::raw('coalesce(version, 0) + 1'),
                ])
            );
            return $row->fresh();
        });

        $this->flushListCache('issue_types');

        // broadcast event after cache invalidation/version bump
        event(new \App\Events\EnumUpdated('issue_types', $row->version));

        return $row;
    }

    public function flushCache(): void
    {
        Cache::tags([$this->tag])->flush();
    }
}
