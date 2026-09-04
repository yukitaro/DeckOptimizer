<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CollectionAnalyticsService
{
    public function valueSummary(int $collectionId): array
    {
        $rows = $this->baseQuery($collectionId)->get();

        return [
            'purchase_total' => $rows->sum('purchase_total'),
            'market_total'   => $rows->sum('market_total'),
            'profit_loss'    => $rows->sum('profit_loss'),
            'percent_gain'   => $rows->sum('purchase_total') > 0
                ? ($rows->sum('profit_loss') / $rows->sum('purchase_total')) * 100
                : 0,
        ];
    }

    private function baseSubquery(int $collectionId)
    {
        $base = $this->baseQuery($collectionId);

        return DB::table(DB::raw("({$base->toSql()}) as t"))
            ->mergeBindings($base);
    }

    public function setsBreakdown(int $collectionId): array
    {
        return $this->baseSubquery($collectionId)
            ->select(
                't.set_name',
                DB::raw('SUM(t.purchase_total) AS purchase_total'),
                DB::raw('SUM(t.market_total) AS market_total'),
                DB::raw('SUM(t.profit_loss) AS profit_loss')
            )
            ->groupBy('t.set_name')
            ->orderBy('t.set_name')
            ->get()
            ->toArray();
    }

    public function rarityBreakdown(int $collectionId): array
    {
        return $this->baseSubquery($collectionId)
            ->select(
                't.rarity',
                DB::raw('SUM(t.total_copies) AS copies'),
                DB::raw('SUM(t.purchase_total) AS purchase_total'),
                DB::raw('SUM(t.market_total) AS market_total')
            )
            ->groupBy('t.rarity')
            ->orderBy('t.rarity')
            ->get()
            ->toArray();
    }

    public function foilBreakdown(int $collectionId): array
    {
        return $this->baseSubquery($collectionId)
            ->select(
                't.is_foil',
                DB::raw('SUM(t.total_copies) AS copies'),
                DB::raw('SUM(t.purchase_total) AS purchase_total'),
                DB::raw('SUM(t.market_total) AS market_total')
            )
            ->groupBy('t.is_foil')
            ->orderBy('t.is_foil')
            ->get()
            ->toArray();
    }

    public function colorIdentityBreakdown(int $collectionId): array
    {
        return $this->baseSubquery($collectionId)
            ->select(
                't.color_identities',
                DB::raw('SUM(t.total_copies) AS copies'),
                DB::raw('SUM(t.market_total) AS market_total')
            )
            ->groupBy('t.color_identities')
            ->orderBy('t.color_identities')
            ->get()
            ->toArray();
    }

    public function topCards(int $collectionId, int $limit = 20): array
    {
        return $this->baseSubquery($collectionId)
            ->orderByDesc('t.market_total')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function baseQuery(int $collectionId)
    {
        return DB::table('collected_cards AS ccs')
            ->join('sets_in_collection AS sic', 'sic.id', '=', 'ccs.set_in_collection_id')
            ->join('collection_management AS cm', 'cm.id', '=', 'sic.collection_management_id')
            ->join('card_data_from_set_data AS cds', 'cds.id', '=', 'ccs.card_data_id')
            ->leftJoin('card_metadata AS meta', 'meta.id', '=', 'cds.card_metadata_id')
            ->leftJoin('mtg_bulk_prices AS prices', 'prices.scryfall_id', '=', 'meta.scryfallId')
            ->where('cm.id', $collectionId)
            ->selectRaw('
                cds.id AS card_id,
                cds.name AS card_name,
                cds.set_name,
                cds.number_in_set,
                cds.rarity,
                cds.colors,
                cds.colorIdentities AS color_identities,
                ccs.is_foil,
                SUM(ccs.card_count) AS total_copies,
                SUM(ccs.purchase_price * ccs.card_count) AS purchase_total,
                SUM(
                    CASE WHEN ccs.is_foil = 1
                        THEN prices.usd_foil * ccs.card_count
                        ELSE prices.usd * ccs.card_count
                    END
                ) AS market_total,
                SUM(
                    CASE WHEN ccs.is_foil = 1
                        THEN (prices.usd_foil * ccs.card_count) - (ccs.purchase_price * ccs.card_count)
                        ELSE (prices.usd * ccs.card_count) - (ccs.purchase_price * ccs.card_count)
                    END
                ) AS profit_loss
            ')
            ->groupBy(
                'cds.id',
                'cds.name',
                'cds.set_name',
                'cds.number_in_set',
                'cds.rarity',
                'cds.colors',
                'cds.colorIdentities',
                'ccs.is_foil'
            );
    }
}
