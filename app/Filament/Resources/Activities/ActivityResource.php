<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ManageActivities;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Security & Audit';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $pluralLabel = 'Activity Logs';

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

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System')
                    ->searchable()
                    ->description(fn ($record) => $record->causer?->email ?? 'Automated Action'),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return 'System';
                        $class = class_basename($state);
                        return $class;
                    })
                    ->description(fn ($record) => $record->subject_id ? "ID: " . substr($record->subject_id, 0, 8) . '...' : '')
                    ->searchable(),

                TextColumn::make('description')
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options([
                        'App\\Models\\User' => 'User',
                        'App\\Models\\ResidentProfile' => 'Resident Profile',
                        'App\\Models\\Post' => 'Post',
                        'App\\Models\\Comment' => 'Comment',
                        'App\\Models\\Listing' => 'Marketplace Listing',
                        'App\\Models\\Announcement' => 'Announcement',
                        'App\\Models\\Poll' => 'Poll',
                        'App\\Models\\PhoneDirectory' => 'Phone Directory',
                    ]),
            ])
            ->recordActions([
                Action::make('view_changes')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(fn ($record) => view('filament.admin.activity-log.details', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('3xl'),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivities::route('/'),
        ];
    }
}
