<?php

namespace App\Filament\Resources\DirectoryReviews\Pages;

use App\Filament\Resources\DirectoryReviews\DirectoryReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDirectoryReviews extends ListRecords
{
    protected static string $resource = DirectoryReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
