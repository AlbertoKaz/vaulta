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

    public function cancelInvitation(int $invitationId): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        Gate::authorize('inviteMembers', $workspace);

        $invitation = $workspace->invitations()
            ->where('id', $invitationId)
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation) {
            return;
        }

        $invitation->delete();

        session()->flash('success', 'Invitation cancelled successfully.');
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

<div class="mx-auto max-w-4xl px-4 py-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
            Members
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ current_workspace()?->name ?? 'No workspace' }} · Invite collaborators and manage workspace access.
        </p>
    </div>

    @if (session()->has('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Invite member</h2>

        <form wire:submit="invite" class="mt-4 grid gap-4 md:grid-cols-[1fr_180px_auto] md:items-end">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="h-9 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    placeholder="name@example.com"
                >
                @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role</label>
                <select
                    id="role"
                    wire:model="role"
                    class="h-9 w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
                @error('role') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                class="h-9 rounded-md border border-zinc-300 px-5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-700"
            >
                Send invitation
            </button>
        </form>
    </div>

    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Pending invitations</h2>

        <div class="mt-4 space-y-2">
            @forelse ($this->pendingInvitations as $invitation)
                <div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200/60 px-4 py-3 dark:border-zinc-800">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $invitation->email }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ ucfirst($invitation->role->value) }} · Expires {{ optional($invitation->expires_at)?->format('Y-m-d H:i') ?? 'No expiry' }}
                        </p>
                    </div>

                    @can('inviteMembers', current_workspace())
                        <button
                            type="button"
                            wire:click="cancelInvitation({{ $invitation->id }})"
                            wire:confirm="Are you sure you want to cancel this invitation?"
                            class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-50 dark:hover:bg-red-950/30"
                        >
                            Cancel
                        </button>
                    @endcan
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    No pending invitations.
                </p>
            @endforelse
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Members</h2>

        <div class="mt-4 space-y-2">
            @forelse ($this->members as $member)
                <div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200/60 px-4 py-3 dark:border-zinc-800">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $member->user->name }}
                            </p>

                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                                {{ ucfirst($member->role->value) }}
                            </span>
                        </div>

                        <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $member->user->email }}
                        </p>
                    </div>

                    @can('updateMember', current_workspace())
                        @if ($member->role->value !== 'owner')
                            <div class="flex shrink-0 items-center gap-2">
                                <select
                                    wire:change="changeRole({{ $member->id }}, $event.target.value)"
                                    class="h-9 rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                >
                                    <option value="member" @selected($member->role->value === 'member')>Member</option>
                                    <option value="admin" @selected($member->role->value === 'admin')>Admin</option>
                                </select>

                                <button
                                    type="button"
                                    wire:click="removeMember({{ $member->id }})"
                                    wire:confirm="Are you sure you want to remove this member?"
                                    class="rounded-lg border border-red-500/30 px-3 py-1.5 text-sm font-medium text-red-400 transition hover:border-red-500/60 hover:bg-red-500/10 hover:text-red-300"
                                >
                                    Remove
                                </button>
                            </div>
                        @endif
                    @endcan
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    No members found.
                </p>
            @endforelse
        </div>
    </div>
</div>
