<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Actions\Action;
use \Guava\Calendar\Widgets\CalendarWidget as BaseCalendarWidget;
use Illuminate\Support\Collection;

class NewCalenderWidget extends BaseCalendarWidget
{
    public static function canView(): bool
    {
        return false;
    }

    public $showCompletedTasks = false;

    public function getEvents(array $fetchInfo = []): array|Collection
    {
        return Task::query()
            ->when(! $this->showCompletedTasks, fn ($query) => $query->whereNull('completed_at'))
            ->whereBetween('due_date', [$fetchInfo['start'], $fetchInfo['end']])
            ->where('user_id', auth()->id())
            ->with('proposal.people')
            ->get();
    }


    public function getEventContent(): null|string|array
    {
        return str(
            <<<'Html'
            <div class="flex items-center space-x-2">
                <div class="flex-1" :class="{'opacity-80 bg-success-100': event.extendedProps.completed_at}">
                    <div class="text-xs font-semibold text-gray-500" x-text="event.extendedProps.proposal_names"></div>
                    <div class="text-xs" x-html="event.title.replace(/\n/g, '<br>')"></div>
                    <div class="text-xs text-gray-500"></div>
                </div>
            </div>
Html

        )->toHtmlString();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('toggleCompletedTasks')
                ->label('הצג משימות שהושלמו')
                ->iconButton()
                ->tooltip($this->showCompletedTasks ? 'הסתר משימות שהושלמו' : 'הצג משימות שהושלמו')
                ->icon($this->showCompletedTasks ? 'heroicon-o-eye-slash' :'heroicon-o-eye')
                ->action(function () {
                    $this->showCompletedTasks = ! $this->showCompletedTasks;

                    $this->refreshRecords();
                }),
        ];
    }
}
