<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CalendarWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Tasks extends BaseDashboard
{
    protected static string $routePath = '/tasks';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament-panels::pages.dashboard';

    protected static ?string $slug = 'tasks';
    protected static ?string $navigationLabel = 'משימות';

    protected static ?string $title = 'משימות';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int|string|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}
