<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function view(User $user, Item $item): bool
    {
        return $item->workspace->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return false;
        }

        $membership = $workspace->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return false;
        }

        return in_array($membership->role->value, [
            WorkspaceRole::OWNER->value,
            WorkspaceRole::ADMIN->value,
            WorkspaceRole::MEMBER->value,
        ], true);
    }

    public function update(User $user, Item $item): bool
    {
        $membership = $item->workspace->members()
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

    public function delete(User $user, Item $item): bool
    {
        $membership = $item->workspace->members()
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
}
