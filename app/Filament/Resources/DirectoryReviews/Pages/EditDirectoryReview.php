<?php

namespace App\Filament\Resources\DirectoryReviews\Pages;

use App\Filament\Resources\DirectoryReviews\DirectoryReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDirectoryReview extends EditRecord
{
    protected static string $resource = DirectoryReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
