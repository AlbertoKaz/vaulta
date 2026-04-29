<?php

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Models\Collection;
use App\Models\Item;
use App\Models\ItemImage;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component {

    use WithFileUploads;

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
    public array $filterTagIds = [];

    public ?TemporaryUploadedFile $image = null;

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




    public function uploadImage(int $itemId): void
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return;
        }

        $item = Item::query()
            ->where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return;
        }

        Gate::authorize('update', $item);

        $this->validate([
            'image' => 'required|image|max:2048',
        ]);

        $path = $this->image->store(
            "workspaces/{$workspace->id}/items/{$item->id}",
            'public'
        );

        $nextPosition = $item->images()->max('position') + 1;

        ItemImage::create([
            'item_id' => $item->id,
            'path' => $path,
            'position' => $nextPosition,
            'alt_text' => $item->name,
        ]);

        $this->reset('image');

        session()->flash('success', 'Image uploaded successfully.');
    }

    public function deleteImage(int $imageId): void
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return;
        }

        $image = ItemImage::query()
            ->where('id', $imageId)
            ->whereHas('item', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                    ->where('collection_id', $this->collection->id);
            })
            ->first();

        if (!$image) {
            return;
        }

        Gate::authorize('update', $image->item);

        Storage::disk('public')->delete($image->path);

        $image->delete();

        session()->flash('success', 'Image deleted successfully.');
    }

    public function setCoverImage(int $imageId): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $image = ItemImage::query()
            ->where('id', $imageId)
            ->whereHas('item', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                    ->where('collection_id', $this->collection->id);
            })
            ->first();

        if (! $image) {
            return;
        }

        Gate::authorize('update', $image->item);

        // mover a posición 1
        $image->item->images()
            ->where('id', '!=', $image->id)
            ->increment('position');

        $image->update(['position' => 1]);

        session()->flash('success', 'Cover image updated.');
    }

    public function reorderImages(int $itemId, array $imageIds): void
    {
        $workspace = current_workspace();

        if (! $workspace) {
            return;
        }

        $item = Item::query()
            ->where('workspace_id', $workspace->id)
            ->where('collection_id', $this->collection->id)
            ->where('id', $itemId)
            ->first();

        if (! $item) {
            return;
        }

        Gate::authorize('update', $item);

        $validImageIds = $item->images()
            ->whereIn('id', $imageIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validImageIds) !== count($imageIds)) {
            return;
        }

        DB::transaction(function () use ($imageIds) {
            foreach (array_values($imageIds) as $index => $imageId) {
                ItemImage::where('id', $imageId)->update([
                    'position' => $index + 1,
                ]);
            }
        });
    }




    public function createTag(): void
    {
        $this->validate([
            'tagName' => 'required|string|max:20',
        ]);

        $workspace = current_workspace();

        if (!$workspace) {
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

        if (!$workspace) {
            return;
        }

        $item = $this->collection->items()
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return;
        }

        $tag = Tag::where('workspace_id', $workspace->id)
            ->where('id', $tagId)
            ->first();

        if (!$tag) {
            return;
        }

        $item->tags()->syncWithoutDetaching([$tag->id]);
    }

    public function detachTag(int $itemId, int $tagId): void
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return;
        }

        $item = $this->collection->items()
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return;
        }

        $item->tags()->detach($tagId);
    }

    public function deleteTag(int $tagId): void
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return;
        }

        $tag = $workspace->tags()->where('id', $tagId)->first();

        if (!$tag) {
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

    public function toggleFilterTag(int $tagId): void
    {
        if (in_array($tagId, $this->filterTagIds, true)) {
            $this->filterTagIds = array_values(
                array_diff($this->filterTagIds, [$tagId])
            );

            return;
        }

        $this->filterTagIds[] = $tagId;
    }

    public function getTagsProperty()
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return collect();
        }

        return $workspace->tags()
            ->orderBy('name')
            ->get();
    }




    public function resetFilters(): void
    {
        $this->filterStatus = null;
        $this->filterCondition = null;
        $this->filterTagIds = [];
    }

    // CSV Export
    public function getExportUrlProperty(): string
    {
        $params = [
            'collection_id' => $this->collection->id,
        ];

        if ($this->filterStatus) {
            $params['status'] = $this->filterStatus;
        }

        if ($this->filterCondition) {
            $params['condition'] = $this->filterCondition;
        }

        if (!empty($this->filterTagIds)) {
            $params['tag_ids'] = $this->filterTagIds;
        }

        return route('exports.items', $params);
    }

    // Salida info
    public function getItemsProperty()
    {
        $workspace = current_workspace();

        if (!$workspace) {
            return collect();
        }

        $query = $this->collection
            ->items()
            ->with(['tags', 'images'])
            ->where('workspace_id', $workspace->id);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCondition) {
            $query->where('condition', $this->filterCondition);
        }

        if (!empty($this->filterTagIds)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('tags.id', $this->filterTagIds);
            });
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
        {{-- Filters --}}
        <div
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm space-y-5 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Filters
                    </h2>
                    <p class="text-xs text-zinc-500">
                        Narrow this collection by status, condition or workspace tags.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="text-xs font-medium text-zinc-500 transition hover:text-zinc-900 dark:hover:text-zinc-100"
                    >
                        Reset filters
                    </button>

                    <a
                        href="{{ $this->exportUrl }}"
                        class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-900"
                    >
                        Export CSV
                    </a>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-500">
                        Status
                    </label>

                    <select
                        wire:model.change.live="filterStatus"
                        class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 dark:border-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-600"
                    >
                        <option value="">All status</option>

                        @foreach (\App\Enums\ItemStatus::cases() as $status)
                            <option value="{{ $status->value }}">
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-500">
                        Condition
                    </label>

                    <select
                        wire:model.change.live="filterCondition"
                        class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 dark:border-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-600"
                    >
                        <option value="">All conditions</option>

                        @foreach (\App\Enums\ItemCondition::cases() as $condition)
                            <option value="{{ $condition->value }}">
                                {{ $condition->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <label class="text-xs font-medium text-zinc-500">
                        Tags
                    </label>

                    @if (!empty($filterTagIds))
                        <button
                            type="button"
                            wire:click="$set('filterTagIds', [])"
                            class="text-xs font-medium text-zinc-500 transition hover:text-zinc-900 dark:hover:text-zinc-100"
                        >
                            Clear tag
                        </button>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="$set('filterTagIds', [])"
                        class="rounded-full border px-3 py-1.5 text-xs font-medium transition duration-150
                    {{ empty($filterTagIds)
                        ? 'border-zinc-900 bg-zinc-900 text-white shadow-sm dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-950'
                        : 'border-zinc-200 text-zinc-500 hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                    >
                        All tags
                    </button>

                    @foreach ($this->tags as $tag)
                        <button
                            type="button"
                            wire:click="toggleFilterTag({{ $tag->id }})"
                            class="rounded-full border px-3 py-1.5 text-xs font-medium transition duration-150
                        {{ in_array($tag->id, $filterTagIds, true)
                            ? 'border-zinc-900 bg-zinc-900 text-white shadow-sm dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-950'
                            : 'border-zinc-200 text-zinc-500 hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100' }}"
                        >
                            {{ $tag->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($filterStatus || $filterCondition || $filterTagIds)
                <div class="flex flex-wrap items-center gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
            <span class="text-xs font-medium text-zinc-500">
                Active filters:
            </span>

                    @if ($filterStatus)
                        <button
                            wire:click="$set('filterStatus', null)"
                            class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            Status: {{ \App\Enums\ItemStatus::from($filterStatus)->label() }} ×
                        </button>
                    @endif

                    @if ($filterCondition)
                        <button
                            wire:click="$set('filterCondition', null)"
                            class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            Condition: {{ \App\Enums\ItemCondition::from($filterCondition)->label() }} ×
                        </button>
                    @endif

                    @foreach ($filterTagIds as $tagId)
                        <button
                            wire:click="toggleFilterTag({{ $tagId }})"
                            class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium"
                        >
                            Tag: {{ $this->tags->firstWhere('id', $tagId)?->name }} ×
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        {{-- End Filters --}}

        @forelse ($this->items as $item)
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950 transition hover:shadow-md hover:-translate-y-px">
                @if ($editingId === $item->id)
                    <div class="p-5 space-y-3">
                        {{-- aquí va todo tu formulario de edición tal cual --}}
                    </div>
                @else
                    <div class="grid gap-5 p-5 md:grid-cols-[180px_1fr]">
                        {{-- Cover --}}
                        <div class="space-y-3">
                            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900">
                                @if ($item->images->isNotEmpty())
                                    <img
                                        src="{{ asset('storage/' . $item->images->first()->path) }}"
                                        alt="{{ $item->name }}"
                                        class="h-40 w-full object-cover md:h-44"
                                    >
                                @else
                                    <div class="flex h-40 items-center justify-center text-xs text-zinc-400">
                                        No cover
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    type="file"
                                    wire:model="image"
                                    accept="image/*"
                                    class="block w-full text-xs text-zinc-500 file:mr-2 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-2.5 file:py-1.5 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-900 dark:file:text-zinc-300"
                                >

                                <button
                                    type="button"
                                    wire:click="uploadImage({{ $item->id }})"
                                    class="rounded-lg border border-zinc-200 px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-900"
                                >
                                    Upload
                                </button>
                            </div>

                            @error('image')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror

                            <div wire:loading wire:target="image" class="text-xs text-zinc-500">
                                Uploading image...
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="min-w-0 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $item->name }}
                                    </h3>

                                    <p class="text-sm text-zinc-500">
                                        {{ $item->slug }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    <button
                                        wire:click="edit({{ $item->id }})"
                                        class="text-xs font-semibold text-blue-500 hover:text-blue-600"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        wire:click="delete({{ $item->id }})"
                                        class="text-xs font-semibold text-red-500 hover:text-red-600"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>

                            @if ($item->description)
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $item->description }}
                                </p>
                            @endif

                            <div class="grid gap-2 sm:grid-cols-3">
                                @if ($item->condition)
                                    <div class="rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-900">
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                            Condition
                                        </p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item->condition->label() }}
                                        </p>
                                    </div>
                                @endif

                                @if ($item->status)
                                    <div class="rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-900">
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                            Status
                                        </p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item->status->label() }}
                                        </p>
                                    </div>
                                @endif

                                @if ($item->location)
                                    <div class="rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-900">
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                            Location
                                        </p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item->location }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Images --}}
                            @if ($item->images->isNotEmpty())
                                <div class="space-y-2">
                                    <p class="text-xs font-medium text-zinc-500">
                                        Images
                                    </p>

                                    <div class="flex flex-wrap gap-3">
                                        <div
                                            x-data="{
                                                draggedId: null,

                                                reorder(itemId) {
                                                    const ids = Array.from(this.$refs.gallery.querySelectorAll('[data-image-id]'))
                                                        .map((el) => Number(el.dataset.imageId));

                                                    $wire.reorderImages(itemId, ids);
                                                }
                                            }"
                                        >
                                            <div
                                                x-ref="gallery"
                                                class="grid grid-cols-3 gap-2 sm:grid-cols-4"
                                            >
                                                @foreach ($item->images as $image)
                                                    <div
                                                        wire:key="image-{{ $image->id }}"
                                                        data-image-id="{{ $image->id }}"
                                                        draggable="true"
                                                        x-on:dragstart="draggedId = {{ $image->id }}"
                                                        x-on:dragover.prevent
                                                        x-on:drop.prevent="
                                                            const dragged = $refs.gallery.querySelector(`[data-image-id='${draggedId}']`);
                                                            const target = $event.currentTarget;

                                                            if (! dragged || dragged === target) return;

                                                            const box = target.getBoundingClientRect();
                                                            const after = $event.clientX > box.left + box.width / 2;

                                                            target.parentNode.insertBefore(dragged, after ? target.nextSibling : target);

                                                            reorder({{ $item->id }});
                                                        "
                                                        class="relative h-24 w-24 cursor-grab overflow-hidden rounded-xl border border-zinc-200 active:cursor-grabbing dark:border-zinc-800"
                                                    >
                                                        <img
                                                            src="{{ asset('storage/' . $image->path) }}"
                                                            alt="{{ $image->alt_text ?? $item->name }}"
                                                            class="h-full w-full object-cover cursor-grab active:cursor-grabbing hover:scale-105 transition"
                                                        >

                                                        <button
                                                            type="button"
                                                            wire:click="deleteImage({{ $image->id }})"
                                                            wire:confirm="Delete this image?"
                                                            class="absolute right-1.5 top-1.5 rounded-full bg-black/70 px-2 py-0.5 text-xs font-medium text-white hover:bg-black"
                                                        >
                                                            ×
                                                        </button>

                                                        @if ($image->position === 1)
                                                            <span class="absolute left-1.5 top-1.5 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-medium text-black ring-2 ring-blue-500">
                                                                Cover
                                                            </span>
                                                        @else
                                                            <button
                                                                type="button"
                                                                wire:click="setCoverImage({{ $image->id }})"
                                                                class="absolute left-1.5 top-1.5 rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-medium text-black hover:bg-white"
                                                            >
                                                                Set cover
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Assigned tags --}}
                            <div class="flex flex-wrap items-center gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                @foreach ($item->tags as $tag)
                                    <button
                                        type="button"
                                        wire:click="detachTag({{ $item->id }}, {{ $tag->id }})"
                                        class="rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-900"
                                    >
                                        {{ $tag->name }} ×
                                    </button>
                                @endforeach

                                <button
                                    type="button"
                                    wire:click="toggleTagManager({{ $item->id }})"
                                    class="text-xs font-medium text-blue-500 hover:text-blue-600"
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
                                                class="rounded-full border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100"
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
</div>
