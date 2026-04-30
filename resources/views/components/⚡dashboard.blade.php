<?php

use App\Enums\ItemStatus;
use App\Models\ActivityLog;
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

    public function getRecentActivitiesProperty()
    {
        if (! $this->workspace) {
            return collect();
        }

        return ActivityLog::query()
            ->with('user')
            ->where('workspace_id', $this->workspace->id)
            ->latest()
            ->take(5)
            ->get();
    }
};
?>

<div class="max-w-4xl mx-auto p-4 space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Dashboard
            </h1>

            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Workspace: {{ $this->workspace?->name ?? 'No workspace' }}
            </p>
        </div>

        <a
            href="{{ route('exports.items') }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-900"
        >
            Export CSV
        </a>
    </div>

    {{-- Primary KPIs --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Estimated total value</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                €{{ number_format($this->estimatedValue, 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Profit / Loss</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight {{ $this->profit >= 0 ? 'text-green-500' : 'text-red-500' }}">
                €{{ number_format($this->profit, 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Purchase total</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                €{{ number_format($this->purchaseValue, 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total items</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $this->totalItems }}
            </p>
        </div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total collections</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $this->totalCollections }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Average item value</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                €{{ number_format($this->averageValue, 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Wishlist items</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $this->wishlistCount }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sold items</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $this->soldCount }}
            </p>
        </div>
    </div>

    {{-- Inventory status --}}
    <div class="space-y-4">
        <div>
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Inventory status
            </h2>

            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Current distribution of items across the workspace.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $this->activeCount }}
                </p>
            </div>

            <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Stored</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $this->storedCount }}
                </p>
            </div>

            <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Wishlist</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $this->wishlistCount }}
                </p>
            </div>

            <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/60">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sold</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $this->soldCount }}
                </p>
            </div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                Recent activity
            </h2>
            <p class="mt-1 text-sm text-zinc-500">
                Latest 5 actions recorded in this workspace.
            </p>
        </div>

        {{-- Recent Activities Log --}}
        <div class="space-y-2">
            @forelse ($this->recentActivities as $activity)

                @php
                    $color = match(true) {
                        str($activity->action)->contains(['created', 'uploaded', 'added']) => 'bg-green-500',
                        str($activity->action)->contains(['deleted', 'removed']) => 'bg-red-500',
                        str($activity->action)->contains(['changed', 'updated']) => 'bg-yellow-500',
                        default => 'bg-zinc-400 dark:bg-zinc-600',
                    };
                @endphp

                <div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200/70 px-4 py-3 dark:border-zinc-800/80">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full {{ $color }}"></span>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $activity->description }}
                            </p>

                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">
                                {{ $activity->user?->name ?? 'System' }}
                                · {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <span class="hidden shrink-0 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400 sm:inline-flex">
                {{ str($activity->action)->replace('.', ' ')->headline() }}
            </span>
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    No activity recorded yet.
                </p>
            @endforelse
        </div>
    </div>

    {{-- Top valuable items --}}
    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="mb-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Latest items
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Most recently added items in this workspace.
            </p>
        </div>

        {{-- Loop --}}
        <div class="grid gap-3 lg:grid-cols-2">
            @forelse ($this->latestItems as $item)
                <div class="group flex items-center justify-between gap-4 rounded-xl border border-zinc-800/70 bg-zinc-950/70 px-4 py-3 transition hover:bg-zinc-900/70">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-zinc-900">
                            @if ($item->images->first())
                                <img
                                    src="{{ asset('storage/' . $item->images->first()->path) }}"
                                    alt="{{ $item->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center text-center text-[11px] text-zinc-500">
                                    No image
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-100">
                                {{ $item->name }}
                            </p>

                            <p class="truncate text-xs text-zinc-500">
                                {{ $item->collection?->name ?? 'No collection' }}
                            </p>

                            <p class="mt-0.5 text-xs text-zinc-500">
                                {{ $item->status?->label() ?? 'No status' }}

                                @if ($item->estimated_value)
                                    <span class="font-medium text-zinc-200">
                                · €{{ number_format((float) $item->estimated_value, 2) }}
                            </span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ route('items.index', $item->collection_id) }}"
                        class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium text-blue-400 hover:bg-blue-950/40"
                    >
                        View
                    </a>
                </div>
            @empty
                <p class="text-sm text-zinc-500">
                    No items yet.
                </p>
            @endforelse
        </div>
    </div>

    {{-- Latest items --}}
    <div class="rounded-2xl border border-zinc-200/60 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="mb-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Latest items
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Most recently added items in this workspace.
            </p>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            @forelse ($this->latestItems as $item)
                <div class="group flex items-center justify-between gap-4 rounded-xl border border-zinc-800/70 bg-zinc-950/70 px-4 py-3 transition hover:bg-zinc-900/70">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-zinc-900">
                            @if ($item->images->first())
                                <img
                                    src="{{ asset('storage/' . $item->images->first()->path) }}"
                                    alt="{{ $item->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center text-center text-[11px] text-zinc-500">
                                    No image
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-100">
                                {{ $item->name }}
                            </p>

                            <p class="truncate text-xs text-zinc-500">
                                {{ $item->collection?->name ?? 'No collection' }}
                            </p>

                            <p class="mt-0.5 text-xs text-zinc-500">
                                {{ $item->status?->label() ?? 'No status' }}

                                @if ($item->estimated_value)
                                    <span class="font-medium text-zinc-200">
                                · €{{ number_format((float) $item->estimated_value, 2) }}
                            </span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ route('items.index', $item->collection_id) }}"
                        class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium text-blue-400 hover:bg-blue-950/40"
                    >
                        View
                    </a>
                </div>
            @empty
                <p class="text-sm text-zinc-500">
                    No items yet.
                </p>
            @endforelse
        </div>
    </div>
</div>
