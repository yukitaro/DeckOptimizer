<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeckScraperService;

class DeckScrapersController extends Controller
{
    protected $deckScraperService;

    public function __construct(DeckScraperService $deckScraperService)
    {
        $this->deckScraperService = $deckScraperService;
    }

    public function processImportFromUrl(Request $request)
    {
        try {
            $dto = $this->deckScraperService->importFromUrl($request->input('url'));
            return response()->json($dto);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }    
    }
}
