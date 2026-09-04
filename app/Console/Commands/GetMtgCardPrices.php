<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GetMtgCardPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-mtg-card-prices';
    protected $description = 'Fetch and store daily MTG card prices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
    }
}
