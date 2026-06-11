<?php

namespace App\Filament\Resources\ResidentProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Table;
use App\Models\ModerationLog;
use App\Events\ResidentVerificationStatusUpdated;

class ResidentProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Resident')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phase')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('block')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('house_number')
                    ->label('House/Plot No.')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('street_number')
                    ->label('Street/Lane')
                    ->sortable()
                    ->searchable()
                    ->default('-'),
                TextColumn::make('user_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'primary' => 'owner',
                        'info' => 'tenant',
                    ])
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),
                IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->can('verify-residents') && $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'is_verified' => true,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);

                        ModerationLog::create([
                            'action' => 'verify_resident',
                            'target_type' => \App\Models\User::class,
                            'target_id' => $record->user_id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Resident profile approved by staff.',
                            'metadata' => json_encode([
                                'phase' => $record->phase,
                                'block' => $record->block,
                                'house_number' => $record->house_number,
                            ]),
                        ]);

                        // Broadcast real-time status update
                        event(new ResidentVerificationStatusUpdated('approved', null, null, $record->user_id));
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()->can('verify-residents') && $record->status === 'pending')
                    ->form([
                        \Filament\Forms\Components\Select::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->options([
                                'address_mismatch' => 'Address Mismatch',
                                'blurry_document' => 'Blurry Document',
                                'expired_document' => 'Expired Document',
                                'invalid_document' => 'Invalid/Unsupported Document',
                             ])
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('rejection_message')
                            ->label('Custom Message (Optional)')
                            ->placeholder('Describe why the document was rejected...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'is_verified' => false,
                            'rejection_reason' => $data['rejection_reason'],
                            'rejection_message' => $data['rejection_message'] ?? null,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);

                        ModerationLog::create([
                            'action' => 'reject_resident',
                            'target_type' => \App\Models\User::class,
                            'target_id' => $record->user_id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Resident profile verification rejected: ' . $data['rejection_reason'] . '. ' . ($data['rejection_message'] ?? ''),
                            'metadata' => json_encode([
                                'rejection_reason' => $data['rejection_reason'],
                                'rejection_message' => $data['rejection_message'] ?? null,
                            ]),
                        ]);

                        // Broadcast real-time status update
                        event(new ResidentVerificationStatusUpdated('rejected', $data['rejection_reason'], $data['rejection_message'] ?? null, $record->user_id));
                    }),

                Action::make('override_moderation')
                    ->label('Override Moderation')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->visible(fn ($record) => auth()->user()->can('override-moderation'))
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('New Status')
                            ->options([
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'pending' => 'Pending',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Reason for Override')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldStatus = $record->status;
                        $newStatus = $data['status'];
                        $isVerified = $newStatus === 'approved';

                        $record->update([
                            'status' => $newStatus,
                            'is_verified' => $isVerified,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);

                        ModerationLog::create([
                            'action' => 'override_resident_moderation',
                            'target_type' => \App\Models\User::class,
                            'target_id' => $record->user_id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Moderation overridden: ' . $data['reason'],
                            'metadata' => json_encode([
                                'previous_status' => $oldStatus,
                                'new_status' => $newStatus,
                                'reason' => $data['reason'],
                            ]),
                        ]);

                        // Broadcast real-time status update
                        event(new ResidentVerificationStatusUpdated($newStatus, null, $data['reason'], $record->user_id));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
