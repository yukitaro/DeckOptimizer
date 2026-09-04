<?php

// app/Repositories/DbEnumRepository.php
namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\EnumValue;
use App\Events\EnumUpdated;

class DbEnumRepository implements EnumRepositoryInterface
{
    protected int $ttl = 3600;

    public function list(string $domain, bool $activeOnly = true): Collection
    {
        $tag = "enums:{$domain}";
        return Cache::tags([$tag])->remember("{$tag}:list", $this->ttl, function () use ($domain, $activeOnly) {
            $q = EnumValue::where('domain', $domain)->orderBy('sort');
            if ($activeOnly) $q->where('active', true);
            return $q->get();
        });
    }

    public function findBySlug(string $domain, string $slug)
    {
        return $this->list($domain)->firstWhere('slug', $slug);
    }

    public function findById(string $domain, int $id)
    {
        return $this->list($domain)->firstWhere('id', $id);
    }

    public function upsert(string $domain, array $payload, int $actorId)
    {
        $row = DB::transaction(function () use ($domain, $payload, $actorId) {
            $row = EnumValue::updateOrCreate(
                ['domain' => $domain, 'slug' => $payload['slug']],
                array_merge($payload, [
                    'updated_by' => $actorId,
                ])
            );

            // increment version with a proper update
            $row->increment('version');

            return $row->fresh();
        });

        // flush cache after transaction
        Cache::tags(["enums:{$domain}"])->flush();

        // broadcast event with the actual integer version
        event(new EnumUpdated($domain, (int) $row->version));

        return $row;
    }

    public function refreshCache(string $domain): void
    {
        Cache::tags(["enums:{$domain}"])->flush();
    }
}
