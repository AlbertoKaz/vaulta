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

<div class="max-w-2xl mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">
        Collections — {{ current_workspace()?->name ?? 'No workspace' }}
    </h1>

    <form wire:submit="create" class="mb-6 space-y-2">
        <input
            type="text"
            wire:model="name"
            placeholder="Collection name"
            class="w-full border rounded px-3 py-2"
        >

        @error('name')
        <p class="text-sm text-red-500">{{ $message }}</p>
        @enderror

        <button
            type="submit"
            class="px-4 py-2 rounded bg-black text-white"
        >
            Create collection
        </button>
    </form>

    <div class="space-y-2">
        @forelse ($this->collections as $collection)
            @if ($editingId === $collection->id)
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model="editingName"
                        class="border px-2 py-1 rounded w-full"
                    >

                    <button wire:click="update" class="text-green-500 text-sm">
                        Save
                    </button>
                </div>
            @else
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium">{{ $collection->name }}</p>
                        <p class="text-sm text-gray-500">{{ $collection->slug }}</p>
                    </div>

                    <div class="flex gap-2">
                        <button wire:click="edit({{ $collection->id }})" class="text-blue-500 text-sm">
                            Edit
                        </button>

                        <button wire:click="delete({{ $collection->id }})" class="text-red-500 text-sm">
                            Delete
                        </button>

                        <a
                            href="{{ route('items.index', $collection) }}"
                            class="text-sm text-blue-500"
                        >
                            View items
                        </a>
                    </div>
                </div>
            @endif
        @empty
            <p class="text-sm text-gray-500">No collections yet.</p>
        @endforelse
    </div>
</div>
