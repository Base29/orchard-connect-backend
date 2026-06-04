<?php

namespace App\Filament\Resources\DirectoryListings\Pages;

use App\Filament\Resources\DirectoryListings\DirectoryListingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDirectoryListing extends EditRecord
{
    protected static string $resource = DirectoryListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
