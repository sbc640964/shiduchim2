<?php

namespace App\Filament\Resources\PersonResource\Widgets;

use App\Models\Person;
use Filament\Widgets\Widget;

class SelfFamilyOverview extends Widget
{
    public ?Person $record = null;

    protected string $view = 'filament.resources.person-resource.widgets.self-family-overview';

    protected int|string|array $columnSpan = 2;

    public function mount(Person $record): void
    {
        $this->record = $record->loadFathersAndMothers();
    }
}
