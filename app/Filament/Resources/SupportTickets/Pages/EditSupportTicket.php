<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

            DeleteAction::make(),
        ];
    }
}
