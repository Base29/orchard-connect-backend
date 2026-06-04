<?php

namespace App\Filament\Resources\DirectoryCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DirectoryCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, \Filament\Forms\Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('icon')
                    ->placeholder('e.g. heroicon-o-home'),
            ]);
    }
}
