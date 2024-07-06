<?php

namespace App\Enums;
 
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
 
enum RepeatInterval: string implements HasLabel, HasColor, HasIcon
{
    case EVERY_DAY = 'every_day';    
    case EVERY_WEEK = 'every_week';
    case EVERY_2WEEKS = 'every_2_weeks';
    case EVERY_MONTH = 'every_month';
    case EVERY_2MONTHS = 'every_2_months';
    case EVERY_3MONTHS = 'every_3_months';
    case EVERY_6MONTHS = 'every_6_months';
    case EVERY_YEAR = 'every_year';
    case EVERY_2YEARS = 'every_2_years';
    case EVERY_3YEARS = 'every_3_years';


    public function getLabel(): ?string
    {
        return match ($this) {
            self::EVERY_DAY => __('_recurrent_expense_repeat_interval_every_day'),
            self::EVERY_WEEK => __('_recurrent_expense_repeat_interval_every_week'),
            self::EVERY_2WEEKS => __('_recurrent_expense_repeat_interval_every_2_weeks'),
            self::EVERY_MONTH => __('_recurrent_expense_repeat_interval_every_month'),
            self::EVERY_2MONTHS => __('_recurrent_expense_repeat_interval_every_2_months'),
            self::EVERY_3MONTHS => __('_recurrent_expense_repeat_interval_every_3_months'),
            self::EVERY_6MONTHS => __('_recurrent_expense_repeat_interval_every_6_months'),           
            self::EVERY_YEAR => __('_recurrent_expense_repeat_interval_every_year'),
            self::EVERY_2YEARS => __('_recurrent_expense_repeat_interval_every_2_years'),
            self::EVERY_3YEARS => __('_recurrent_expense_repeat_interval_every_3_years'),
        };
    }
 
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EVERY_DAY => 'danger',
            self::EVERY_WEEK => 'warning',
            self::EVERY_2WEEKS => 'warning',
            self::EVERY_MONTH => 'info',
            self::EVERY_2MONTHS => 'info',
            self::EVERY_3MONTHS => 'info',
            self::EVERY_6MONTHS => 'info',
            self::EVERY_YEAR => 'success',
            self::EVERY_2YEARS => 'success',
            self::EVERY_3YEARS => 'success',
        };
    }
 
    public function getIcon(): ?string
    {
        return match ($this) {
            self::EVERY_DAY => 'heroicon-m-calendar',
            self::EVERY_WEEK => 'heroicon-m-calendar',
            self::EVERY_2WEEKS => 'heroicon-m-calendar',
            self::EVERY_MONTH => 'heroicon-m-calendar',
            self::EVERY_2MONTHS => 'heroicon-m-calendar',
            self::EVERY_3MONTHS => 'heroicon-m-calendar',
            self::EVERY_6MONTHS => 'heroicon-m-calendar',
            self::EVERY_YEAR => 'heroicon-m-calendar',
            self::EVERY_2YEARS => 'heroicon-m-calendar',
            self::EVERY_3YEARS => 'heroicon-m-calendar',
        };
    }
}