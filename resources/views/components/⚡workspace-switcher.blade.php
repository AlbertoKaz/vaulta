<?php

use Livewire\Component;

new class extends Component {

    public ?int $workspaceId = null;

    public function mount(): void
    {
        $this->workspaceId = current_workspace()?->id;
    }

    public function getWorkspacesProperty()
    {
        return auth()->user()
            ->workspaces()
            ->orderBy('name')
            ->get();
    }

    public function updatedWorkspaceId($value): void
    {
        $workspace = auth()->user()
            ->workspaces()
            ->where('workspaces.id', $value)
            ->first();

        if (! $workspace) {
            return;
        }

        session(['current_workspace_id' => $workspace->id]);

        $this->redirect(route('dashboard'), navigate: true);
    }
};

?>

@if($this->workspaces->isNotEmpty())
    <div>
        <label for="workspace-switcher" class="mb-1 block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            Workspace
        </label>

        <select
            id="workspace-switcher"
            wire:model.live="workspaceId"
            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
        >
            @foreach($this->workspaces as $workspace)
                <option value="{{ $workspace->id }}">
                    {{ $workspace->name }}
                </option>
            @endforeach
        </select>
    </div>
@endif
