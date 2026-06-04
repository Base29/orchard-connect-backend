<?php

namespace App\Filament\Resources\ModerationLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModerationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('moderator.name')
                    ->label('Moderator')
                    ->searchable()
                    ->sortable()
                    ->default('System / Auto'),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->colors([
                        'danger' => fn ($state) => in_array($state, ['ban_user', 'delete_post', 'delete_comment']),
                        'warning' => fn ($state) => in_array($state, ['suspend_user', 'suspend_listing', 'flag_post', 'flag_listing']),
                        'success' => fn ($state) => in_array($state, ['verify_resident', 'approve_listing']),
                    ])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_type')
                    ->label('Target Type')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                TextColumn::make('target_id')
                    ->label('Target ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // Read-only, no bulk delete
            ]);
    }
}
