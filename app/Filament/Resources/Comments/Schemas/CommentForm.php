<?php

namespace App\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('post_id')
                    ->relationship('post', 'content')
                    ->label('Post')
                    ->required()
                    ->searchable()
                    ->limit(50),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Author')
                    ->required()
                    ->searchable(),
                Select::make('parent_id')
                    ->relationship('parent', 'content')
                    ->label('Parent Comment (Reply To)')
                    ->placeholder('None (Top-Level Comment)')
                    ->searchable()
                    ->nullable(),
                Textarea::make('content')
                    ->label('Comment Content')
                    ->required()
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }
}
