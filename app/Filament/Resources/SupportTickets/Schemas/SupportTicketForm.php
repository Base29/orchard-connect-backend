<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('tracking_id')
                            ->label('Tracking ID')
                            ->disabled(),
                        Select::make('category')
                            ->options([
                                'general' => 'General',
                                'auth_issue' => 'Account/Auth Issue',
                                'security' => 'Security',
                                'marketplace_dispute' => 'Marketplace Dispute',
                                'technical' => 'Technical Support',
                            ])
                            ->disabled(),
                        TextInput::make('subject')
                            ->columnSpanFull()
                            ->disabled(),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull()
                            ->disabled(),
                    ]),

                Section::make('Submitter Info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('guest_name')
                            ->label('Guest Name')
                            ->disabled()
                            ->placeholder('N/A')
                            ->visible(fn ($record) => $record && $record->user_id === null),
                        TextInput::make('guest_email')
                            ->label('Guest Email')
                            ->disabled()
                            ->placeholder('N/A')
                            ->visible(fn ($record) => $record && $record->user_id === null),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Registered Resident')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->user_id !== null),
                    ])
                    ->visible(fn ($record) => $record !== null),

                Section::make('Resolution & Operations')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending Review',
                                'open' => 'Open',
                                'resolved' => 'Resolved',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->default('pending'),
                        Select::make('assigned_to')
                            ->label('Assigned Staff')
                            ->options(function () {
                                return User::role(['superadmin', 'support-staff', 'community-admin', 'marketplace-moderator'])
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),
                        RichEditor::make('resolution_notes')
                            ->label('Resolution Summary Note')
                            ->columnSpanFull()
                            ->placeholder('Provide a final summary note explaining the resolution to the user...'),
                    ]),
            ]);
    }
}
