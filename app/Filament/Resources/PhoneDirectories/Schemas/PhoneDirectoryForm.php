<?php

namespace App\Filament\Resources\PhoneDirectories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PhoneDirectoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Security Control Room'),
                TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->required()
                    ->tel()
                    ->placeholder('e.g. +92 42 111 000 111'),
                Select::make('category')
                    ->options([
                        'Emergency & Health' => 'Emergency & Health',
                        'Security' => 'Security',
                        'Utilities' => 'Utilities',
                        'Administration' => 'Administration',
                    ])
                    ->required()
                    ->default('Utilities'),
                TextInput::make('order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->placeholder('e.g. 1'),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->rows(3)
                    ->placeholder('Enter a brief description of this contact number...'),
            ]);
    }
}
