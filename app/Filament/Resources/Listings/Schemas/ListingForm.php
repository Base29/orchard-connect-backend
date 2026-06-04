<?php

namespace App\Filament\Resources\Listings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Seller')
                    ->required()
                    ->searchable(),
                TextInput::make('title')
                    ->required(),
                Select::make('category')
                    ->options([
                        'Electronics' => 'Electronics',
                        'Vehicles' => 'Vehicles',
                        'Property' => 'Property',
                        'Services' => 'Services',
                        'Furniture' => 'Furniture',
                        'Other' => 'Other',
                    ])
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->prefix('PKR'),
                TextInput::make('contact_whatsapp')
                    ->label('WhatsApp Number')
                    ->placeholder('e.g. +923001234567')
                    ->required(),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Under Review',
                        'sold' => 'Sold',
                        'flagged' => 'Flagged',
                        'suspended' => 'Suspended',
                    ])
                    ->required()
                    ->default('active'),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->rows(4),
            ]);
    }
}
