<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use App\Models\SetData;

class FetchSetSymbols extends Command
{
    protected $signature = 'magic:fetch-set-symbols';
    protected $description = 'Download and store set symbol images locally';

    public function handle()
    {
        $wotcSetCodeMap = [
            'LEA' => '1E',
            'LEB' => '2E',
            '2ED' => '2U',
            'ARN' => 'AN',
            'ATQ' => 'AQ',
            '3ED' => '3E',
            'LEG' => 'LE',
            'DRK' => 'DK',
            'FEM' => 'FE',
            '4ED' => '4E',
            'ICE' => 'IA',
            'CHR' => 'CH',
            'HML' => 'HM',
            'ALL' => 'AL',
            'MIR' => 'MI',
            'VIS' => 'VI',
            '5ED' => '5E',
            'POR' => 'PO',
            'WTH' => 'WL',
            'TMP' => 'TE',
            'STH' => 'ST',
            'EXO' => 'EX',
            'P02' => 'P2',
            'UGL' => 'UG',
            'USG' => 'UZ',
            'ATH' => 'ATH',
            'ULG' => 'UL',
            '6ED' => '6E',
            'PTK' => 'PK',
            'UDS' => 'UD',
            'S99' => 'P3',
            'MMQ' => 'MM',
            'BRB' => 'BRB',
            'NEM' => 'NE',
            'S00' => 'S00',
            'PCY' => 'PR',
            'INV' => 'IN',
            'BTD' => 'BTD',
            'PLS' => 'PS',
            '7ED' => '7E',
            'APC' => 'AP',
            'ODY' => 'OD',
        ];

        $sets = SetData::where('total_cards', '>',91)->get();
        $disk = Storage::disk('public');
        $path = 'set-symbols';

        $disk->makeDirectory($path);

        foreach ($sets as $set) {
            $code = $set->set_name;

            $filename = "{$path}/{$code}.png";

            $code = $wotcSetCodeMap[$code] ?? $code;

            if ($disk->exists($filename)) {
                $this->info("✔ Symbol already exists for {$code}");
                continue;
            }

            $url = "https://gatherer-static.wizards.com/set_symbols/{$code}/large-common-{$code}.png";

            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $disk->put($filename, $response->body());
                    $this->info("✅ Downloaded symbol for {$code}");
                } else {
                    $this->warn("⚠️ Failed to fetch {$code} (HTTP {$response->status()})");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error fetching {$code}: {$e->getMessage()}");
            }
        }

        $this->info('🎉 Symbol fetch complete.');
    }
}