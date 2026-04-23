<?php

namespace App\Actions\Workspaces;

use App\Models\Invitation;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;

class AcceptInvitation
{
    public function handle(Invitation $invitation, User $user): void
    {
        if ($invitation->isAccepted()) {
            throw new \DomainException('Esta invitación ya fue aceptada.');
        }

        if ($invitation->isExpired()) {
            throw new \DomainException('Esta invitación ha expirado.');
        }

        if (mb_strtolower($user->email) !== mb_strtolower($invitation->email)) {
            throw new \DomainException('Esta invitación no corresponde al usuario autenticado.');
        }

        DB::transaction(function () use ($invitation, $user) {
            WorkspaceMember::firstOrCreate(
                [
                    'workspace_id' => $invitation->workspace_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $invitation->role,
                    'joined_at' => now(),
                ]
            );

            $invitation->update([
                'accepted_at' => now(),
            ]);

            session([
                'current_workspace_id' => $invitation->workspace_id,
            ]);
        });
    }
}
