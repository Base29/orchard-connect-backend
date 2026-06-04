<?php

namespace App\Filament\Resources\Listings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\ModerationLog;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(35),
                TextColumn::make('user.name')
                    ->label('Seller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'info' => 'pending',
                        'neutral' => 'sold',
                        'warning' => 'flagged',
                        'danger' => 'suspended',
                    ])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('flags_count')
                    ->label('Flags')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Listed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Under Review',
                        'sold' => 'Sold',
                        'flagged' => 'Flagged',
                        'suspended' => 'Suspended',
                    ]),
                SelectFilter::make('category')
                    ->options([
                        'Electronics' => 'Electronics',
                        'Vehicles' => 'Vehicles',
                        'Property' => 'Property',
                        'Services' => 'Services',
                        'Furniture' => 'Furniture',
                        'Other' => 'Other',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                EditAction::make(),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->can('moderate_listing') && in_array($record->status, ['pending', 'flagged', 'suspended']))
                    ->action(function ($record) {
                        $oldStatus = $record->status;
                        $record->update(['status' => 'active']);

                        ModerationLog::create([
                            'action' => 'approve_listing',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Listing approved by administrator.',
                            'metadata' => json_encode(['previous_status' => $oldStatus]),
                        ]);
                    }),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()->can('moderate_listing') && $record->status !== 'suspended')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Reason for Suspension')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldStatus = $record->status;
                        $record->update(['status' => 'suspended']);

                        ModerationLog::create([
                            'action' => 'suspend_listing',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => $data['reason'],
                            'metadata' => json_encode(['previous_status' => $oldStatus]),
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
