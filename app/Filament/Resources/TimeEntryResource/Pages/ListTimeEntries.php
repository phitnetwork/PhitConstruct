<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TimeEntryResource;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('_time_entry_create_manual_entry')->translateLabel(),
        ];
    }
}
