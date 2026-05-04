<?php

use App\Actions\Activity\RecordActivity;
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
use Livewire\WithPagination;

new class extends Component {

    use WithFileUploads;
    use WithPagination;

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
    public ?int $previewImageFor = null;
    public $previewImage = null;

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

        $item = Item::create([
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

        // Activity log
        app(RecordActivity::class)->handle(
            workspace: $workspace,
            user: auth()->user(),
            action: 'item.created',
            description: "Created item '{$item->name}'",
            subject: $item
        );

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

        $nextPosition = ((int) $item->images()->max('position')) + 1;

        $image = ItemImage::create([
            'item_id' => $item->id,
            'path' => $path,
            'position' => $nextPosition,
            'alt_text' => $item->name,
        ]);

        app(RecordActivity::class)->handle(
            workspace: $workspace,
            user: auth()->user(),
            action: 'image.uploaded',
            description: "Uploaded image to '{$item->name}'",
            subject: $item
        );

        $this->reset(['image', 'previewImage', 'previewImageFor']);

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

        app(RecordActivity::class)->handle(
            workspace: $workspace,
            user: auth()->user(),
            action: 'image.deleted',
            description: "Deleted image from '{$image->item->name}'",
            subject: $image->item
        );

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

        app(RecordActivity::class)->handle(
            workspace: $workspace,
            user: auth()->user(),
            action: 'image.cover_changed',
            description: "Changed cover image for '{$image->item->name}'",
            subject: $image->item
        );

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

    public function updatedImage(): void
    {
        $this->previewImage = $this->image;
    }

    public function setPreviewImageFor(int $itemId): void
    {
        $this->previewImageFor = $itemId;
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

            $this->resetPage();

            return;
        }

        $this->filterTagIds[] = $tagId;

        $this->resetPage();
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

        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCondition(): void
    {
        $this->resetPage();
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

        return $query->latest()->paginate(3);
    }

};
?>

<div class="max-w-4xl mx-auto p-4 space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
            Items
        </h1>

        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $collection->name }} · {{ current_workspace()?->name }}
        </p>
    </div>

    {{-- Create Form --}}
    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
        <form wire:submit="create" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Item name"
                        class="h-[52px] w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                    @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <select
                    wire:model="status"
                    class="h-[52px] w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    @foreach (\App\Enums\ItemStatus::cases() as $status)
                        <option value="{{ $status->value }}">
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>

                <select
                    wire:model="condition"
                    class="h-[52px] w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Condition --</option>

                    @foreach (\App\Enums\ItemCondition::cases() as $condition)
                        <option value="{{ $condition->value }}">
                            {{ $condition->label() }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="text"
                    wire:model="location"
                    placeholder="Location"
                    class="h-[52px] w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >

                <div>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="purchase_price"
                        placeholder="Purchase price"
                        class="h-[52px] w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                    @error('purchase_price') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="estimated_value"
                        placeholder="Estimated value"
                        class="h-[52px] w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                    @error('estimated_value') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <textarea
                wire:model="description"
                placeholder="Description"
                rows="3"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            ></textarea>

            <textarea
                wire:model="notes"
                placeholder="Notes"
                rows="3"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            ></textarea>

            <div class="rounded-xl border border-dashed border-zinc-300 p-3 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                Tags and images can be added after the item is created.
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="h-[44px] rounded-xl border border-zinc-300 px-5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-800"
                >
                    Create item
                </button>
            </div>
        </form>
    </div>
    {{-- End Create Form --}}

    {{-- Tag panel --}}
    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
        <div>
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Tags
            </h2>

            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Create reusable tags for this workspace.
            </p>
        </div>

        <form wire:submit="createTag" class="mt-4 flex gap-2">
            <input
                type="text"
                wire:model="tagName"
                placeholder="Tag name"
                class="h-9 w-full rounded-xl border border-zinc-300 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >

            <button
                type="submit"
                class="h-9 shrink-0 rounded-xl border border-zinc-300 px-4 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-700"
            >
                Add tag
            </button>
        </form>

        @error('tagName')
        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($this->tags as $tag)
                <button
                    wire:click="deleteTag({{ $tag->id }})"
                    class="rounded-full border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-500 transition hover:border-red-500/50 hover:bg-red-500/10 hover:text-red-400 dark:border-zinc-700 dark:text-zinc-400"
                >
                    {{ $tag->name }} ×
                </button>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    No tags yet.
                </p>
            @endforelse
        </div>
    </div>
    {{-- End Tag panel --}}

    <div class="space-y-3">
        {{-- Filters --}}
        <div
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm space-y-5 dark:border-zinc-800 dark:bg-zinc-950/60">
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
                        class="h-9 w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 dark:border-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-600"
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
                        class="h-9 w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 dark:border-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-600"
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
                            class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            Tag: {{ $this->tags->firstWhere('id', $tagId)?->name }} ×
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        {{-- End Filters --}}

        {{-- Items Loop --}}
        @forelse ($this->items as $item)
            <div
                x-data
                x-ref="item{{ $item->id }}"
                class="overflow-hidden rounded-2xl border border-zinc-200/60 bg-white shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950/60 dark:hover:bg-zinc-900/50">
                @if ($editingId === $item->id)
                    {{-- Edit Form --}}
                    <div
                        x-data="{ open: true }"
                        x-show="open"
                        x-transition.opacity.duration.200ms
                        class="p-5 space-y-5"
                    >

                        {{-- Name --}}
                        <div class="space-y-1.5">
                            <input
                                type="text"
                                wire:model="editingName"
                                placeholder="Item name"
                                class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 dark:border-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-600"
                            >
                            @error('editingName') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Description --}}
                        <textarea
                            wire:model="editingDescription"
                            placeholder="Description"
                            class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 dark:border-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-600"
                        ></textarea>

                        {{-- Status + Condition --}}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <select
                                wire:model="editingStatus"
                                class="h-9 w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-800"
                            >
                                @foreach (\App\Enums\ItemStatus::cases() as $status)
                                    <option value="{{ $status->value }}">
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>

                            <select
                                wire:model="editingCondition"
                                class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-800"
                            >
                                <option value="">Condition</option>

                                @foreach (\App\Enums\ItemCondition::cases() as $condition)
                                    <option value="{{ $condition->value }}">
                                        {{ $condition->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Prices --}}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model="editingPurchasePrice"
                                    placeholder="Purchase price"
                                    class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-800"
                                >
                                @error('editingPurchasePrice') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model="editingEstimatedValue"
                                    placeholder="Estimated value"
                                    class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-800"
                                >
                                @error('editingEstimatedValue') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Location --}}
                        <input
                            type="text"
                            wire:model="editingLocation"
                            placeholder="Location"
                            class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-800"
                        >

                        {{-- Notes --}}
                        <textarea
                            wire:model="editingNotes"
                            placeholder="Notes"
                            class="w-full rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-800"
                        ></textarea>

                        {{-- Actions --}}
                        <div class="flex items-center gap-3 pt-2">
                            <button
                                type="button"
                                wire:click="update"
                                class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-black hover:bg-zinc-200 transition"
                            >
                                Save
                            </button>

                            <button
                                type="button"
                                wire:click="cancelEdit"
                                class="rounded-lg border border-zinc-300 px-4 py-2 text-sm text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-900 transition"
                            >
                                Cancel
                            </button>
                        </div>

                    </div>
                    {{-- End Edit Form --}}
                @else
                    <div class="grid gap-5 p-5 md:grid-cols-[180px_1fr]">
                        {{-- Cover --}}
                        <div class="space-y-3">
                            <div class="aspect-square overflow-hidden rounded-2xl border border-zinc-200/60 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900">
                                @if ($item->images->isNotEmpty())
                                    <img
                                        src="{{ asset('storage/' . $item->images->first()->path) }}"
                                        alt="{{ $item->name }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xs text-zinc-400">
                                        No cover
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <input
                                    type="file"
                                    wire:model="image"
                                    wire:change="setPreviewImageFor({{ $item->id }})"
                                    accept="image/*"
                                    class="block w-full text-xs text-zinc-500 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-900 file:px-3 file:py-2 file:text-xs file:font-medium file:text-zinc-200 hover:file:bg-zinc-700"
                                >

                                <button
                                    type="button"
                                    wire:click="uploadImage({{ $item->id }})"
                                    class="w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-900"
                                >
                                    Upload image
                                </button>
                            </div>

                            @if ($previewImage && $previewImageFor === $item->id)
                                <img
                                    src="{{ $previewImage->temporaryUrl() }}"
                                    alt="Preview"
                                    class="h-24 w-24 rounded-xl border border-zinc-200 object-cover dark:border-zinc-800"
                                >
                            @endif

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
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <button
                                        wire:click="edit({{ $item->id }})"
                                        x-on:click="
                                            setTimeout(() => {
                                                $refs.item{{ $item->id }}?.scrollIntoView({
                                                    behavior: 'smooth',
                                                    block: 'center'
                                                })
                                            }, 250)
                                            "
                                        class="text-xs font-semibold text-blue-500 hover:text-blue-600"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        wire:click="delete({{ $item->id }})"
                                        class="rounded-lg px-2.5 py-1 text-xs font-medium text-red-500 transition hover:bg-red-900/50"
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

                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                @if ($item->condition)
                                    <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 px-4 py-3">
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                            Condition
                                        </p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item->condition->label() }}
                                        </p>
                                    </div>
                                @endif

                                @if ($item->status)
                                        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 px-4 py-3">
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                            Status
                                        </p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item->status->label() }}
                                        </p>
                                    </div>
                                @endif

                                @if ($item->location)
                                        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 px-4 py-3">
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
                            @if ($item->images->count() > 1)
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
                                    class="text-xs font-medium text-blue-500 transition hover:text-blue-400"
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
        {{-- End Items Loop --}}

        @if ($this->items->hasPages())
            <div class="pt-4">
                {{ $this->items->links() }}
            </div>
        @endif
    </div>
</div>
