<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Traits\HasToastNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use HasToastNotifications;

    protected static string $resource = UserResource::class;
}
