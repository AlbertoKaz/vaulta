<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function inviteMembers(User $user, Workspace $workspace): bool
    {
        $membership = $workspace->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return false;
        }

        return in_array($membership->role->value, [
            WorkspaceRole::OWNER->value,
            WorkspaceRole::ADMIN->value,
        ], true);
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        $membership = $workspace->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return false;
        }

        return $membership->role === WorkspaceRole::OWNER;
    }

    public function updateMember(User $user, Workspace $workspace): bool
    {
        $membership = $workspace->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return false;
        }

        return $membership->role === \App\Enums\WorkspaceRole::OWNER;
    }

    public function removeMember(User $user, Workspace $workspace): bool
    {
        $membership = $workspace->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return false;
        }

        return $membership->role === \App\Enums\WorkspaceRole::OWNER;
    }
}
