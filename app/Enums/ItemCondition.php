<?php

namespace App\Enums;

enum ItemCondition: string
{
    case GRADED = 'graded';
    case SEALED = 'sealed';
    case MINT = 'mint';
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';

    public function label(): string
    {
        return match ($this) {
            self::GRADED => 'Graded',
            self::SEALED => 'Sealed',
            self::MINT => 'Mint',
            self::EXCELLENT => 'Excellent',
            self::GOOD => 'Good',
            self::FAIR => 'Fair',
            self::POOR => 'Poor',
        };
    }
}
