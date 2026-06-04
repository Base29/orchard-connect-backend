<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Author')
                    ->required(),
                Select::make('status')
                    ->options([
                        'published' => 'Published',
                        'flagged' => 'Flagged',
                        'hidden' => 'Hidden',
                        'moderated' => 'Moderated',
                    ])
                    ->required()
                    ->default('published'),
                TextInput::make('flags_count')
                    ->label('Flags Count')
                    ->numeric()
                    ->required()
                    ->default(0),
                Textarea::make('content')
                    ->label('Post Content')
                    ->required()
                    ->columnSpanFull()
                    ->rows(4),
            ]);
    }
}
