<?php

namespace App\Models\Traits;

use View;
use Filament\Forms\Components\Select;
use App\Filament\Clusters\Settings\Pages\Statuses;
use Filament\Forms;

trait HasProposalFilamentFormsFields
{
    public function statusField(?bool $all = false, ?string $name = null)
    {
        $statuses = ($all
            ? Statuses::getProposalStatuses()
            : $this->allowedBeDefinedStatuses(null, true)
        )
            ->mapWithKeys(function ($status) {
                return [
                    $status['name'] => View::make('components.status-option-in-select', [
                        'status' => $status,
                    ])->render(),
                ];
            })
            ->toArray();

        return Select::make($name ?? 'statuses.proposal')
            ->options($statuses)
            ->default($this->status)
            ->searchValues()
            ->searchable()
            ->allowHtml()
            ->label('סטטוס הצעה');
    }

    public function itemStatusField($side)
    {
        return Select::make("statuses.$side")
            ->label($side === 'girl' ? 'סטטוס בחורה' : 'סטטוס בחור')
            ->options($this->allowedBeDefinedStatuses($side, true)
                ->mapWithKeys(function ($status) {
                    return [
                        $status['name'] => View::make('components.status-option-in-select', [
                            'status' => $status,
                        ])->render(),
                    ];
                })
            )
            ->hidden(! $side)
            ->default(fn () => $this->{'status_'.$side})
            ->searchValues()
            ->searchable()
            ->allowHtml();
    }
}
