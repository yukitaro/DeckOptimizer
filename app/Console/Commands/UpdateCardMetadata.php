<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateCardMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-card-metadata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Okay Urza's Power Plant got Version 1-4, each maps to 84a, 84c, 84b, 84d
        // (Extended Art) is frameEffects: "extendedart"
        // (Borderless) is borderColor: "borderless", isFullArt: true, BUT no frameEffects
        // (Showcase) is frameEffects: "showcase"
        // (Retro Frame) is frameVersion: "1997" + borderColor: "black" -- any other combinations for retro??
        // (Foil Etched) is finishes: ["foil", "etched"]
        // ("Alternate Art") is probably frameEffects: "inverted", borderColor: "borderless", isFullArt:true
    }
}
