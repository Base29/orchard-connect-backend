<?php

namespace App\Filament\Resources\DirectoryReviews;

use App\Filament\Resources\DirectoryReviews\Pages\CreateDirectoryReview;
use App\Filament\Resources\DirectoryReviews\Pages\EditDirectoryReview;
use App\Filament\Resources\DirectoryReviews\Pages\ListDirectoryReviews;
use App\Filament\Resources\DirectoryReviews\Schemas\DirectoryReviewForm;
use App\Filament\Resources\DirectoryReviews\Tables\DirectoryReviewsTable;
use App\Models\DirectoryReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DirectoryReviewResource extends Resource
{
    protected static ?string $model = DirectoryReview::class;

    protected static ?string $navigationLabel = 'Business Reviews';

    protected static ?string $modelLabel = 'Business Review';

    protected static ?string $pluralModelLabel = 'Business Reviews';

    protected static \UnitEnum|string|null $navigationGroup = 'Marketplace & Business';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function form(Schema $schema): Schema
    {
        return DirectoryReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectoryReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDirectoryReviews::route('/'),
            'create' => CreateDirectoryReview::route('/create'),
            'edit' => EditDirectoryReview::route('/{record}/edit'),
        ];
    }
}
