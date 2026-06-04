<?php

namespace App\Filament\Resources\ModerationLogs;

use App\Filament\Resources\ModerationLogs\Pages\CreateModerationLog;
use App\Filament\Resources\ModerationLogs\Pages\EditModerationLog;
use App\Filament\Resources\ModerationLogs\Pages\ListModerationLogs;
use App\Filament\Resources\ModerationLogs\Schemas\ModerationLogForm;
use App\Filament\Resources\ModerationLogs\Tables\ModerationLogsTable;
use App\Models\ModerationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ModerationLogResource extends Resource
{
    protected static ?string $model = ModerationLog::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Security & Audit';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return ModerationLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModerationLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModerationLogs::route('/'),
        ];
    }
}
