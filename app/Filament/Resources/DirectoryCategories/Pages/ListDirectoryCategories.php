<?php

namespace App\Filament\Resources\DirectoryCategories\Pages;

use App\Filament\Resources\DirectoryCategories\DirectoryCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDirectoryCategories extends ListRecords
{
    protected static string $resource = DirectoryCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
