<?php

namespace App\Filament\Resources\DirectoryListings\Pages;

use App\Filament\Resources\DirectoryListings\DirectoryListingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDirectoryListings extends ListRecords
{
    protected static string $resource = DirectoryListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
