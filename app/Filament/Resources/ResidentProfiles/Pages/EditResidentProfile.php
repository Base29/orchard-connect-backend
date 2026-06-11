<?php

namespace App\Filament\Resources\ResidentProfiles\Pages;

use App\Filament\Resources\ResidentProfiles\ResidentProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

use Filament\Actions\Action;
use App\Models\ModerationLog;
use App\Events\ResidentVerificationStatusUpdated;

class EditResidentProfile extends EditRecord
{
    protected static string $resource = ResidentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Verification')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()->can('verify-residents') && $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'approved',
                        'is_verified' => true,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);

                    ModerationLog::create([
                        'action' => 'verify_resident',
                        'target_type' => \App\Models\User::class,
                        'target_id' => $this->record->user_id,
                        'moderator_id' => auth()->id(),
                        'reason' => 'Resident profile approved by staff from detailed view.',
                        'metadata' => json_encode([
                            'phase' => $this->record->phase,
                            'block' => $this->record->block,
                            'house_number' => $this->record->house_number,
                        ]),
                    ]);

                    event(new ResidentVerificationStatusUpdated('approved', null, null, $this->record->user_id));

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Action::make('reject')
                ->label('Reject Verification')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => auth()->user()->can('verify-residents') && $this->record->status === 'pending')
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
                ->action(function (array $data) {
                    $this->record->update([
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
                        'target_id' => $this->record->user_id,
                        'moderator_id' => auth()->id(),
                        'reason' => 'Resident profile verification rejected from detailed view: ' . $data['rejection_reason'] . '. ' . ($data['rejection_message'] ?? ''),
                        'metadata' => json_encode([
                            'rejection_reason' => $data['rejection_reason'],
                            'rejection_message' => $data['rejection_message'] ?? null,
                        ]),
                    ]);

                    event(new ResidentVerificationStatusUpdated('rejected', $data['rejection_reason'], $data['rejection_message'] ?? null, $this->record->user_id));

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Action::make('override_moderation')
                ->label('Override Moderation')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->visible(fn () => auth()->user()->can('override-moderation'))
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
                ->action(function (array $data) {
                    $oldStatus = $this->record->status;
                    $newStatus = $data['status'];
                    $isVerified = $newStatus === 'approved';

                    $this->record->update([
                        'status' => $newStatus,
                        'is_verified' => $isVerified,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);

                    ModerationLog::create([
                        'action' => 'override_resident_moderation',
                        'target_type' => \App\Models\User::class,
                        'target_id' => $this->record->user_id,
                        'moderator_id' => auth()->id(),
                        'reason' => 'Moderation overridden from detailed view: ' . $data['reason'],
                        'metadata' => json_encode([
                            'previous_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'reason' => $data['reason'],
                        ]),
                    ]);

                    event(new ResidentVerificationStatusUpdated($newStatus, null, $data['reason'], $this->record->user_id));

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make(),
        ];
    }
}
