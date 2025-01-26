<?php

namespace App\Filament\Widgets;

use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpenProposals extends BaseWidget
{

    protected static ?int $sort = -100;

    protected int | string | array $columnSpan = 4;

    public static function canView(): bool
    {
        return auth()->user()->can('open_proposals');
    }

    protected function getStats(): array
    {
        $opens = Proposal::query()
            ->whereNotNull('opened_at')
            ->whereNull('closed_at')
            ->get();

        $finished = Proposal::query()
            ->whereBetween('finished_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        return [
            Stat::make('ההצעות הפתוחות שלך', $opens
                ->where('created_by', auth()->id())
                ->count()
            )
                ->icon('heroicon-o-lock-open'),

            Stat::make('ההצעות הפתוחות של כולם', $opens->count())
            ->icon('heroicon-o-lock-open')
            ->description("ממוצע לשדכן: " . round($opens->avg(fn($proposal) => $proposal->created_by), 2)),

            Stat::make(
                'סה"כ הצעות שסגרת החודש',
                $finished->where('created_by', auth()->user()->id)->count()
            )
                ->description("מתוך " . $finished->count())
                ->icon('heroicon-o-lock-open')
        ];
    }
}
