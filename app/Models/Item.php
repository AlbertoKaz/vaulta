<?php

namespace App\Models;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Item extends Model
{
    protected $fillable = [
        'workspace_id',
        'collection_id',
        'name',
        'slug',
        'description',
        'status',
        'condition',
        'purchase_price',
        'estimated_value',
        'acquired_at',
        'location',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ItemStatus::class,
            'condition' => ItemCondition::class,
            'purchase_price' => 'decimal:2',
            'estimated_value' => 'decimal:2',
            'acquired_at' => 'date',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }
}
