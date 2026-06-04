<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Tables\Table;
use App\Models\ModerationLog;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'banned',
                    ])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('residentProfile.phase')
                    ->label('Phase')
                    ->sortable()
                    ->default('-'),
                TextColumn::make('residentProfile.block')
                    ->label('Block')
                    ->sortable()
                    ->default('-'),
                IconColumn::make('residentProfile.is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->can('verify_user') && $record->residentProfile && !$record->residentProfile->is_verified)
                    ->action(function ($record) {
                        $record->residentProfile()->update([
                            'is_verified' => true,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);

                        ModerationLog::create([
                            'action' => 'verify_resident',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Resident profile verified by staff.',
                            'metadata' => json_encode(['previous_is_verified' => false]),
                        ]);
                    }),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn ($record) => auth()->user()->can('ban_user') && $record->status === 'active')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Reason for Suspension')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldStatus = $record->status;
                        $record->update(['status' => 'suspended']);

                        ModerationLog::create([
                            'action' => 'suspend_user',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => $data['reason'],
                            'metadata' => json_encode(['previous_status' => $oldStatus]),
                        ]);
                    }),

                Action::make('ban')
                    ->label('Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()->can('ban_user') && $record->status !== 'banned')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Reason for Ban')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldStatus = $record->status;
                        $record->update(['status' => 'banned']);

                        ModerationLog::create([
                            'action' => 'ban_user',
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
