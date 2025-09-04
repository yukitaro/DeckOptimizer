<?php

namespace Database\Seeders;

use League\Csv\Reader;

use App\Models\MagicSetData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MagicSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = base_path('data/all_magic_sets_09012025.csv');
        $csv = Reader::createFromPath($csvPath, 'r');

        // Set the header offset if your CSV has a header row (e.g., first row is headers)
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();

        if (empty($records)) {
            echo 'Well that\'s a problem\n';
        }

        foreach ($records as $record) {
            //$aParsedMagicSet = this.createNewSet($record);
            $aParsedMagicSet = new MagicSetData([
                'official_set_id' => $record['official_set_id'],
                'set_name' => $record['set_name'],
                'published_year' => $record['published_year']
            ]);
            //$aMagicSet = app\Models\MagicSetData;
            //$aMagicSet->set_name = $aParsedMagicSet->set_name;
            //DB::table('magic_sets')->insert($aParsedMagicSet);
            $aParsedMagicSet->save();
        }
            
    }

    public function createNewSet($a_magic_set): MagicSetData
    {
        return new MagicSetData([
            'official_set_id' => $a_magic_set[0],
            'set_name' => $a_magic_set[1],
            'published_year' => $a_magic_set[2]
        ]);
    }
}


