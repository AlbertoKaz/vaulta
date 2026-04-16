<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class SetCurrentWorkspaceOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! session()->has('current_workspace_id')) {
            $workspace = $user->workspaces()->first();

            if ($workspace) {
                session(['current_workspace_id' => $workspace->id]);
            }
        }
    }
}
