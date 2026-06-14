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
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->colors([
                        'warning' => 'superadmin',
                        'success' => 'community-admin',
                        'info' => 'content-moderator',
                        'primary' => 'marketplace-moderator',
                    ])
                    ->searchable()
                    ->default('-'),
                TextColumn::make('residentProfile.phase')
                    ->label('Phase')
                    ->sortable()
                    ->default('-'),
                TextColumn::make('residentProfile.block')
                    ->label('Block')
                    ->sortable()
                    ->default('-'),
                IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->state(fn ($record) => $record->email_verified_at !== null)
                    ->sortable(),
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
                    ->visible(fn ($record) => auth()->user()->can('verify-residents') && $record->residentProfile && !$record->residentProfile->is_verified)
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

                Action::make('verify_email')
                    ->label('Verify Email')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->hasRole('superadmin') && is_null($record->email_verified_at))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->markEmailAsVerified();

                        event(new \Illuminate\Auth\Events\Verified($record));

                        ModerationLog::create([
                            'action' => 'verify_email',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'User email verified by superadmin via dashboard.',
                            'metadata' => json_encode(['email' => $record->email]),
                        ]);
                    }),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn ($record) => auth()->user()->can('manage-system') && $record->status === 'active')
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
                    ->visible(fn ($record) => 
                        $record->status !== 'banned' && 
                        !$record->hasRole('superadmin') && 
                        (auth()->user()->can('manage-system') || auth()->user()->can('assign-moderator-roles'))
                    )
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
