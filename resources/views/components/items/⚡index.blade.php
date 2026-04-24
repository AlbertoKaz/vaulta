<?php

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Models\Collection;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public Collection $collection;

    public string $name = '';
    public ?string $description = null;
    public string $status = 'active';
    public ?string $condition = null;
    public ?string $location = null;
    public ?string $notes = null;
    public ?string $purchase_price = null;
    public ?string $estimated_value = null;

    public string $tagName = '';
    public ?int $managingTagsFor = null;

    public ?string $filterStatus = null;
    public ?string $filterCondition = null;

    public ?int $editingId = null;
    public string $editingName = '';
    public ?string $editingDescription = null;
    public ?string $editingStatus = null;
    public ?string $editingCondition = null;
    public ?string $editingLocation = null;
    public ?string $editingNotes = null;
    public ?string $editingPurchasePrice = null;
    public ?string $editingEstimatedValue = null;

    public function mount(Collection $collection): void
    {
        $workspace = current_workspace();

        abort_unless($workspace, 403);

        abort_unless(
            $collection->workspace_id === $workspace->id,
            404
        );

        Gate::authorize('view', $collection);

        $this->collection = $collection;
    }

    public function create(): void
    {
        Gate::authorize('create', Item::class);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'estimated_value' => 'nullable|numeric|min:0',
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
            'status' => $this->status,
            'condition' => $this->condition,
            'purchase_price' => $this->purchase_price,
            'estimated_value' => $this->estimated_value,
            'location' => $this->location,
            'notes' => $this->notes,
        ]);

        $this->reset([
            'name',
            'description',
            'condition',
            'purchase_price',
            'estimated_value',
            'location',
            'notes',
        ]);

        $this->status = ItemStatus::ACTIVE->value;

    }

    public function edit(int $id): void
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return;
        }

        $item = $this->collection->items()
            ->where('id', $id)
            ->first();

        if (!$item) {
            return;
        }

        Gate::authorize('update', $item);

        $this->editingId = $item->id;
        $this->editingName = $item->name;
        $this->editingDescription = $item->description;
        $this->editingStatus = $item->status?->value;
        $this->editingCondition = $item->condition?->value;
        $this->editingPurchasePrice = $item->purchase_price;
        $this->editingEstimatedValue = $item->estimated_value;
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
            'editingStatus',
            'editingPurchasePrice',
            'editingEstimatedValue',
            'editingLocation',
            'editingNotes',
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'editingName' => 'required|string|max:255',
            'editingDescription' => 'nullable|string',
            'editingCondition' => 'required|string|max:255',
            'editingStatus' => 'required|string|max:255',
            'editingLocation' => 'nullable|string|max:255',
            'editingPurchasePrice' => 'nullable|numeric|min:0',
            'editingEstimatedValue' => 'nullable|numeric|min:0',
            'editingNotes' => 'nullable|string',
        ]);

        $workspace = current_workspace();

        if (!$workspace || !$this->editingId) {
            return;
        }

        $item = Item::where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->where('id', $this->editingId)
            ->first();

        if (!$item) {
            return;
        }

        Gate::authorize('update', $item);

        $item->update([
            'name' => $this->editingName,
            'description' => $this->editingDescription,
            'condition' => $this->editingCondition,
            'status' => $this->editingStatus,
            'purchase_price' => $this->editingPurchasePrice,
            'estimated_value' => $this->editingEstimatedValue,
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

        $item = $this->collection->items()
            ->where('id', $id)
            ->first();

        if (!$item) {
            return;
        }

        Gate::authorize('delete', $item);

        $item->delete();
    }

    public function createTag(): void
    {
        $this->validate([
            'tagName' => 'required|string|max:20',
        ]);

        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $name = trim($this->tagName);
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
        Tag::where('workspace_id', $workspace->id)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        Tag::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'slug' => $slug,
        ]);

        $this->reset('tagName');

        session()->flash('success', 'Tag created successfully.');
    }

    public function attachTag(int $itemId, int $tagId): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $item = $this->collection->items()
            ->where('id', $itemId)
            ->first();

        if (! $item) {
            return;
        }

        $tag = Tag::where('workspace_id', $workspace->id)
            ->where('id', $tagId)
            ->first();

        if (! $tag) {
            return;
        }

        $item->tags()->syncWithoutDetaching([$tag->id]);
    }

    public function detachTag(int $itemId, int $tagId): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $item = $this->collection->items()
            ->where('id', $itemId)
            ->first();

        if (! $item) {
            return;
        }

        $item->tags()->detach($tagId);
    }

    public function deleteTag(int $tagId): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $tag = $workspace->tags()->where('id', $tagId)->first();

        if (! $tag) {
            return;
        }

        // elimina también relaciones pivot automáticamente por cascade
        $tag->delete();
    }

    public function toggleTagManager(int $itemId): void
    {
        $this->managingTagsFor = $this->managingTagsFor === $itemId
            ? null
            : $itemId;
    }

    public function getTagsProperty()
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return collect();
        }

        return $workspace->tags()
            ->orderBy('name')
            ->get();
    }

    // CSV Export
    public function getExportUrlProperty(): string
    {
        return route('exports.items', array_filter([
            'collection_id' => $this->collection->id,
            'status' => $this->filterStatus,
            'condition' => $this->filterCondition,
        ]));
    }

    // Salida info
    public function getItemsProperty()
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return collect();
        }

        $query = $this->collection->items()->with('tags')
            ->where('workspace_id', current_workspace()?->id);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCondition) {
            $query->where('condition', $this->filterCondition);
        }

        return $query->latest()->get();
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

        <select wire:model="status" class="w-full border rounded px-3 py-2">
            @foreach (\App\Enums\ItemStatus::cases() as $status)
                <option value="{{ $status->value }}">
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>

        <select wire:model="condition" class="w-full border rounded px-3 py-2">
            <option value="">-- Condition --</option>

            @foreach (\App\Enums\ItemCondition::cases() as $condition)
                <option value="{{ $condition->value }}">
                    {{ $condition->label() }}
                </option>
            @endforeach
        </select>

        <input
            type="number"
            step="0.01"
            min="0"
            wire:model="purchase_price"
            placeholder="Purchase price"
            class="w-full border rounded px-3 py-2"
        >
        @error('purchase_price') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

        <input
            type="number"
            step="0.01"
            min="0"
            wire:model="estimated_value"
            placeholder="Estimated value"
            class="w-full border rounded px-3 py-2"
        >
        @error('estimated_value') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

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

    <div class="border rounded-xl p-4 space-y-4">
        <div>
            <h2 class="text-lg font-semibold">Tags</h2>
            <p class="text-sm text-gray-500">
                Create reusable tags for this workspace.
            </p>
        </div>

        <form wire:submit="createTag" class="flex gap-2">
            <input
                type="text"
                wire:model="tagName"
                placeholder="Tag name"
                class="w-full rounded-lg border px-3 py-2 text-sm"
            >

            <button
                type="submit"
                class="rounded-lg border px-4 py-2 text-sm font-medium"
            >
                Add tag
            </button>
        </form>


        @error('tagName')
        <p class="text-sm text-red-500">{{ $message }}</p>
        @enderror

        <div class="flex flex-wrap gap-2">
            @forelse ($this->tags as $tag)
                <button
                    wire:click="deleteTag({{ $tag->id }})"
                    class="rounded-full border px-3 py-1 text-xs text-gray-400 hover:bg-zinc-800"
                >
                    {{ $tag->name }} ×
                </button>
            @empty
                <p class="text-sm text-gray-500">No tags yet.</p>
            @endforelse
        </div>
    </div>

    <div class="space-y-3">
        <div class="flex gap-3">
            <a
                href="{{ $this->exportUrl }}"
                class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800"
            >
                Export filtered CSV
            </a>

            <select wire:model.change.live="filterStatus" class="border rounded px-3 py-2">
                <option value="">All status</option>

                @foreach (\App\Enums\ItemStatus::cases() as $status)
                    <option value="{{ $status->value }}">
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>

            <select wire:model.change.live="filterCondition" class="border rounded px-3 py-2">
                <option value="">All conditions</option>

                @foreach (\App\Enums\ItemCondition::cases() as $condition)
                    <option value="{{ $condition->value }}">
                        {{ $condition->label() }}
                    </option>
                @endforeach
            </select>

            <button
                type="button"
                wire:click="$set('filterStatus', ''); $set('filterCondition', '')"
                class="text-sm text-gray-500"
            >
                Reset
            </button>
        </div>

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

                        <select wire:model="editingCondition" class="w-full border rounded px-3 py-2">
                            <option value="">-- Condition --</option>

                            @foreach (\App\Enums\ItemCondition::cases() as $condition)
                                <option value="{{ $condition->value }}">
                                    {{ $condition->label() }}
                                </option>
                            @endforeach
                        </select>

                        <select wire:model="editingStatus" class="w-full border rounded px-3 py-2">
                            @foreach (\App\Enums\ItemStatus::cases() as $status)
                                <option value="{{ $status->value }}">
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="editingPurchasePrice"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Purchase price"
                        >

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="editingEstimatedValue"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Estimated value"
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
                                <p class="text-sm mt-1">
                                    Condition: {{ $item->condition->label() }}
                                </p>
                            @endif

                            @if ($item->status)
                                <p class="text-sm mt-1">
                                    Status: {{ $item->status->label() }}
                                </p>
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

                <div class="mt-3 space-y-2">
                        {{-- Assigned tags --}}
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($item->tags as $tag)
                                <button
                                    type="button"
                                    wire:click="detachTag({{ $item->id }}, {{ $tag->id }})"
                                    class="rounded-full border border-zinc-600 px-2 py-1 text-xs text-zinc-300 hover:bg-zinc-800 cursor-pointer"
                                >
                                    {{ $tag->name }} ×
                                </button>
                            @endforeach

                            <button
                                type="button"
                                wire:click="toggleTagManager({{ $item->id }})"
                                class="text-xs text-blue-400 hover:underline"
                            >
                                {{ $item->tags->isNotEmpty() ? '+ Add tag' : 'Manage tags' }}
                            </button>
                        </div>

                        {{-- Available tags --}}
                        @if ($managingTagsFor === $item->id && $this->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->tags as $tag)
                                    @unless ($item->tags->contains('id', $tag->id))
                                        <button
                                            type="button"
                                            wire:click="attachTag({{ $item->id }}, {{ $tag->id }})"
                                            class="rounded-full border border-zinc-700 px-2 py-1 text-xs text-zinc-500 hover:bg-zinc-800 hover:text-zinc-200"
                                        >
                                            + {{ $tag->name }}
                                        </button>
                                    @endunless
                                @endforeach
                            </div>
                        @endif
                    </div>
            </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">No items yet.</p>
        @endforelse
    </div>

