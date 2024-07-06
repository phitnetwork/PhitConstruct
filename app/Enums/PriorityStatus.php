<?php

namespace App\Enums;
 
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
 
enum PriorityStatus: string implements HasLabel, HasColor
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
 
    public function getLabel(): ?string
    {
        return match ($this) {
            self::LOW => __('_priority_low'),
            self::MEDIUM => __('_priority_medium'),
            self::HIGH => __('_priority_high'),
        };
    }
 
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LOW => 'success',
            self::MEDIUM => 'warning',
            self::HIGH => 'danger',
        };
    }
}