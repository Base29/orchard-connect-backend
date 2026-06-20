<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\LogParser;
use Livewire\WithPagination;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class LogViewer extends Page
{
    use WithPagination;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'System Logs';
    protected static ?string $title = 'System Logs';
    protected static ?string $slug = 'logs';

    protected string $view = 'filament.pages.log-viewer';

    public ?string $search = '';
    public ?string $level = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearAll')
                ->label('Clear All Logs')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clear All Logs')
                ->modalDescription('Are you sure you want to empty the laravel.log file?')
                ->action(fn () => $this->clearAll()),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLevel(): void
    {
        $this->resetPage();
    }

    public function clearAll(): void
    {
        if (!auth()->user()?->hasRole('superadmin')) {
            abort(403);
        }

        $filePath = storage_path('logs/laravel.log');
        if (file_exists($filePath)) {
            file_put_contents($filePath, '');
            Notification::make()
                ->title('Logs cleared successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Log file does not exist')
                ->warning()
                ->send();
        }
        $this->resetPage();
    }

    public function deleteLog(int $index): void
    {
        if (!auth()->user()?->hasRole('superadmin')) {
            abort(403);
        }

        $filePath = storage_path('logs/laravel.log');
        $success = LogParser::deleteEntry($filePath, $index);

        if ($success) {
            Notification::make()
                ->title('Log entry deleted')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to delete log entry')
                ->danger()
                ->send();
        }
    }

    public function getViewData(): array
    {
        $filePath = storage_path('logs/laravel.log');
        $allEntries = LogParser::parseLogsLazy($filePath);

        // Apply filters
        $filtered = array_filter($allEntries, function ($entry) {
            $matchesSearch = true;
            $matchesLevel = true;

            if (!empty($this->search)) {
                $searchLower = strtolower($this->search);
                $matchesSearch = str_contains(strtolower($entry['message']), $searchLower) || 
                                 str_contains(strtolower($entry['stack']), $searchLower) || 
                                 str_contains(strtolower($entry['timestamp']), $searchLower);
            }

            if (!empty($this->level)) {
                $matchesLevel = $entry['level'] === $this->level;
            }

            return $matchesSearch && $matchesLevel;
        });

        // We want the newest logs first
        $filtered = array_reverse($filtered);

        // Paginate manually
        $perPage = 20;
        $total = count($filtered);
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $perPage;
        
        $items = array_slice($filtered, $offset, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return [
            'logs' => $paginator,
            'levels' => ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
        ];
    }
}
