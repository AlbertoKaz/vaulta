<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoWorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $alberto = User::where('email', 'alberto@example.com')->firstOrFail();
        $judith = User::where('email', 'judith@example.com')->firstOrFail();
        $john = User::where('email', 'john@example.com')->firstOrFail();
        $lucy = User::where('email', 'lucy@example.com')->firstOrFail();
        $mike = User::where('email', 'mike@example.com')->firstOrFail();

        $vaults = [
            [
                'name' => "Alberto's Vault",
                'owner' => $alberto,
                'members' => [
                    [$judith, WorkspaceRole::ADMIN],
                    [$john, WorkspaceRole::MEMBER],
                ],
            ],
            [
                'name' => 'Art & Sculpture Archive',
                'owner' => $judith,
                'members' => [
                    [$alberto, WorkspaceRole::ADMIN],
                    [$lucy, WorkspaceRole::MEMBER],
                ],
            ],
            [
                'name' => 'Retro Gaming Vault',
                'owner' => $mike,
                'members' => [
                    [$john, WorkspaceRole::ADMIN],
                    [$lucy, WorkspaceRole::MEMBER],
                ],
            ],
        ];

        foreach ($vaults as $vault) {
            $workspace = Workspace::updateOrCreate(
                ['slug' => Str::slug($vault['name'])],
                [
                    'owner_id' => $vault['owner']->id,
                    'name' => $vault['name'],
                ]
            );

            WorkspaceMember::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'user_id' => $vault['owner']->id,
                ],
                [
                    'role' => WorkspaceRole::OWNER,
                    'joined_at' => now(),
                ]
            );

            foreach ($vault['members'] as [$user, $role]) {
                WorkspaceMember::updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => $role,
                        'joined_at' => now(),
                    ]
                );
            }
        }
    }
}
