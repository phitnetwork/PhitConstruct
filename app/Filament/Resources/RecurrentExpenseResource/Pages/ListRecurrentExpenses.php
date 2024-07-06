<?php

namespace App\Filament\Resources\RecurrentExpenseResource\Pages;

use App\Filament\Resources\RecurrentExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecurrentExpenses extends ListRecords
{
    protected static string $resource = RecurrentExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
