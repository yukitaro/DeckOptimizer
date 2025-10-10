<?php

namespace App\Utilities;

class CollectionExportUtility
{
    public static function toCsv($collection)
    {
        $lines = [];
        $lines[] = "Set,Collector Number,Card Name,Count";

        foreach ($collection->sets as $set) {
            foreach ($set->cards as $card) {
                $lines[] = "{$set->set_name},{$card->collector_number},\"{$card->name}\",{$card->pivot->card_count}";
            }
        }

        return implode("\n", $lines);
    }
}