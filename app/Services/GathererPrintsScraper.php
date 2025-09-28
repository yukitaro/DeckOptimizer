<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;

use App\Utilities\MtgStringUtilities;

class GathererPrintsScraper
{
    public function scrapePrints(string $cardName): array
    {
        $kebabCaseName = MtgStringUtilities::normalizeCardNameToKebabCase($cardName);
        $url = "https://gatherer.wizards.com/prints/{$kebabCaseName}/en-us";

        $response = Http::get($url);
        if (!$response->ok()) {
            throw new \Exception("Failed to fetch prints page for {$cardName}");
        }

        $crawler = new Crawler($response->body());
        $cards = [];

        $crawler->filter('div[data-testid="imageListCard"]')->each(function (Crawler $node) use (&$cards) {
            $cardName = $node->filter('section p')->text('');
            $imageTag = $node->filter('img')->first();

            $imageUrl = null;
            $backImage = null;
            $hasFrontAndBack = $node->filter('[data-testid="cardFrontImage"]')->count() && $node->filter('[data-testid="cardBackImage"]')->count();

            if ($hasFrontAndBack) {
                $imageUrl = $crawler->filter('[data-testid="cardFrontImage"]')->attr('src') ?? null;
                $backImage = $crawler->filter('[data-testid="cardBackImage"]')->attr('src') ?? null;
            } else {
                $imageUrl = $imageTag->attr('src') ?? null;
            }
            
            $imageHash = basename($imageUrl, '.webp');
            $title = $imageTag->attr('title'); // e.g. "Urza's Rage, Strixhaven Mystical Archive"

            [$nameFromTitle, $setName] = explode(',', $title . ',', 2);
            $setName = trim($setName);
            $setCode = $this->extractSetCodeFromUrl($node);


            $cardNumber = $node->filter('div[data-testid="imageListInfo"] span')->text('');
            $setSymbolUrl = $node->filter('div[data-testid="imageListInfo"] img')->attr('src');

            $cards[] = [
                'card_name' => $cardName,
                'set_code' => $setCode,
                'set_name' => $setName,
                'card_number' => trim($cardNumber, '# '),
                'image_hash' => $imageHash,
                'image_url' => $imageUrl,
                'back_image_url' => $backImage ?? null,
                'set_symbol_url' => $setSymbolUrl,
            ];
        });

        return $cards;
    }

    private function extractSetCodeFromUrl(Crawler $node): string
    {
        $href = $node->filter('a')->attr('href'); // e.g. /STA/en-us/47/urzas-rage
        $segments = explode('/', $href);
        return $segments[1] ?? '';
    }
}
