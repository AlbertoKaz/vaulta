<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

class InviteMemberToWorkspace
{
    public function handle(
        Workspace $workspace,
        User $inviter,
        string $email,
        WorkspaceRole $role
    ): Invitation {
        $email = mb_strtolower(trim($email));

        $alreadyMember = $workspace->users()
            ->where('users.email', $email)
            ->exists();

        if ($alreadyMember) {
            throw new \DomainException('Ese usuario ya pertenece al workspace.');
        }

        $pendingInvitation = Invitation::query()
            ->where('workspace_id', $workspace->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($pendingInvitation) {
            throw new \DomainException('Ya existe una invitación pendiente para ese email.');
        }

        return Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);
    }
}
