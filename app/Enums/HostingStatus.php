<?php

namespace App\Enums;
 
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
 
enum HostingStatus: string implements HasLabel, HasColor
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => __('_hosting_status_active'),
            self::INACTIVE => __('_hosting_status_inactive'),
            self::SUSPENDED => __('_hosting_status_suspended'),
            self::TERMINATED => __('_hosting_status_terminated'),
        };
    }
 
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::SUSPENDED => 'danger',
            self::TERMINATED => 'danger',
        };
    }
}