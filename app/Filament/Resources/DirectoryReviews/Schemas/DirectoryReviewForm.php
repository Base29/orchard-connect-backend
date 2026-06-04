<?php

namespace App\Filament\Resources\DirectoryReviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DirectoryReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('directory_listing_id')
                    ->relationship('listing', 'name')
                    ->label('Business')
                    ->required()
                    ->searchable(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Resident')
                    ->required()
                    ->searchable(),
                Select::make('rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ])
                    ->label('Rating')
                    ->required(),
                Textarea::make('comment')
                    ->label('Review Comment')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }
}
