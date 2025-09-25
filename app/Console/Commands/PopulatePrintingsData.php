<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\CardDataNormalized;

class PopulatePrintingsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-printings-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute to populate the printings data for Cards where it doesn\'t exist yet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate printings data...');

        $cards = CardDataNormalized::whereNull('printings')
                    ->orWhere('printings', '')
                    ->with('setData')
                    ->chunk(1000, function($cards) {
                        foreach ($cards as $card) {
                            $card->update(['printings' => $card->setData->printings ?? '']);
                        }
                        $this->info("Processed a batch of 1000 cards");
                    });
    }
}
