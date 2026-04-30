<?php

namespace App\Actions\Workspaces;

use App\Actions\Activity\RecordActivity;
use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitationNotification;
use DomainException;
use Illuminate\Support\Facades\Notification;
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
            throw new DomainException('Ese usuario ya pertenece al workspace.');
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
            throw new DomainException('Ya existe una invitación pendiente para ese email.');
        }

        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $email)
            ->notify(new WorkspaceInvitationNotification($invitation));

        app(RecordActivity::class)->handle(
            workspace: $workspace,
            user: $inviter,
            action: 'member.invited',
            description: "Invited {$email} to workspace",
            subject: $workspace
        );

        return $invitation;
    }
}
