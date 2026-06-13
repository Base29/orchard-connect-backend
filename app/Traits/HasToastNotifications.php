<?php

namespace App\Traits;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasToastNotifications
{
    protected function getSavedNotification(): ?Notification
    {
        $resource = static::getResource();
        $label = $resource ? $resource::getModelLabel() : 'record';
        return Notification::make()
            ->success()
            ->title(ucfirst($label) . ' saved successfully.')
            ->body('The changes have been saved.');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $resource = static::getResource();
        $label = $resource ? $resource::getModelLabel() : 'record';
        return Notification::make()
            ->success()
            ->title(ucfirst($label) . ' created successfully.')
            ->body('The new record has been created.');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Validation Failed')
            ->body('Please check the form for errors: ' . implode(' ', Arr::flatten($exception->errors())))
            ->send();
    }
}
