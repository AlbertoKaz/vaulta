<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $currentWorkspaceId = session('current_workspace_id');

        if ($currentWorkspaceId) {
            $belongsToUser = $user->workspaces()
                ->where('workspaces.id', $currentWorkspaceId)
                ->exists();

            if ($belongsToUser) {
                return $next($request);
            }
        }

        $firstWorkspace = $user->workspaces()->first();

        if ($firstWorkspace) {
            session(['current_workspace_id' => $firstWorkspace->id]);
        } else {
            session()->forget('current_workspace_id');
        }

        return $next($request);
    }
}
