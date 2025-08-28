<?php

namespace App\Filament\Pages;

//use App\Filament\Widgets\CalendarWidget;
use App\Filament\Widgets\NewCalenderWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Tasks extends BaseDashboard
{
    protected static string $routePath = '/tasks';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $slug = 'tasks';
    protected static ?string $navigationLabel = 'משימות';

    protected static ?string $title = 'משימות';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            NewCalenderWidget::class,
        ];
    }
}
