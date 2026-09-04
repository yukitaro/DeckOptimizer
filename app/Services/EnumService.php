<?php

namespace App\Services;

use App\Events\EnumUpdated;
use App\Repositories\DbEnumRepository;

class EnumService
{
    private DbEnumRepository $repo;

    public function __construct(DbEnumRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * List all values for a given domain.
     */
    public function list(string $domain, bool $activeOnly = true)
    {
        return $this->repo->list($domain, $activeOnly);
    }

    /**
     * Upsert a value for a given domain.
     */
    public function upsert(string $domain, array $payload, int $actorId)
    {
        $row = $this->repo->upsert($domain, $payload, $actorId);

        // flush cache and broadcast
        $this->repo->refreshCache($domain);
        event(new EnumUpdated($domain, (int) $row->version));

        return $row;
    }

    /**
     * Find by slug.
     */
    public function findBySlug(string $domain, string $slug)
    {
        return $this->repo->findBySlug($domain, $slug);
    }

    /**
     * Find by id.
     */
    public function findById(string $domain, int $id)
    {
        return $this->repo->findById($domain, $id);
    }
}
