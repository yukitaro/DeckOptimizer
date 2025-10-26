<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\CardDataFromSetData;

class ExportDBToCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-db-to-csv';

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
            $rows = CardDataFromSetData::where('name', 'like', '%//%')->with('cardMetadata')->get();
            $csv = fopen(storage_path('app/export.csv'), 'w');

            fputcsv($csv, ['card_uuid', 'name', 'set_name', 'number_in_set', 'scryfallId']); // headers
            foreach ($rows as $row) {
                fputcsv($csv, [$row->card_uuid, $row->name, $row->set_name, $row->number_in_set, $row->cardMetadata->scryfallId]);
            }

            fclose($csv);
            $this->info('CSV exported!');
    }
}
