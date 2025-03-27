<?php

namespace App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets;

use App\Filament\Widgets\FilterReportsTrait;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Url;

class ProposalInfo extends Widget
{
    use FilterReportsTrait;

    #[Url]
    public ?string $activeTab = 'info';

    #[Reactive]
    public ?int $proposal = null;

    protected static string $view = 'filament.widgets.proposal-info';
}
