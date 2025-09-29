<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Utilities\MtgStringUtilities;
use App\Models\CardDataFromSetData;

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

        $crawler->filter('div[data-testid="imageListCard"]')->each(function (Crawler $node, $i) use (&$cards) {
            try {
                $imageTag = $node->filter('div[data-testid="cardPreviewImage"] img')->first();
                $imageUrl = $imageTag->attr('src') ?? null;
                $imageHash = basename(parse_url($imageUrl, PHP_URL_PATH), '.webp');
                $title = $imageTag->attr('title') ?? '';

                [$nameFromTitle, $setName] = explode(',', $title . ',', 2);
                $cardName = trim($nameFromTitle);
                $setName = trim($setName);

                $href = $node->filter('a')->attr('href'); // e.g. /STA/en-us/47/urzas-rage
                $segments = explode('/', $href);
                $setCode = $segments[1] ?? '';
                $cardNumber = $segments[2] ?? '';

                $imageHash = basename(parse_url($imageUrl, PHP_URL_PATH), '.webp');
                Log::debug("Image hash for {$cardName} ({$setCode})", ['hash' => $imageHash]);

                Log::debug("Extracted: cardName='{$cardName}', setCode='{$setCode}', setName='{$setName}', title='{$title}'");

                // Resolve UUID from local DB
                $uuid = CardDataFromSetData::where('name', $cardName)
                    ->where('set_name', $setCode)
                    ->value('card_uuid');

                if ($uuid) {
                    $cards[] = [
                        'card_name' => $cardName,
                        'set_code' => $setCode,
                        'set_name' => $setName,
                        'card_number' => $cardNumber,
                        'card_uuid' => $uuid,
                        'image_hash' => $imageHash,
                        'image_url' => $imageUrl,
                    ];
                }
            } catch (\Exception $e) {
                // Skip broken nodes
            }
        });

        return $cards;
    }
}
