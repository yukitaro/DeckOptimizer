<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

interface EnumRepositoryInterface
{
    public function list(string $domain, bool $activeOnly = true): Collection;
    public function findBySlug(string $domain, string $slug);
    public function findById(string $domain, int $id);
    public function upsert(string $domain, array $payload, int $actorId);
    public function refreshCache(string $domain): void;
}
