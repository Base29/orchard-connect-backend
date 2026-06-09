<?php

namespace App\Filament\Resources\Polls;

use App\Filament\Resources\Polls\Pages\ManagePolls;
use App\Models\Poll;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Models\ModerationLog;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Community & Verification';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Creator')
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                DateTimePicker::make('start_at')
                    ->required()
                    ->timezone('Asia/Karachi')
                    ->label('Start At'),
                DateTimePicker::make('end_at')
                    ->required()
                    ->timezone('Asia/Karachi')
                    ->label('End At'),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'closed' => 'Closed',
                    ])
                    ->required()
                    ->default('active'),
                Repeater::make('options')
                    ->relationship('options')
                    ->schema([
                        TextInput::make('option_text')
                            ->required()
                            ->maxLength(150)
                            ->label('Option Text'),
                    ])
                    ->minItems(2)
                    ->maxItems(10)
                    ->label('Poll Choices / Options')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('Creator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(function (Poll $record): string {
                        if ($record->status === 'suspended') {
                            return 'suspended';
                        }
                        $now = now();
                        if ($record->start_at > $now) {
                            return 'scheduled';
                        }
                        if ($record->end_at < $now) {
                            return 'closed';
                        }
                        return 'active';
                    })
                    ->colors([
                        'success' => 'active',
                        'warning' => 'scheduled',
                        'danger' => 'suspended',
                        'neutral' => 'closed',
                    ])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('votes_count')
                    ->label('Votes')
                    ->counts('votes')
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label('Start At')
                    ->dateTime()
                    ->timezone('Asia/Karachi')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('End At')
                    ->dateTime()
                    ->timezone('Asia/Karachi')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),

                Action::make('suspend')
                    ->label('Suspend / Stop')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(function ($record) {
                        $oldStatus = $record->status;
                        $record->update(['status' => 'suspended']);

                        ModerationLog::create([
                            'action' => 'suspend_poll',
                            'target_type' => get_class($record),
                            'target_id' => $record->id,
                            'moderator_id' => auth()->id(),
                            'reason' => 'Stopped/Suspended by administrator via Filament panel.',
                            'metadata' => json_encode([
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

    public static function getPages(): array
    {
        return [
            'index' => ManagePolls::route('/'),
        ];
    }
}
