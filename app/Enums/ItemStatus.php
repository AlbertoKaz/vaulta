<?php

namespace App\Enums;

enum ItemStatus: string
{
    case ACTIVE = 'active';
    case STORED = 'stored';
    case WISHLIST = 'wishlist';
    case SOLD = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::STORED => 'Stored',
            self::WISHLIST => 'Wishlist',
            self::SOLD => 'Sold',
        };
    }
}
