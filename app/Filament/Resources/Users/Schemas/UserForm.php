<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'banned' => 'Banned',
                            ])
                            ->required()
                            ->default('active'),
                        Select::make('roles')
                            ->label('Security Roles')
                            ->multiple()
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: function ($query) {
                                    $user = auth()->user();
                                    if ($user->hasRole('superadmin')) {
                                        return $query;
                                    }
                                    if ($user->hasRole('community-admin')) {
                                        return $query->whereIn('name', ['content-moderator', 'marketplace-moderator']);
                                    }
                                    return $query->where('id', null);
                                }
                            )
                            ->preload()
                            ->disabled(fn (? \App\Models\User $record) => $record && !auth()->user()->hasRole('superadmin') && ($record->hasRole('superadmin') || $record->hasRole('community-admin'))),
                        Textarea::make('avatar_url')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Section::make('Resident Profile')
                    ->relationship('residentProfile', condition: function (?array $state): bool {
                        if (!$state) {
                            return false;
                        }
                        return filled($state['phase'] ?? null) 
                            || filled($state['block'] ?? null) 
                            || filled($state['house_number'] ?? null)
                            || filled($state['street_number'] ?? null)
                            || filled($state['user_type'] ?? null);
                    })
                    ->schema([
                        Select::make('phase')
                            ->options([
                                'Phase 1' => 'Phase 1',
                                'Phase 2' => 'Phase 2',
                                'Phase 3' => 'Phase 3',
                                'Phase 4' => 'Phase 4',
                            ])
                            ->required(fn ($get, $record) => ($record?->exists) || filled($get('block')) || filled($get('house_number')) || filled($get('user_type'))),
                        TextInput::make('block')
                            ->required(fn ($get, $record) => ($record?->exists) || filled($get('phase')) || filled($get('house_number')) || filled($get('user_type')))
                            ->placeholder('e.g. Block A'),
                        TextInput::make('house_number')
                            ->required(fn ($get, $record) => ($record?->exists) || filled($get('phase')) || filled($get('block')) || filled($get('user_type')))
                            ->placeholder('e.g. 124-B'),
                        TextInput::make('street_number')
                            ->placeholder('e.g. Street 4'),
                        Select::make('user_type')
                            ->label('Resident Type')
                            ->options([
                                'owner' => 'Owner',
                                'tenant' => 'Tenant',
                                'visitor' => 'Visitor',
                            ])
                            ->required(fn ($get, $record) => ($record?->exists) || filled($get('phase')) || filled($get('block')) || filled($get('house_number'))),
                        Toggle::make('is_verified')
                            ->label('Verified Community Resident?'),
                    ]),
            ]);
    }
}
