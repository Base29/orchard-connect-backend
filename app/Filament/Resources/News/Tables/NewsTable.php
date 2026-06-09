<?php

namespace App\Filament\Resources\News\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Thumbnail')
                    ->disk(empty(config('filesystems.disks.s3.key')) || empty(config('filesystems.disks.s3.bucket')) ? 'public' : 's3')
                    ->square()
                    ->size(40),
                TextColumn::make('title')
                    ->label('Headline')
                    ->searchable()
                    ->sortable()
                    ->limit(55),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'neutral' => 'archived',
                    ])
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
