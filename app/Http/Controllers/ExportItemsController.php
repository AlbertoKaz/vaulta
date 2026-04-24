<?php

namespace App\Http\Controllers;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportItemsController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $workspace = current_workspace();

        abort_unless($workspace, 403);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ItemStatus::class)],
            'condition' => ['nullable', Rule::enum(ItemCondition::class)],
            'collection_id' => ['nullable', 'integer'],
        ]);

        if (! empty($validated['collection_id'])) {
            abort_unless(
                $workspace->collections()
                    ->where('id', $validated['collection_id'])
                    ->exists(),
                404
            );
        }

        $filename = 'vaulta-items-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($workspace, $validated) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Name',
                'Collection',
                'Status',
                'Condition',
                'Purchase price',
                'Estimated value',
                'Location',
                'Created at',
            ]);

            $query = $workspace->items()
                ->with('collection')
                ->orderBy('name');

            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (! empty($validated['condition'])) {
                $query->where('condition', $validated['condition']);
            }

            if (! empty($validated['collection_id'])) {
                $query->where('collection_id', $validated['collection_id']);
            }

            $query->chunk(200, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->name,
                        $item->collection?->name,
                        $item->status?->label() ?? $item->status?->value ?? '',
                        $item->condition?->label() ?? $item->condition?->value ?? '',
                        $item->purchase_price,
                        $item->estimated_value,
                        $item->location,
                        optional($item->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
