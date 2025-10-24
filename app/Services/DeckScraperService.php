<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

use App\DataTransferObjects\DeckImportDTO;
use Yukitaro\Scrapers\MtgDecksScraper;
use Yukitaro\Scrapers\DeckScraper;

class DeckScraperService
{
    public function importFromUrl(string $url): DeckImportDTO {
        $scraper = $this->resolveScraper($url);

        $response = Http::withHeaders(['Content-Type' => 'application/json',])
            ->post("http://puppeteer:3000/scrape", [
                'url' => $url,
                'commands' => $scraper->getPuppeteerCommands() ?? [],
            ]);

        if (!$response->ok()) {
            \Log::error('Puppeteer scrape failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("Scrape failed");
        }

        $html = $response->json()['html'] ?? null;

        if (!$html) {
            throw new \Exception("Scraper returned invalid HTML");
        }

        file_put_contents(storage_path('logs/scraper_raw.html'), $html);

        $deckData = $scraper->parseDeck($html, $url);

        return new DeckImportDTO(
            name: $deckData['name'],
            format: $deckData['format'],
            mainboard: $deckData['mainboard'],
            sideboard: $deckData['sideboard'],
            sourceUrl: $url,
            archetype: $deckData['archetype'] ?? null,
            tags: $deckData['tags'] ?? null,
            description: $deckData['description'] ?? null,
        );
    }

    private function resolveScraper(string $url): DeckScraper {
        foreach ($this->scraperPatterns as $pattern => $scraperClass) {
            if (preg_match($pattern, $url)) {
                return app($scraperClass);
            }
        }

        throw new \Exception("No scraper matched for URL: $url");
    }
    protected array $scraperPatterns = [
        '/mtgdecks\.net\/.+/' => \Yukitaro\Scrapers\MtgDecksScraper::class,
        //'/mtgtop8\.com\/deck\?id=\d+/' => \App\Scrapers\MTGTop8Scraper::class,
        // Add more patterns here
    ];
}