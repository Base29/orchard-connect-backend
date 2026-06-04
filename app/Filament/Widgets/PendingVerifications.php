<?php

namespace App\Filament\Widgets;

use App\Models\ResidentProfile;
use App\Filament\Resources\ResidentProfiles\ResidentProfileResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;

class PendingVerifications extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Pending Verifications')
            ->query(
                ResidentProfile::where('status', 'pending')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Resident')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('phase')
                    ->label('Phase')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('block')
                    ->label('Block')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('house_number')
                    ->label('House #'),
                Tables\Columns\TextColumn::make('street_number')
                    ->label('Street #'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->since()
                    ->color('gray'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ResidentProfile $record) {
                        $record->update([
                            'status' => 'approved',
                            'is_verified' => true,
                            'verified_at' => now(),
                            'verified_by' => auth()->id(),
                        ]);

                        event(new \App\Events\ResidentVerificationStatusUpdated(
                            'approved',
                            null,
                            null,
                            $record->user_id
                        ));
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->form([
                        Select::make('rejection_reason')
                            ->label('Reason')
                            ->options([
                                'invalid_document' => 'Invalid or Blur Document',
                                'address_mismatch' => 'Address Mismatch',
                                'not_resident' => 'Not a Resident',
                            ])
                            ->required(),
                        Textarea::make('rejection_message')
                            ->label('Rejection Message')
                            ->required(),
                    ])
                    ->action(function (ResidentProfile $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'is_verified' => false,
                            'rejection_reason' => $data['rejection_reason'],
                            'rejection_message' => $data['rejection_message'],
                            'verified_at' => now(),
                            'verified_by' => auth()->id(),
                        ]);

                        event(new \App\Events\ResidentVerificationStatusUpdated(
                            'rejected',
                            $data['rejection_reason'],
                            $data['rejection_message'],
                            $record->user_id
                        ));
                    }),
                Action::make('edit')
                    ->label('Review')
                    ->icon('heroicon-m-eye')
                    ->url(fn (ResidentProfile $record): string => ResidentProfileResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
