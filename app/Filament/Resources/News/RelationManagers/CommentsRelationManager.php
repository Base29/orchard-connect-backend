<?php

namespace App\Filament\Resources\News\RelationManagers;

use App\Models\ModerationLog;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'News Comments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('content')
                    ->label('Comment Content')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('content')
                    ->label('Comment')
                    ->limit(65)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                EditAction::make(),

                Action::make('moderate')
                    ->label('Moderate / Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn () => auth()->user()->can('moderate-comments'))
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason for Deleting Comment')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldContent = $record->content;

                        // Create moderation log entry
                        ModerationLog::create([
                            'action' => 'delete_comment',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => $data['reason'],
                            'metadata' => json_encode([
                                'previous_content' => $oldContent,
                            ]),
                        ]);

                        // Delete the comment
                        $record->delete();
                    }),
            ]);
    }
}
