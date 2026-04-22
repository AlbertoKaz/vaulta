<?php

use App\Models\Workspace;

function current_workspace(): ?Workspace
{
    $user = auth()->user();

    return $user?->currentWorkspace();

}


if (!function_exists('current_workspace')) {
    function current_workspace(): ?Workspace
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $workspaceId = session('current_workspace_id');

        if ($workspaceId) {
            $workspace = $user->workspaces()
                ->where('workspaces.id', $workspaceId)
                ->first();

            if ($workspace) {
                return $workspace;
            }
        }

        $fallbackWorkspace = $user->workspaces()->first();

        if ($fallbackWorkspace) {
            session(['current_workspace_id' => $fallbackWorkspace->id]);
        }

        return $fallbackWorkspace;
    }
}
