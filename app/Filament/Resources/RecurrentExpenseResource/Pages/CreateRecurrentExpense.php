<?php

namespace App\Filament\Resources\RecurrentExpenseResource\Pages;

use App\Filament\Resources\RecurrentExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurrentExpense extends CreateRecord
{
    protected static string $resource = RecurrentExpenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
