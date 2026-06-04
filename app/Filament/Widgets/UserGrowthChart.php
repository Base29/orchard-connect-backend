<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class UserGrowthChart extends ChartWidget
{
    protected ?string $heading = 'Resident Registrations';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $fifteenDaysAgo = Carbon::now()->subDays(14)->startOfDay();
        $usersByDay = User::where('created_at', '>=', $fifteenDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $data = [];
        $labels = [];

        for ($i = 14; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            
            // Count users created on this specific day from our pre-fetched collection
            $data[] = $usersByDay->get($date->toDateString(), 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Registrations',
                    'data' => $data,
                    'borderColor' => '#10b981', // Emerald-500 matching brand style
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4, // Beautiful smooth bezier curve
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
