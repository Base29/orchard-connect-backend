<?php

namespace App\Filament\Resources\DirectoryListings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
                Select::make('logo_source')
                    ->label('Logo Option')
                    ->options([
                        'url' => 'Paste Logo URL',
                        'upload' => 'Upload Logo Image',
                    ])
                    ->placeholder('Select an option')
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, $set, $record) {
                        if ($record && $record->getRawOriginal('logo_url')) {
                            $rawLogo = $record->getRawOriginal('logo_url');
                            if (filter_var($rawLogo, FILTER_VALIDATE_URL)) {
                                $set('logo_source', 'url');
                            } else {
                                $set('logo_source', 'upload');
                            }
                        }
                    }),
                TextInput::make('logo_paste_url')
                    ->label('Logo Image URL')
                    ->placeholder('e.g. https://example.com/logo.png')
                    ->visible(fn ($get) => $get('logo_source') === 'url')
                    ->live(onBlur: true)
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, $set, $record) {
                        if ($record && $record->getRawOriginal('logo_url')) {
                            $rawLogo = $record->getRawOriginal('logo_url');
                            if (filter_var($rawLogo, FILTER_VALIDATE_URL)) {
                                $set('logo_paste_url', $rawLogo);
                            }
                        }
                    }),
                FileUpload::make('logo_upload')
                    ->label('Upload Logo Image')
                    ->image()
                    ->maxFiles(1)
                    ->disk(empty(config('filesystems.disks.s3.key')) || empty(config('filesystems.disks.s3.bucket')) ? 'public' : 's3')
                    ->visibility('public')
                    ->directory('directory/logos')
                    ->visible(fn ($get) => $get('logo_source') === 'upload')
                    ->live()
                    ->dehydrated(false)
                    ->getUploadedFileNameForStorageUsing(function ($file) {
                        return time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    })
                    ->getUploadedFileUsing(static function (\Filament\Forms\Components\FileUpload $component, string $file, $storedFileNames): ?array {
                        $storage = $component->getDisk();
                        $shouldFetchFileInformation = $component->shouldFetchFileInformation();
                        try {
                            if (!$storage->exists($file)) {
                                return null;
                            }
                        } catch (\Exception $e) {
                            return null;
                        }

                        $url = $component->getDiskName() === 's3' 
                            ? route('admin.s3.preview', ['path' => $file]) 
                            : $storage->url($file);

                        return [
                            'name' => basename($file),
                            'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
                            'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
                            'url' => $url,
                        ];
                    })
                    ->afterStateHydrated(function ($state, $set, $record) {
                        if ($record && $record->getRawOriginal('logo_url')) {
                            $rawLogo = $record->getRawOriginal('logo_url');
                            if (!filter_var($rawLogo, FILTER_VALIDATE_URL)) {
                                $set('logo_upload', $rawLogo);
                            }
                        }
                    }),
                Hidden::make('logo_url')
                    ->dehydrated(true)
                    ->dehydrateStateUsing(function ($state, $get) {
                        if ($get('logo_source') === 'url') {
                            return $get('logo_paste_url');
                        }

                        $upload = $get('logo_upload');
                        if (is_array($upload)) {
                            return reset($upload) ?: null;
                        }
                        return $upload;
                    }),
                Toggle::make('is_verified')
                    ->label('Verified Business?')
                    ->default(false),
            ]);
    }
}
