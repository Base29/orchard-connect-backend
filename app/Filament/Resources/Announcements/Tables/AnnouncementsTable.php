<?php

namespace App\Filament\Resources\Announcements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(55),
                TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'info' => 'general',
                        'danger' => 'security',
                        'warning' => 'maintenance',
                        'success' => 'event',
                    ])
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'neutral' => 'archived',
                    ])
                    ->sortable(),
                IconColumn::make('pinned')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->default('System'),
                TextColumn::make('created_at')
                    ->label('Published At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'general' => 'General',
                        'security' => 'Security',
                        'maintenance' => 'Maintenance',
                        'event' => 'Event',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
