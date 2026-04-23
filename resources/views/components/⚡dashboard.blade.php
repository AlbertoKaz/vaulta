<?php

use App\Enums\ItemStatus;
use Livewire\Component;

new class extends Component
{
    public function getWorkspaceProperty()
    {
        return current_workspace();
    }

    public function getTotalCollectionsProperty(): int
    {
        if (! $this->workspace) {
            return 0;
        }

        return $this->workspace->collections()->count();
    }

    public function getTotalItemsProperty(): int
    {
        if (! $this->workspace) {
            return 0;
        }

        return $this->workspace->items()->count();
    }

    public function getEstimatedValueProperty(): float
    {
        if (! $this->workspace) {
            return 0;
        }

        return (float) $this->workspace->items()->sum('estimated_value');
    }

    public function getPurchaseValueProperty(): float
    {
        if (! $this->workspace) {
            return 0;
        }

        return (float) $this->workspace->items()->sum('purchase_price');
    }

    public function getWishlistCountProperty(): int
    {
        if (! $this->workspace) {
            return 0;
        }

        return $this->workspace->items()
            ->where('status', ItemStatus::WISHLIST->value)
            ->count();
    }

    public function getActiveCountProperty(): int
    {
        if (! $this->workspace) {
            return 0;
        }

        return $this->workspace->items()
            ->where('status', ItemStatus::ACTIVE->value)
            ->count();
    }

    public function getStoredCountProperty(): int
    {
        if (! $this->workspace) {
            return 0;
        }

        return $this->workspace->items()
            ->where('status', ItemStatus::STORED->value)
            ->count();
    }

    public function getSoldCountProperty(): int
    {
        if (! $this->workspace) {
            return 0;
        }

        return $this->workspace->items()
            ->where('status', ItemStatus::SOLD->value)
            ->count();
    }

    public function getProfitProperty(): float
    {
        return $this->estimatedValue - $this->purchaseValue;
    }

    public function getAverageValueProperty(): float
    {
        if (! $this->workspace || $this->totalItems === 0) {
            return 0;
        }

        return $this->estimatedValue / $this->totalItems;
    }

    public function getTopItemsProperty()
    {
        if (! $this->workspace) {
            return collect();
        }

        return $this->workspace->items()
            ->orderByDesc('estimated_value')
            ->take(5)
            ->get();
    }

    public function getLatestItemsProperty()
    {
        if (! $this->workspace) {
            return collect();
        }

        return $this->workspace->items()
            ->with('collection')
            ->latest()
            ->take(5)
            ->get();
    }
};
?>

<div class="max-w-4xl mx-auto p-4 space-y-6">
    <div>
        <h1 class="text-3xl font-bold">
            Dashboard
        </h1>
        <p class="text-sm text-gray-500">
            Workspace: {{ $this->workspace?->name ?? 'No workspace' }}
        </p>
    </div>

    {{-- Primary KPIs --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Estimated total value</p>
            <p class="text-2xl font-semibold">€{{ number_format($this->estimatedValue, 2) }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Profit / Loss</p>
            <p class="text-2xl font-semibold {{ $this->profit >= 0 ? 'text-green-500' : 'text-red-600' }}">
                €{{ number_format($this->profit, 2) }}
            </p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Purchase total</p>
            <p class="text-2xl font-semibold">€{{ number_format($this->purchaseValue, 2) }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Total items</p>
            <p class="text-2xl font-semibold">{{ $this->totalItems }}</p>
        </div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Total collections</p>
            <p class="text-2xl font-semibold">{{ $this->totalCollections }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Average item value</p>
            <p class="text-2xl font-semibold">€{{ number_format($this->averageValue, 2) }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Wishlist items</p>
            <p class="text-2xl font-semibold">{{ $this->wishlistCount }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Sold items</p>
            <p class="text-2xl font-semibold">{{ $this->soldCount }}</p>
        </div>
    </div>

    {{-- Inventory status --}}
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">Inventory status</h2>
            <p class="mt-1 text-sm text-gray-500">
                Current distribution of items across the workspace.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="border rounded-xl p-4">
                <p class="text-sm text-gray-500">Active</p>
                <p class="text-2xl font-semibold">{{ $this->activeCount }}</p>
            </div>

            <div class="border rounded-xl p-4">
                <p class="text-sm text-gray-500">Stored</p>
                <p class="text-2xl font-semibold">{{ $this->storedCount }}</p>
            </div>

            <div class="border rounded-xl p-4">
                <p class="text-sm text-gray-500">Wishlist</p>
                <p class="text-2xl font-semibold">{{ $this->wishlistCount }}</p>
            </div>

            <div class="border rounded-xl p-4">
                <p class="text-sm text-gray-500">Sold</p>
                <p class="text-2xl font-semibold">{{ $this->soldCount }}</p>
            </div>
        </div>
    </div>

    {{-- Top valuable items --}}
    <div class="border rounded-xl p-4">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Top valuable items</h2>
            <p class="mt-1 text-sm text-gray-500">
                Highest estimated-value items in this workspace.
            </p>
        </div>

        <div class="space-y-3">
            @forelse ($this->topItems as $item)
                <div class="flex items-center justify-between border rounded-lg px-3 py-2">
                    <div>
                        <p class="font-medium">{{ $item->name }}</p>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">
                            {{ $item->collection?->name }}
                        </p>
                        <p class="text-sm font-medium text-zinc-200">
                            €{{ number_format((float) $item->estimated_value, 2) }}
                        </p>
                    </div>

                    <a
                        href="{{ route('items.index', $item->collection_id) }}"
                        class="text-sm text-blue-500 hover:underline"
                    >
                        View collection
                    </a>
                </div>
            @empty
                <p class="text-sm text-gray-500">No items yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Latest items --}}
    <div class="border rounded-xl p-4">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Latest items</h2>
            <p class="mt-1 text-sm text-gray-500">
                Most recently added items in this workspace.
            </p>
        </div>

        <div class="space-y-3">
            @forelse ($this->latestItems as $item)
                <div class="flex items-center justify-between border rounded-lg px-3 py-2">
                    <div>
                        <p class="font-medium">{{ $item->name }}</p>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">
                            {{ $item->collection?->name }}
                        </p>
                        <p class="text-sm text-gray-500">
                            {{ $item->status?->label() ?? 'No status' }}
                            @if ($item->estimated_value)
                                · €{{ number_format((float) $item->estimated_value, 2) }}
                            @endif
                        </p>
                    </div>

                    <a
                        href="{{ route('items.index', $item->collection_id) }}"
                        class="text-sm text-blue-500 hover:underline"
                    >
                        View collection
                    </a>
                </div>
            @empty
                <p class="text-sm text-gray-500">No items yet.</p>
            @endforelse
        </div>
    </div>
</div>
