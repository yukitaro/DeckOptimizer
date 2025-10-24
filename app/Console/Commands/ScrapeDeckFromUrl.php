<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DeckScraperService;

class ScrapeDeckFromUrl extends Command
{
    protected $signature = 'decks:scrape-url {url}';
    protected $description = 'Scrape a deck from a given URL and dump the DTO';

    public function handle(DeckScraperService $service)
    {
        $url = $this->argument('url');

        try {
            $dto = $service->importFromUrl($url);
            dump($dto);
        } catch (\Exception $e) {
            $this->error("Scrape failed: " . $e->getMessage());
        }
    }
}
