<?php

use App\Actions\Workspaces\InviteMemberToWorkspace;
use App\Enums\WorkspaceRole;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|in:admin,member')]
    public string $role = 'member';

    public function invite(InviteMemberToWorkspace $inviteMemberToWorkspace): void
    {
        $workspace = current_workspace();
        $user = auth()->user();

        if (!$workspace || !$user) {
            $this->addError('email', 'No workspace available.');
            return;
        }

        Gate::authorize('inviteMembers', $workspace);

        $this->validate();

        try {
            $inviteMemberToWorkspace->handle(
                workspace: $workspace,
                inviter: $user,
                email: $this->email,
                role: WorkspaceRole::from($this->role),
            );

            $this->reset('email');
            $this->role = 'member';

            session()->flash('success', 'Invitation created successfully.');
        } catch (DomainException $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function getPendingInvitationsProperty()
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return collect();
        }

        return $workspace->invitations()
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();
    }

    public function getMembersProperty()
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return collect();
        }

        return $workspace->members()
            ->with('user')
            ->orderByRaw("
                case role
                    when 'owner' then 1
                    when 'admin' then 2
                    when 'member' then 3
                    else 4
                end
            ")
            ->get();
    }

    public function changeRole(int $memberId, string $role): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        Gate::authorize('updateMember', $workspace);

        if (! in_array($role, [
            WorkspaceRole::ADMIN->value,
            WorkspaceRole::MEMBER->value,
        ], true)) {
            return;
        }

        $member = $workspace->members()
            ->where('id', $memberId)
            ->first();

        if (! $member) {
            return;
        }

        if ($member->role === WorkspaceRole::OWNER) {
            return;
        }

        $member->update([
            'role' => $role,
        ]);

        session()->flash('success', 'Member role updated successfully.');
    }

    public function removeMember(int $memberId): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        Gate::authorize('removeMember', $workspace);

        $member = $workspace->members()
            ->where('id', $memberId)
            ->first();

        if (! $member) {
            return;
        }

        if ($member->role === WorkspaceRole::OWNER) {
            return;
        }

        $member->delete();

        session()->flash('success', 'Member removed successfully.');
    }
};
?>

<div class="mx-auto max-w-4xl px-4 py-6 space-y-8">
    <div>
        <h1 class="text-2xl font-bold">
            Members — {{ current_workspace()?->name ?? 'No workspace' }}
        </h1>
        <p class="text-sm text-gray-500">
            Invite collaborators and manage workspace access.
        </p>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border p-5 space-y-4">
        <h2 class="text-lg font-semibold">Invite member</h2>

        <form wire:submit="invite" class="space-y-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="w-full rounded-lg border px-3 py-2"
                    placeholder="name@example.com"
                >
                @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-medium">Role</label>
                <select
                    id="role"
                    wire:model="role"
                    class="w-full rounded-lg border px-3 py-2"
                >
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
                @error('role')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="rounded-lg bg-black px-4 py-2 text-sm font-medium text-white"
            >
                Send invitation
            </button>
        </form>
    </div>

    <div class="rounded-xl border p-5 space-y-4">
        <h2 class="text-lg font-semibold">Pending invitations</h2>

        <div class="space-y-3">
            @forelse ($this->pendingInvitations as $invitation)
                <div class="rounded-lg border px-4 py-3">
                    <div class="font-medium">{{ $invitation->email }}</div>
                    <div class="text-sm text-gray-500">
                        Role: {{ $invitation->role->value }}
                    </div>
                    <div class="text-xs text-gray-400">
                        Expires: {{ optional($invitation->expires_at)?->format('Y-m-d H:i') ?? 'No expiry' }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No pending invitations.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border p-5 space-y-4">
        <h2 class="text-lg font-semibold">Members</h2>

        <div class="space-y-3">
            @forelse ($this->members as $member)
                <div class="rounded-lg border px-4 py-3 flex items-center justify-between gap-4">
                    <div>
                        <div class="font-medium">{{ $member->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $member->user->email }}</div>
                        <div class="text-xs text-gray-400 uppercase">{{ $member->role->value }}</div>
                    </div>

                    @can('updateMember', current_workspace())
                        @if ($member->role->value !== 'owner')
                            <div class="flex items-center gap-2">
                                <select
                                    wire:change="changeRole({{ $member->id }}, $event.target.value)"
                                    class="rounded-lg border px-3 py-2 text-sm"
                                >
                                    <option value="member" @selected($member->role->value === 'member')>Member</option>
                                    <option value="admin" @selected($member->role->value === 'admin')>Admin</option>
                                </select>

                                <button
                                    type="button"
                                    wire:click="removeMember({{ $member->id }})"
                                    wire:confirm="Are you sure you want to remove this member?"
                                    class="rounded-lg border px-3 py-2 text-sm"
                                >
                                    Remove
                                </button>
                            </div>
                        @endif
                    @endcan
                </div>
            @empty
                <p class="text-sm text-gray-500">No members found.</p>
            @endforelse
        </div>
    </div>
</div>
