<?php

use App\Models\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $name = '';
    public ?int $editingId = null;
    public string $editingName = '';

    public function create(): void
    {
        Gate::authorize('create', Collection::class);

        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $baseSlug = Str::slug($this->name);
        $slug = $baseSlug;
        $counter = 2;

        while (
        Collection::where('workspace_id', $workspace->id)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        Collection::create([
            'workspace_id' => $workspace->id,
            'name' => $this->name,
            'slug' => $slug,
        ]);

        $this->reset('name');
    }

    public function edit(int $id): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $collection = $workspace
            ->collections()
            ->where('id', $id)
            ->first();

        if (! $collection) {
            return;
        }

        Gate::authorize('update', $collection);

        $this->editingId = $collection->id;
        $this->editingName = $collection->name;
    }

    public function update(): void
    {
        $this->validate([
            'editingName' => 'required|string|max:255',
        ]);

        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $collection = $workspace
            ->collections()
            ->where('id', $this->editingId)
            ->first();

        if (! $collection) {
            return;
        }

        Gate::authorize('update', $collection);

        $collection->update([
            'name' => $this->editingName,
        ]);

        $this->reset(['editingId', 'editingName']);
    }

    public function delete(int $id): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $collection = $workspace
            ->collections()
            ->where('id', $id)
            ->first();

        if (! $collection) {
            return;
        }

        Gate::authorize('delete', $collection);

        $collection->delete();
    }

    public function getCollectionsProperty()
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return collect();
        }

        return $workspace
            ->collections()
            ->latest()
            ->get();
    }
};
?>

<div class="max-w-4xl mx-auto p-4 space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
            Collections
        </h1>

        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ current_workspace()?->name ?? 'No workspace' }}
        </p>
    </div>

    {{-- Create Form --}}
    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
        <form wire:submit="create" class="space-y-3">
            <input
                type="text"
                wire:model="name"
                placeholder="Collection name"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900"
            >

            @error('name')
            <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-black dark:hover:bg-zinc-300"
            >
                Create collection
            </button>
        </form>
    </div>
    {{-- End Create Form --}}

    {{-- Loop --}}
    <div class="space-y-3">
        @forelse ($this->collections as $collection)
            @if ($editingId === $collection->id)

                {{-- Edit mode --}}
                <div class="rounded-xl border border-zinc-200/60 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950/60">
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model="editingName"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                        >

                        <button wire:click="update" class="text-sm font-medium text-green-500">
                            Save
                        </button>
                    </div>
                </div>

            @else

                {{-- Normal mode --}}
                <div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200/60 bg-white px-4 py-3 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950/60 dark:hover:bg-zinc-900/60">

                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $collection->name }}
                        </p>

                        <p class="text-xs text-zinc-500">
                            {{ $collection->slug }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 text-xs">
                        <a
                            href="{{ route('items.index', $collection) }}"
                            class="font-medium text-blue-500 hover:underline"
                        >
                            View
                        </a>

                        <button
                            wire:click="edit({{ $collection->id }})"
                            class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                        >
                            Edit
                        </button>

                        <button
                            wire:click="delete({{ $collection->id }})"
                            class="text-red-500 hover:underline"
                        >
                            Delete
                        </button>

                        <a
                            href="{{ route('exports.items', ['collection_id' => $collection->id]) }}"
                            class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800"
                        >
                            Export
                        </a>
                    </div>
                </div>

            @endif
        @empty
            <p class="text-sm text-zinc-500">
                No collections yet.
            </p>
        @endforelse
    </div>
</div>
