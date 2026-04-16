<?php

namespace App\Listeners;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDefaultWorkspaceForUser
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        DB::transaction(function () use ($user) {
            $baseName = "$user->name's Vault";

            $workspace = Workspace::create([
                'owner_id' => $user->id,
                'name' => $baseName,
                'slug' => $this->generateUniqueSlug($baseName),
            ]);

            WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceRole::OWNER,
                'joined_at' => now(),
            ]);
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = "$baseSlug-$counter";
            $counter++;
        }

        return $slug;
    }
}
