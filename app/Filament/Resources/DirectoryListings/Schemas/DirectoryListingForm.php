<?php

namespace App\Filament\Resources\DirectoryListings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DirectoryListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->placeholder('e.g. Al-Fatah Supermarket'),
                TextInput::make('contact_phone')
                    ->label('Contact Phone')
                    ->tel()
                    ->placeholder('e.g. +9242111123456'),
                TextInput::make('whatsapp')
                    ->label('WhatsApp Number')
                    ->placeholder('e.g. +923001234567'),
                Textarea::make('address')
                    ->placeholder('e.g. Commercial Area, Phase 1, Bahria Orchard')
                    ->columnSpanFull()
                    ->rows(2),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->rows(3),
                Textarea::make('logo_url')
                    ->label('Logo Image URL')
                    ->columnSpanFull()
                    ->rows(2),
                Toggle::make('is_verified')
                    ->label('Verified Business?')
                    ->default(false),
            ]);
    }
}
