<?php

namespace App\Filament\Resources\ModerationLogs\Pages;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditModerationLog extends EditRecord
{
    protected static string $resource = ModerationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
