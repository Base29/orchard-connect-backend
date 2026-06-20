<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\LogParser;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class LogDetail extends Page
{
    protected static ?string $slug = 'logs/{index}';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Log Details';
    protected string $view = 'filament.pages.log-detail';

    public int $logIndex;
    public ?array $log = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to System Logs')
                ->color('gray')
                ->url(LogViewer::getUrl()),
                
            Action::make('deleteLog')
                ->label('Delete Log Entry')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Log Entry')
                ->modalDescription('Are you sure you want to delete this log entry?')
                ->action(fn () => $this->deleteLog()),
        ];
    }

    public function mount(int $index): void
    {
        if (!auth()->user()?->hasRole('superadmin')) {
            abort(403);
        }

        $this->logIndex = $index;
        $filePath = storage_path('logs/laravel.log');
        $allEntries = LogParser::parseLogsLazy($filePath);

        // Find the log with this index
        foreach ($allEntries as $entry) {
            if ($entry['index'] === $index) {
                $this->log = $entry;
                break;
            }
        }

        if (!$this->log) {
            Notification::make()
                ->title('Log entry not found')
                ->danger()
                ->send();
            
            $this->redirect(LogViewer::getUrl());
        }
    }

    public function deleteLog(): void
    {
        if (!auth()->user()?->hasRole('superadmin')) {
            abort(403);
        }

        $filePath = storage_path('logs/laravel.log');
        $success = LogParser::deleteEntry($filePath, $this->logIndex);

        if ($success) {
            Notification::make()
                ->title('Log entry deleted')
                ->success()
                ->send();
            
            $this->redirect(LogViewer::getUrl());
        } else {
            Notification::make()
                ->title('Failed to delete log entry')
                ->danger()
                ->send();
        }
    }
}
