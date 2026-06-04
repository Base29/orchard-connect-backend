<?php

namespace App\Filament\Resources\DirectoryListings;

use App\Filament\Resources\DirectoryListings\Pages\CreateDirectoryListing;
use App\Filament\Resources\DirectoryListings\Pages\EditDirectoryListing;
use App\Filament\Resources\DirectoryListings\Pages\ListDirectoryListings;
use App\Filament\Resources\DirectoryListings\Schemas\DirectoryListingForm;
use App\Filament\Resources\DirectoryListings\Tables\DirectoryListingsTable;
use App\Models\DirectoryListing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DirectoryListingResource extends Resource
{
    protected static ?string $model = DirectoryListing::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Marketplace & Business';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return DirectoryListingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectoryListingsTable::configure($table);
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
            'index' => ListDirectoryListings::route('/'),
            'create' => CreateDirectoryListing::route('/create'),
            'edit' => EditDirectoryListing::route('/{record}/edit'),
        ];
    }
}
