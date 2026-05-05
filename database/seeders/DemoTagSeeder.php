<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoTagSeeder extends Seeder
{
    public function run(): void
    {
        $tagsByWorkspace = [
            "Alberto's Vault" => [
                'Rare',
                'Limited',
                'Signed',
                'Mint',
                'Display Piece',
                'Japanese Import',
            ],

            'Art & Sculpture Archive' => [
                'Oil',
                'Acrylic',
                'Bronze',
                'Marble',
                'Modern',
                'Gallery',
                'Signed',
            ],

            'Retro Gaming Vault' => [
                'Nintendo',
                'Sega',
                'PlayStation',
                'Retro',
                'Boxed',
                'Japanese Import',
                'PAL',
            ],
        ];

        foreach ($tagsByWorkspace as $workspaceName => $tags) {
            $workspace = Workspace::where('name', $workspaceName)->firstOrFail();

            foreach ($tags as $tagName) {
                Tag::updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'slug' => Str::slug($tagName),
                    ],
                    [
                        'name' => $tagName,
                    ]
                );
            }
        }
    }
}
