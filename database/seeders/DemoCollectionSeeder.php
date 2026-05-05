<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collectionsByWorkspace = [
            "Alberto's Vault" => [
                'Designer Figures',
                'Limited Editions',
                'Collector Memorabilia',
            ],

            'Art & Sculpture Archive' => [
                'Contemporary Paintings',
                'Sculptures',
                'Gallery Pieces',
            ],

            'Retro Gaming Vault' => [
                'Retro Consoles',
                'Physical Games',
                'Gaming Accessories',
            ],
        ];

        foreach ($collectionsByWorkspace as $workspaceName => $collectionNames) {
            $workspace = Workspace::where('name', $workspaceName)->firstOrFail();

            foreach ($collectionNames as $collectionName) {
                Collection::updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'slug' => Str::slug($collectionName),
                    ],
                    [
                        'name' => $collectionName,
                    ]
                );
            }
        }
    }
}
