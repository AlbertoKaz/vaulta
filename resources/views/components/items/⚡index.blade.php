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

    public ?int $editingId = null;
    public string $editingName = '';
    public ?string $editingDescription = null;
    public ?string $editingCondition = null;
    public ?string $editingLocation = null;
    public ?string $editingNotes = null;

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

    public function edit(int $id): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $item = Item::where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->where('id', $id)
            ->first();

        if (! $item) {
            return;
        }

        $this->editingId = $item->id;
        $this->editingName = $item->name;
        $this->editingDescription = $item->description;
        $this->editingCondition = $item->condition;
        $this->editingLocation = $item->location;
        $this->editingNotes = $item->notes;
    }

    public function cancelEdit(): void
    {
        $this->reset([
            'editingId',
            'editingName',
            'editingDescription',
            'editingCondition',
            'editingLocation',
            'editingNotes',
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'editingName' => 'required|string|max:255',
            'editingDescription' => 'nullable|string',
            'editingCondition' => 'nullable|string|max:255',
            'editingLocation' => 'nullable|string|max:255',
            'editingNotes' => 'nullable|string',
        ]);

        $workspace = current_workspace();

        if (! $workspace || ! $this->editingId) {
            return;
        }

        $item = Item::where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->where('id', $this->editingId)
            ->first();

        if (! $item) {
            return;
        }

        $item->update([
            'name' => $this->editingName,
            'description' => $this->editingDescription,
            'condition' => $this->editingCondition,
            'location' => $this->editingLocation,
            'notes' => $this->editingNotes,
        ]);

        $this->cancelEdit();
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
            <div class="border rounded p-4 space-y-3">
                @if ($editingId === $item->id)
                    <div class="space-y-3">
                        <input
                            type="text"
                            wire:model="editingName"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Item name"
                        >
                        @error('editingName') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

                        <textarea
                            wire:model="editingDescription"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Description"
                        ></textarea>

                        <input
                            type="text"
                            wire:model="editingCondition"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Condition"
                        >

                        <input
                            type="text"
                            wire:model="editingLocation"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Location"
                        >

                        <textarea
                            wire:model="editingNotes"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Notes"
                        ></textarea>

                        <div class="flex gap-3">
                            <button
                                wire:click="update"
                                class="text-sm text-green-600"
                            >
                                Save
                            </button>

                            <button
                                wire:click="cancelEdit"
                                class="text-sm text-gray-500"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex justify-between gap-4">
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

                        <div class="flex gap-3">
                            <button
                                wire:click="edit({{ $item->id }})"
                                class="text-sm text-blue-500"
                            >
                                Edit
                            </button>

                            <button
                                wire:click="delete({{ $item->id }})"
                                class="text-sm text-red-500"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">No items yet.</p>
        @endforelse
    </div>
</div>
