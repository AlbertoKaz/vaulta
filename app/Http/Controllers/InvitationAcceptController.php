<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\AcceptInvitation;
use App\Models\Invitation;
use Illuminate\Http\Request;

class InvitationAcceptController extends Controller
{
    public function __invoke(Request $request, string $token, AcceptInvitation $acceptInvitation)
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->firstOrFail();

        try {
            $acceptInvitation->handle($invitation, $request->user());
        } catch (\DomainException $e) {
            return redirect()
                ->route('members.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Invitación aceptada correctamente.');
    }
}
