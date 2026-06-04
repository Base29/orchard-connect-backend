<?php

namespace App\Filament\Resources\Comments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Table;
use App\Models\ModerationLog;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('content')
                    ->label('Comment')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('post.content')
                    ->label('Original Post')
                    ->limit(40)
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
                    ->visible(fn () => auth()->user()->can('moderate_comment'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
