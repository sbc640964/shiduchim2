<?php

namespace App\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Contracts\View\View;

class FamilyTable extends Entry
{
    protected string $view = 'infolists.components.family-table';

    public function getViewData(): array
    {
        return $this->viewData;
    }
}
