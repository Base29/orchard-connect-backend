<?php

namespace App\Filament\Resources\ModerationLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ModerationLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('action')
                    ->label('Action Performed')
                    ->required(),
                TextInput::make('target_type')
                    ->label('Target Model')
                    ->required(),
                TextInput::make('target_id')
                    ->label('Target ID/UUID')
                    ->required(),
                Select::make('moderator_id')
                    ->label('Action Moderator')
                    ->relationship('moderator', 'name')
                    ->placeholder('System / Auto'),
                Textarea::make('reason')
                    ->label('Moderation Reason')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('metadata')
                    ->label('Saved Pre-Action State Context (JSON)')
                    ->columnSpanFull()
                    ->rows(6),
            ]);
    }
}
