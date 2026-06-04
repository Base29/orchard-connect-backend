<?php

namespace App\Filament\Resources\DirectoryCategories;

use App\Filament\Resources\DirectoryCategories\Pages\CreateDirectoryCategory;
use App\Filament\Resources\DirectoryCategories\Pages\EditDirectoryCategory;
use App\Filament\Resources\DirectoryCategories\Pages\ListDirectoryCategories;
use App\Filament\Resources\DirectoryCategories\Schemas\DirectoryCategoryForm;
use App\Filament\Resources\DirectoryCategories\Tables\DirectoryCategoriesTable;
use App\Models\DirectoryCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DirectoryCategoryResource extends Resource
{
    protected static ?string $model = DirectoryCategory::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Marketplace & Business';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    public static function form(Schema $schema): Schema
    {
        return DirectoryCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectoryCategoriesTable::configure($table);
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
            'index' => ListDirectoryCategories::route('/'),
            'create' => CreateDirectoryCategory::route('/create'),
            'edit' => EditDirectoryCategory::route('/{record}/edit'),
        ];
    }
}
