<?php

namespace App\Filament\Resources\ModerationLogs\Pages;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModerationLog extends CreateRecord
{
    protected static string $resource = ModerationLogResource::class;
}
