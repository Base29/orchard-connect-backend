<?php

namespace App\Filament\Resources\PhoneDirectories;

use App\Filament\Resources\PhoneDirectories\Pages\CreatePhoneDirectory;
use App\Filament\Resources\PhoneDirectories\Pages\EditPhoneDirectory;
use App\Filament\Resources\PhoneDirectories\Pages\ListPhoneDirectories;
use App\Filament\Resources\PhoneDirectories\Schemas\PhoneDirectoryForm;
use App\Filament\Resources\PhoneDirectories\Tables\PhoneDirectoriesTable;
use App\Models\PhoneDirectory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PhoneDirectoryResource extends Resource
{
    protected static ?string $model = PhoneDirectory::class;

    protected static ?string $navigationLabel = 'Phone Directory';

    protected static ?string $modelLabel = 'Directory Contact';

    protected static ?string $pluralModelLabel = 'Phone Directory Contacts';

    protected static \UnitEnum|string|null $navigationGroup = 'Marketplace & Business';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    public static function form(Schema $schema): Schema
    {
        return PhoneDirectoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhoneDirectoriesTable::configure($table);
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
            'index' => ListPhoneDirectories::route('/'),
            'create' => CreatePhoneDirectory::route('/create'),
            'edit' => EditPhoneDirectory::route('/{record}/edit'),
        ];
    }
}
