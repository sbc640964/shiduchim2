<?php

namespace App\Filament\Widgets;

use App\Models\Person;
use App\Models\Subscriber;
use App\Models\User;
use Arr;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

trait FilterReportsTrait
{
    use InteractsWithPageFilters;

    function getFilter(?string $key = null, bool $raw = false): Subscriber|array|string|null
    {
        if(!$key) {
            return [
                'matchmaker' => $this->getFilter('matchmaker'),
                'dates_range' => $this->getFilter('dates_range'),
                'person' => $this->getFilter('person'),
                'subscription' => $this->getFilter('subscription'),
            ];
        }

        $sessionKey = 'filters_reports.'.$key.($raw ? '.raw' : '.normalized');

        if(!session()->has($sessionKey)) {
            session()->flash(
                $sessionKey,
                match ($key) {
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
                    'subscription' => filled($this->filters['subscription'] ?? null)
                        ? (
                        $raw
                            ? $this->filters['subscription']
                            : Subscriber::find($this->filters['subscription'])
                        ) : null,
                    default => null,
                }
            );
        }

        return session($sessionKey);
    }
}
