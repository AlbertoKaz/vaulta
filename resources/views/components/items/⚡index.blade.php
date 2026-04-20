<?php

use App\Models\Collection;
use App\Models\Item;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public Collection $collection;

    public string $name = '';
    public ?string $description = null;
    public ?string $condition = null;
    public ?string $location = null;
    public ?string $notes = null;

    public function mount(Collection $collection): void
    {
        $workspace = current_workspace();

        abort_unless($workspace, 403);

        abort_unless(
            $collection->workspace_id === $workspace->id,
            404
        );

        $this->collection = $collection;
    }

    public function create(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $workspace = current_workspace();

        if (!$workspace) {
            return;
        }

        $baseSlug = Str::slug($this->name);
        $slug = $baseSlug;
        $counter = 2;

        while (
        Item::where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "$baseSlug-$counter";
            $counter++;
        }

        Item::create([
            'workspace_id' => $workspace->id,
            'collection_id' => $this->collection->id,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description,
            'condition' => $this->condition,
            'location' => $this->location,
            'notes' => $this->notes,
            'status' => 'active',
        ]);

        $this->reset([
            'name',
            'description',
            'condition',
            'location',
            'notes',
        ]);
    }

    public function delete(int $id): void
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return;
        }

        $item = Item::where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->where('id', $id)
            ->first();

        if (!$item) {
            return;
        }

        $item->delete();
    }

    public function getItemsProperty()
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return collect();
        }

        return Item::where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->latest()
            ->get();
    }

};
?>

<div class="max-w-3xl mx-auto p-4 space-y-6">
    <div>
        <h1 class="text-2xl font-bold">
            Items — {{ $collection->name }}
        </h1>
        <p class="text-sm text-gray-500">
            Workspace: {{ current_workspace()?->name }}
        </p>
    </div>

    <form wire:submit="create" class="space-y-3">
        <input
            type="text"
            wire:model="name"
            placeholder="Item name"
            class="w-full border rounded px-3 py-2"
        >
        @error('name') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

        <textarea
            wire:model="description"
            placeholder="Description"
            class="w-full border rounded px-3 py-2"
        ></textarea>

        <input
            type="text"
            wire:model="condition"
            placeholder="Condition"
            class="w-full border rounded px-3 py-2"
        >

        <input
            type="text"
            wire:model="location"
            placeholder="Location"
            class="w-full border rounded px-3 py-2"
        >

        <textarea
            wire:model="notes"
            placeholder="Notes"
            class="w-full border rounded px-3 py-2"
        ></textarea>

        <button
            type="submit"
            class="px-4 py-2 rounded bg-black text-white"
        >
            Create item
        </button>
    </form>

    <div class="space-y-3">
        @forelse ($this->items as $item)
            <div class="border rounded p-4 flex justify-between gap-4">
                <div>
                    <p class="font-semibold">{{ $item->name }}</p>
                    <p class="text-sm text-gray-500">{{ $item->slug }}</p>

                    @if ($item->description)
                        <p class="text-sm mt-2">{{ $item->description }}</p>
                    @endif

                    @if ($item->condition)
                        <p class="text-sm mt-1">Condition: {{ $item->condition }}</p>
                    @endif

                    @if ($item->location)
                        <p class="text-sm mt-1">Location: {{ $item->location }}</p>
                    @endif
                </div>

                <button
                    wire:click="delete({{ $item->id }})"
                    class="text-sm text-red-500"
                >
                    Delete
                </button>
            </div>
        @empty
            <p class="text-sm text-gray-500">No items yet.</p>
        @endforelse
    </div>
</div>
