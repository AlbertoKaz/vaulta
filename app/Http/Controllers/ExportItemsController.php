<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportItemsController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $workspace = current_workspace();

        abort_unless($workspace, 403);

        $filename = 'vaulta-items-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($workspace) {
            $handle = fopen('php://output', 'w');

            // BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // Header CSV
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

            $workspace->items()
                ->with('collection')
                ->orderBy('name')
                ->chunk(200, function ($items) use ($handle) {
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
