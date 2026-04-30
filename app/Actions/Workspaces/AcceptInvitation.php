<?php

namespace App\Actions\Workspaces;

use App\Actions\Activity\RecordActivity;
use App\Models\Invitation;
use App\Models\User;
use App\Models\WorkspaceMember;
use DomainException;
use Illuminate\Support\Facades\DB;

class AcceptInvitation
{
    public function handle(Invitation $invitation, User $user): void
    {
        // 🚫 Ya aceptada
        if ($invitation->accepted_at) {
            throw new DomainException('Esta invitación ya fue aceptada.');
        }

        // 🚫 Expirada
        if ($invitation->expires_at && now()->greaterThan($invitation->expires_at)) {
            throw new DomainException('Esta invitación ha expirado.');
        }

        // 🚫 Email no coincide
        if (mb_strtolower($user->email) !== mb_strtolower($invitation->email)) {
            throw new DomainException('Esta invitación no corresponde a tu cuenta.');
        }

        DB::transaction(function () use ($invitation, $user) {

            // ✅ Crear membership (o evitar duplicado)
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

            // ✅ Marcar como aceptada
            $invitation->update([
                'accepted_at' => now(),
            ]);

            // ✅ Cambiar workspace actual
            session([
                'current_workspace_id' => $invitation->workspace_id,
            ]);

            app(RecordActivity::class)->handle(
                workspace: $invitation->workspace,
                user: $user,
                action: 'member.joined',
                description: "{$user->name} joined the workspace",
                subject: $invitation->workspace
            );
        });
    }
}
