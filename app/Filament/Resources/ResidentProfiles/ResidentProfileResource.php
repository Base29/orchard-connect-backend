<?php

namespace App\Filament\Resources\ResidentProfiles;

use App\Filament\Resources\ResidentProfiles\Pages\CreateResidentProfile;
use App\Filament\Resources\ResidentProfiles\Pages\EditResidentProfile;
use App\Filament\Resources\ResidentProfiles\Pages\ListResidentProfiles;
use App\Filament\Resources\ResidentProfiles\Schemas\ResidentProfileForm;
use App\Filament\Resources\ResidentProfiles\Tables\ResidentProfilesTable;
use App\Models\ResidentProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResidentProfileResource extends Resource
{
    protected static ?string $model = ResidentProfile::class;

    public static function canAccessNavigation(): bool
    {
        return !auth()->user()->hasAnyRole(['marketplace-moderator', 'content-moderator']);
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Community & Verification';

    protected static ?string $navigationLabel = 'Residency Verifications';

    protected static ?string $modelLabel = 'Residency Verification';

    protected static ?string $pluralModelLabel = 'Residency Verifications';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function form(Schema $schema): Schema
    {
        return ResidentProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResidentProfilesTable::configure($table);
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
            'index' => ListResidentProfiles::route('/'),
            'create' => CreateResidentProfile::route('/create'),
            'edit' => EditResidentProfile::route('/{record}/edit'),
        ];
    }
}
