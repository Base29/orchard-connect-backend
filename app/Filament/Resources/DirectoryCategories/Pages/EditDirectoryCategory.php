<?php

namespace App\Filament\Resources\DirectoryCategories\Pages;

use App\Filament\Resources\DirectoryCategories\DirectoryCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDirectoryCategory extends EditRecord
{
    protected static string $resource = DirectoryCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
