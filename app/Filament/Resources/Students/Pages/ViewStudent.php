<?php

namespace App\Filament\Resources\Students\Pages;

use Blade;
use App\Filament\Resources\Students\StudentResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected static ?string $navigationLabel = 'צפה בתלמיד';

    protected static string | \BackedEnum | null $navigationIcon = '';

    public function getSubheading(): string|Htmlable|null
    {
        return str(Blade::render(
            <<<'HTML'
            <div class="flex gap-1 items-center">
                <x-filament::badge color="{{ $age > 20 ? 'danger' : 'warning'}}" class="me-2">
                    {{ $record->gender === 'B' ? 'בן' : 'בת'}} {{ $age }}
                </x-filament::badge>
                <div>
                {{ $record->gender === 'B' ? 'בן' : 'בת'}}  {{ $record->father?->first_name . ($record->mother?->first_name ? ' ו'.$record->mother->first_name : '') }}
                מ{{ $record->parentsFamily?->city?->name }}
                 </div>
            </div>
            HTML,
            ['record' => $this->getRecord(), 'age' => $this->record->age]))->toHtmlString();
    }

    protected function getHeaderWidgets(): array
    {
        return [

        ];
    }
}
