<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchWorkspaceController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $workspaceId = (int) $request->input('workspace_id');

        if (! $workspaceId) {
            return back()->with('error', 'No se recibió ningún workspace.');
        }

        $workspace = $request->user()
            ->workspaces()
            ->where('workspaces.id', $workspaceId)
            ->first();

        if (! $workspace) {
            return back()->with('error', 'Workspace no válido.');
        }

        session()->put('current_workspace_id', $workspace->id);

        return redirect()->route('dashboard');
    }
}
