<?php

namespace App\Filament\Widgets;

use App\Models\Person;
use App\Models\User;
use Arr;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

trait FilterReportsTrait
{
    use InteractsWithPageFilters;

    function getFilter(?string $key = null, bool $raw = false): array|string|null
    {
        return match ($key) {
            'matchmaker' => $raw ? $this->filters['matchmaker'] ?? null : Arr::wrap($this->filters['matchmaker'] ?? User::pluck('id')->toArray()),
            'dates_range' => $raw ? data_get($this->filters, 'dates_range') : (data_get($this->filters, 'dates_range') ? Arr::map(
                explode(' - ', $this->filters['dates_range'] ?? ''),
                fn ($date) => $date ? Carbon::createFromFormat('d/m/Y',$date) : null
            ) : [null, null]),
            'person' => $raw ? $this->filters['person'] ?? null : Arr::wrap($this->filters['person'] ?? Person::query()
                ->whereIn('billing_matchmaker', $this->getFilter('matchmaker'))
                ->where('billing_status', 'active')
                ->pluck('id')
                ->toArray()
            ),
            default => [
                'matchmaker' => $this->getFilter('matchmaker'),
                'dates_range' => $this->getFilter('dates_range'),
                'person' => $this->getFilter('person'),
            ],
        };
    }
}
