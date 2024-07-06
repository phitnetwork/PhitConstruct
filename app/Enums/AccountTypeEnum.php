<?php

namespace App\Enums;
 
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
 
enum AccountTypeEnum: string implements HasLabel, HasColor, HasIcon
{
    case ASSETS = 'assets';
    case BANK = 'bank';
    case CREDIT_CARD = 'credit_card';
    case LIABILITIES = 'liabilities';
    case REVENUES = 'revenues';
    case EXPENSES = 'expenses';
 
    public function getLabel(): ?string
    {
        return match ($this) {
            self::ASSETS => __('_account_type_assets'),
            self::BANK => __('_account_type_bank'),
            self::CREDIT_CARD => __('_account_type_credit_card'),
            self::LIABILITIES => __('_account_type_liabilities'),
            self::REVENUES => __('_account_type_revenues'),
            self::EXPENSES => __('_account_type_expenses'),            
        };
    }
 
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ASSETS => 'success',
            self::BANK => 'warning',
            self::CREDIT_CARD => 'warning',
            self::LIABILITIES => 'warning',
            self::REVENUES => 'success',
            self::EXPENSES => 'danger',
        };
    }
 
    public function getIcon(): ?string
    {
        return match ($this) {
            self::ASSETS => 'heroicon-m-currency-euro',
            self::BANK => 'heroicon-m-building-library',
            self::CREDIT_CARD => 'heroicon-m-credit-card',
            self::LIABILITIES => 'heroicon-m-eye',
            self::REVENUES => 'heroicon-m-currency-dollar',
            self::EXPENSES => 'heroicon-m-scale',
        };
    }
}