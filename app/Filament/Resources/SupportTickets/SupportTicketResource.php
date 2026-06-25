<?php

namespace App\Filament\Resources\SupportTickets;

use App\Filament\Resources\SupportTickets\Pages\CreateSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if (!$user) return 'Support & Operations';

        if ($user->hasRole('marketplace-moderator')) {
            return 'Marketplace & Business';
        }
        if ($user->hasRole('community-admin')) {
            return 'Community & Verification';
        }
        return 'Support & Operations';
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if (!$user) return 'Support Tickets';

        if ($user->hasRole('marketplace-moderator')) {
            return 'Dispute Tickets';
        }
        if ($user->hasRole('community-admin')) {
            return 'Support Tickets';
        }
        return 'Support Tickets';
    }

    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('superadmin')) {
            return $query;
        }

        if ($user->hasRole('support-staff')) {
            return $query->whereIn('category', ['general', 'auth_issue', 'technical']);
        }

        if ($user->hasRole('community-admin')) {
            return $query->where('category', 'security');
        }

        if ($user->hasRole('marketplace-moderator')) {
            return $query->where('category', 'marketplace_dispute');
        }

        return $query->whereRaw('1 = 0');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'create' => CreateSupportTicket::route('/create'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
