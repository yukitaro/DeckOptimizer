<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Repositories\IssueTypeEnumRepository;
use App\Models\IssueType;

class TestEnumCache extends Command
{
    protected $signature = 'test:enum-cache';
    protected $description = 'Test enum repository cache behavior (tag vs versioned fallback)';

    public function handle(IssueTypeEnumRepository $repo)
    {
        $this->line('Cache driver: ' . config('cache.default'));
        $this->line('Cache store class: ' . get_class(Cache::getStore()));

        $supportsTags = method_exists(Cache::getStore(), 'tags') || (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore);
        $this->info('Supports tags: ' . ($supportsTags ? 'yes' : 'no'));

        $this->line("\n==== Initial list() ====");
        $list1 = $repo->list(true);
        $this->line('Count: ' . $list1->count());
        $this->line('Versions present (first item): ' . ($list1->first()?->version ?? 'n/a'));

        $this->line("\n==== Performing upsert() to bump version and invalidate cache ====");
        // Create a temp slug to upsert (idempotent)
        $slug = 'tst-' . time();
        $row = $repo->upsert([
            'slug' => $slug,
            'label' => 'Test ' . now()->toDateTimeString(),
            'metadata' => ['test' => true],
            'sort' => 9999,
            'active' => true,
        ], 0);

        $this->line("Upserted id={$row->id} slug={$row->slug} version={$row->version}");

        $this->line("\n==== list() after upsert (should reflect new row) ====");
        $list2 = $repo->list(true);
        $this->line('Count: ' . $list2->count());
        $this->line('Found new slug? ' . ($list2->firstWhere('slug', $slug) ? 'yes' : 'no'));

        $this->line("\n==== Cleaning up test row ====");
        // remove test row to keep DB tidy
        IssueType::where('slug', $slug)->delete();
        $this->info('Done.');
    }
}