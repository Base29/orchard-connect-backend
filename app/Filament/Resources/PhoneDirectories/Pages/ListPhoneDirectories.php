<?php

namespace App\Filament\Resources\PhoneDirectories\Pages;

use App\Filament\Resources\PhoneDirectories\PhoneDirectoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhoneDirectories extends ListRecords
{
    protected static string $resource = PhoneDirectoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
