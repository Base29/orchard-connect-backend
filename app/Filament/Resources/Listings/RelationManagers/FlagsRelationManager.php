<?php

namespace App\Filament\Resources\Listings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FlagsRelationManager extends RelationManager
{
    protected static string $relationship = 'flags';

    protected static ?string $title = 'Listing Reports / Flags';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only relation manager
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Reporter')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'spam' => 'warning',
                        'harassment', 'hate_speech' => 'danger',
                        'inappropriate' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'spam' => 'Spam / Misleading',
                        'harassment' => 'Harassment',
                        'hate_speech' => 'Hate Speech',
                        'inappropriate' => 'Inappropriate Content',
                        'other' => 'Other / Violation',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('comment')
                    ->label('Additional Details')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Reported At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Read-only
            ])
            ->recordActions([
                // Read-only
            ])
            ->toolbarActions([
                // Read-only
            ]);
    }
}
