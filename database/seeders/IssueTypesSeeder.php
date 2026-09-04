<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Repositories\EnumRepositoryInterface;

class IssueTypesSeeder extends Seeder
{
    public function run()
    {
        $repo = app(EnumRepositoryInterface::class);
        $items = [
            ['slug'=>'bug','label'=>'Bug','sort'=>10,'active'=>true],
            ['slug'=>'feature','label'=>'Feature','sort'=>20,'active'=>true],
            ['slug'=>'task','label'=>'Task','sort'=>30,'active'=>true],
        ];
        foreach ($items as $i) {
            $repo->upsert('issue_types', $i, auth()->id() ?? 0);
        }
    }
}
