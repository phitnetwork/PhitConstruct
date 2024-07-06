<?php

namespace App\Filament\Resources\RecurrentExpenseResource\Pages;

use App\Filament\Resources\RecurrentExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecurrentExpense extends EditRecord
{
    protected static string $resource = RecurrentExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
