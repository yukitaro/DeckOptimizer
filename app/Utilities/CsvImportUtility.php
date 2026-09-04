<?php

namespace App\Utilities;

use League\Csv\Reader;

class CsvImportUtility
{
    public static function parse($file): array
    {
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0); // assumes first row is headers

        $records = [];
        foreach ($csv->getRecords() as $row) {
            $records[] = $row;
        }

        return $records;
    }
}