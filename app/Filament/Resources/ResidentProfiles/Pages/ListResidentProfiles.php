<?php

namespace App\Filament\Resources\ResidentProfiles\Pages;

use App\Filament\Resources\ResidentProfiles\ResidentProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListResidentProfiles extends ListRecords
{
    protected static string $resource = ResidentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending Reviews')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')->whereNotNull('document_path')),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            'all' => Tab::make('All'),
        ];
    }
}
