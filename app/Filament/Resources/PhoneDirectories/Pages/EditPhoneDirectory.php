<?php

namespace App\Filament\Resources\PhoneDirectories\Pages;

use App\Filament\Resources\PhoneDirectories\PhoneDirectoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPhoneDirectory extends EditRecord
{
    protected static string $resource = PhoneDirectoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
