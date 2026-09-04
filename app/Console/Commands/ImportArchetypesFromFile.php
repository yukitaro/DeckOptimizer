<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\MtgArchetype;

class ImportArchetypesFromFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-archetypes-from-file {--dry-run}';

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
        $path = base_path('data/mtg_archetypes_seed.csv');
        $rows = array_map('str_getcsv', file($path));
        $header = array_map(fn($h) => trim(str_replace("\r", '', $h)), array_shift($rows));

        $dryRun = $this->option('dry-run');
        $imported = 0;

        foreach ($rows as $row) {
            $row = array_map(fn($v) => trim(str_replace("\r", '', $v)), $row);
            $data = array_combine($header, $row);

            if ($dryRun) {
                $this->line("Would import: {$data['name']} ({$data['format']}) [source_id: {$data['archetype_source_site_id']}]");
                continue;
            }

            MtgArchetype::updateOrCreate(
                [
                    'name' => $data['name']
                ],
                [
                    'format' => $data['format'] ?? null,
                    'description' => $data['description'] ?? null,
                    'criteria' => $data['criteria'] ?? null,
                    'archetype_source_site' => $data['archetype_source_site'] ?? 'mtgdecks',
                    'archetype_source_site_id' => $data['archetype_source_site_id'] ?? null,
                ]
            );

            $imported++;
        }

        if ($dryRun) {
            $this->info("Dry run complete. {$imported} archetypes previewed.");
        } else {
            $this->info("✅ Imported {$imported} archetypes from CSV.");
        }
    }
}
