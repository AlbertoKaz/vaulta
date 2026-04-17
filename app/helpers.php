<?php

use App\Models\Workspace;

function current_workspace(): ?Workspace
{
    $user = auth()->user();

    if (! $user) {
        return null;
    }

    return $user->currentWorkspace();
}
