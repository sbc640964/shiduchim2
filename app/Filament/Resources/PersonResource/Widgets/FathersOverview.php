<?php

namespace App\Filament\Resources\PersonResource\Widgets;

use App\Models\Person;
use Filament\Widgets\Widget;

class FathersOverview extends Widget
{
    public ?Person $record = null;

    protected static string $view = 'filament.resources.person-resource.widgets.parent-overview';

    public function mount(Person $record): void
    {
        $this->record = $record->loadFathersAndMothers();
    }
}
