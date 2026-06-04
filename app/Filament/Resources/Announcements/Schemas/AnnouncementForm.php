<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category')
                            ->options([
                                'general' => 'General',
                                'security' => 'Security',
                                'maintenance' => 'Maintenance',
                                'event' => 'Event',
                            ])
                            ->required()
                            ->default('general'),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->required()
                            ->default('published'),
                        Toggle::make('pinned')
                            ->label('Pin this notice to top of Community Board?')
                            ->default(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Content Details')
                    ->schema([
                        RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
