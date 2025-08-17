<?php

namespace App\Filament\Resources\Proposals\Widgets;

use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CallOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    public ?Proposal $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('שיחות', $this->record
                ->diaries()
                ->when(
                    ! auth()->user()->canAccessAllTimeSheets(),
                    fn ($query) => $query->where('created_by', auth()->id())
                )
                ->whereNotNull('data->call_id')
                ->distinct('data->call_id')
                ->count()
            )
                ->icon('heroicon-o-phone')
//                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->description('כמות השיחות שביצעת עבור ההצעה'),

            Stat::make('זמן שיחה', gmdate('G:i:s',
                    $this->record
                        ->diaries()
                        ->when(
                            ! auth()->user()->canAccessAllTimeSheets(),
                            fn ($query) => $query->where('created_by', auth()->id())
                        )
                        ->whereNotNull('data->call_id')
                        ->sum('data->duration')
                )
            )
                ->icon('heroicon-o-clock')
                ->description('סך הזמן שביצעת שיחות עבור ההצעה'),
        ];
    }
}
