<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\CardData;

class CardDataController extends Controller
{
    public function retrieve(int $num_cards): View
    {
        $matching_cards = CardData::where('rarity', 'common')
        ->limit($num_cards)
        ->get();

        return view('card_listing', ['matchingCards' => $matching_cards]);
    }
}
