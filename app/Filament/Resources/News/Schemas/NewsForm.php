<?php

namespace App\Filament\Resources\News\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Headline / Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->required()
                            ->default('published'),
                        FileUpload::make('image_path')
                            ->label('Featured Image')
                            ->image()
                            ->maxFiles(1)
                            ->disk(empty(config('filesystems.disks.s3.key')) || empty(config('filesystems.disks.s3.bucket')) ? 'public' : 's3')
                            ->directory(fn () => 'news/' . auth()->id())
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                return time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                            }),
                    ]),

                Section::make('News Content')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Article Body')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
