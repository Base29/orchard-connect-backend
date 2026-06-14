<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_id')
                    ->label('Ticket ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'info' => 'general',
                        'warning' => 'auth_issue',
                        'danger' => 'security',
                        'warning' => 'marketplace_dispute',
                        'success' => 'technical',
                    ])
                    ->sortable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'open',
                        'success' => 'resolved',
                        'neutral' => 'closed',
                    ])
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Resident')
                    ->searchable()
                    ->sortable()
                    ->default(fn ($record) => $record->guest_name ? $record->guest_name . ' (Guest)' : 'Guest'),
                TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->default('Unassigned'),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'general' => 'General',
                        'auth_issue' => 'Account/Auth Issue',
                        'security' => 'Security',
                        'marketplace_dispute' => 'Marketplace Dispute',
                        'technical' => 'Technical Support',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Review',
                        'open' => 'Open',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                EditAction::make(),

                Action::make('resolve')
                    ->label('Resolve Ticket')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'open']))
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'resolved' => 'Resolved',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->default('resolved'),
                        \Filament\Forms\Components\RichEditor::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->placeholder('Describe the resolution actions and conclusions for the user...')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'resolution_notes' => $data['resolution_notes'],
                        ]);

                        \App\Models\ModerationLog::create([
                            'action' => 'resolve_support_ticket',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Support ticket status resolved/closed by staff.',
                            'metadata' => json_encode([
                                'tracking_id' => $record->tracking_id,
                                'status' => $data['status'],
                            ]),
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
