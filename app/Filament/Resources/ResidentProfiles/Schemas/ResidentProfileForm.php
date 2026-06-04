<?php

namespace App\Filament\Resources\ResidentProfiles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ResidentProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Residency Address Details')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Resident Name')
                            ->disabled(),
                        TextInput::make('phase')
                            ->label('Phase')
                            ->disabled(),
                        TextInput::make('block')
                            ->label('Block')
                            ->disabled(),
                        TextInput::make('house_number')
                            ->label('House/Plot Number')
                            ->disabled(),
                        TextInput::make('street_number')
                            ->label('Street/Lane')
                            ->disabled()
                            ->placeholder('None'),
                        TextInput::make('user_type')
                            ->label('Resident Type')
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Review Status')
                            ->disabled(),
                    ]),

                Section::make('Uploaded Verification Document')
                    ->columnSpan(1)
                    ->schema([
                        ViewField::make('document_path')
                            ->view('filament.forms.components.document-viewer')
                    ]),
            ]);
    }
}
