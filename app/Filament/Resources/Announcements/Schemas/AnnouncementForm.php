<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
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
                                'suspended' => 'Suspended',
                                'archived' => 'Archived',
                            ])
                            ->required()
                            ->default('published'),
                        FileUpload::make('image_path')
                            ->label('Announcement Image')
                            ->image()
                            ->maxFiles(1)
                            ->disk(empty(config('filesystems.disks.s3.key')) || empty(config('filesystems.disks.s3.bucket')) ? 'public' : 's3')
                            ->visibility('private')
                            ->directory(fn () => 'announcements/' . auth()->id())
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
                            ->columnSpanFull(),
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
