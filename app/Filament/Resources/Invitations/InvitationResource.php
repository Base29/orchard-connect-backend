<?php

namespace App\Filament\Resources\Invitations;

use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Models\Invitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    public static function canAccessNavigation(): bool
    {
        return auth()->user()?->hasRole('superadmin') || false;
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Security & Audit';

    protected static ?string $navigationLabel = 'Invitations';

    protected static ?string $modelLabel = 'Invitation';

    protected static ?string $pluralModelLabel = 'Invitations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('invitedBy.name')
                    ->label('Invited By (Admin)')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('registeredUser.name')
                    ->label('Registered Resident')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Pending Registration')
                    ->default('-'),
                TextColumn::make('registeredUser.email')
                    ->label('Resident Email')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitations::route('/'),
        ];
    }
}
