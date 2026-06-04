<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use App\Models\ModerationLog;

class PostsTable
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
                    ->label('Content')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'published',
                        'warning' => 'flagged',
                        'danger' => 'moderated',
                        'neutral' => 'hidden',
                    ])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('flags_count')
                    ->label('Flags')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Posted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('flagged_only')
                    ->label('Flagged / High Risk Only')
                    ->query(fn ($query) => $query->where('flags_count', '>', 0)->orWhere('status', 'flagged')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                EditAction::make(),

                Action::make('approve')
                    ->label('Resolve / Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->can('moderate_post') && ($record->flags_count > 0 || $record->status === 'flagged'))
                    ->action(function ($record) {
                        $oldFlags = $record->flags_count;
                        $oldStatus = $record->status;
                        
                        $record->update([
                            'status' => 'published',
                            'flags_count' => 0,
                        ]);

                        ModerationLog::create([
                            'action' => 'approve_post',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Post flags cleared and content approved.',
                            'metadata' => json_encode([
                                'previous_flags_count' => $oldFlags,
                                'previous_status' => $oldStatus,
                            ]),
                        ]);
                    }),

                Action::make('moderate')
                    ->label('Moderate / Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()->can('moderate_post') && $record->status !== 'moderated')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Reason for Moderation/Deletion')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldContent = $record->content;
                        $oldStatus = $record->status;

                        $record->update([
                            'content' => '[Content removed by moderator]',
                            'status' => 'moderated',
                            'flags_count' => 0,
                        ]);

                        ModerationLog::create([
                            'action' => 'delete_post',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => $data['reason'],
                            'metadata' => json_encode([
                                'previous_content' => $oldContent,
                                'previous_status' => $oldStatus,
                            ]),
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
