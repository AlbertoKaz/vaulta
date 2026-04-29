<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemImage extends Model
{
    protected $fillable = [
        'item_id',
        'path',
        'position',
        'alt_text',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
