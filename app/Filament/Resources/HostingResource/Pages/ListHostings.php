<?php

namespace App\Filament\Resources\HostingResource\Pages;

use App\Filament\Resources\HostingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostings extends ListRecords
{
    protected static string $resource = HostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
