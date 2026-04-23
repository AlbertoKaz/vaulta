<?php

use App\Enums\ItemStatus;
use App\Models\Item;
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

    public function getLatestItemsProperty()
    {
        if (! $this->workspace) {
            return collect();
        }

        return Item::where('workspace_id', $this->workspace->id)
            ->latest()
            ->take(5)
            ->get();
    }
};
?>

<div class="max-w-6xl mx-auto p-4 space-y-6">
    <div>
        <h1 class="text-3xl font-bold">
            Dashboard
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                Session workspace ID: {{ session('current_workspace_id') }}<br>
                Helper workspace: {{ current_workspace()?->name }}<br>
                Helper workspace ID: {{ current_workspace()?->id }}
            </div>
        </h1>
        <p class="text-sm text-gray-500">
            Workspace: {{ current_workspace()?->name ?? 'No workspace' }}
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Total collections</p>
            <p class="text-2xl font-semibold">{{ $this->totalCollections }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Total items</p>
            <p class="text-2xl font-semibold">{{ $this->totalItems }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Purchase total</p>
            <p class="text-2xl font-semibold">€{{ number_format($this->purchaseValue, 2) }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Estimated total value</p>
            <p class="text-2xl font-semibold">€{{ number_format($this->estimatedValue, 2) }}</p>
        </div>

        <div class="border rounded-xl p-4">
            <p class="text-sm text-gray-500">Profit / Loss</p>
            <p class="text-2xl font-semibold
    {       {{ $this->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                €{{ number_format($this->profit, 2) }}
            </p>
        </div>
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

    <div class="border rounded-xl p-4">
        <h2 class="text-lg font-semibold mb-4">Latest items</h2>

        <div class="space-y-3">
            @forelse ($this->latestItems as $item)
                <div class="flex items-center justify-between border rounded-lg px-3 py-2">
                    <div>
                        <p class="font-medium">{{ $item->name }}</p>
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
