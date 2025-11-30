<?php

namespace App\Filament\Exports;

use App\Models\Person;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class StudentExporter extends Exporter
{
    protected static ?string $model = Person::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query
            ->whereNotNull('external_code_students')
            ->with([
                'parentsFamily.people.parentsFamily.people',
                'parentsFamily.people.school',
                'family.people.parentsFamily.people',
                'schools',
                'city',
            ]);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('first_name')->label('שם פרטי'),
            ExportColumn::make('last_name')->label('שם משפחה'),
            ExportColumn::make('external_code_students')->label('קוד תלמיד'),

            ExportColumn::make('father_name')
                ->label('שם האב')
                ->state(fn (Person $student) => $student->parentsFamily?->husband?->first_name ?? null),

            ExportColumn::make('father_external_code')
                ->label('קוד איחוד - אב')
                ->state(fn (Person $student) => $student->parentsFamily?->husband?->external_code ?? null),

            ExportColumn::make('mother_name')
                ->label('שם האם')
                ->state(fn (Person $student) => $student->parentsFamily?->wife?->first_name ?? null),

            ExportColumn::make('mother_father_name')
                ->label('שם אבי אם')
                ->state(fn (Person $student) => $student->parentsFamily?->wife?->parentsFamily?->husband?->full_name ?? null),

            ExportColumn::make('mother_father_external_code')
                ->label('קוד איחוד - אבי אם')
                ->state(fn (Person $student) => $student->parentsFamily?->wife?->parentsFamily?->husband?->external_code ?? null),

            ExportColumn::make('schools')
                ->label('מוסדות')
                ->state(fn (Person $student) => $student->schools->pluck('name')->join(', ')),

            ExportColumn::make('city')
                ->label('עיר')
                ->state(fn (Person $student) => $student->city->name ?? null),

            ExportColumn::make('address')->label('כתובת'),

            ExportColumn::make('class')
                ->label('כיתה')
                ->state(fn (Person $student) => $student->data_raw['class'] ?? null),

            ExportColumn::make('synagogue')
                ->label('בית כנסת')
                ->state(fn (Person $student) => $student->parentsFamily?->husband?->school?->first()?->name ?? null),

            ExportColumn::make('born_at')
                ->label('תאריך לידה (לועזי)')
                ->state(fn (Person $student) => $student->born_at?->format('Y-m-d') ?? null),

            ExportColumn::make('born_at_heb')
                ->label('תאריך לידה (עברי)')
                ->state(fn (Person $student) => $student->born_at ? optional($student->born_at->hebcal())->hebrewDate(true) : null),

            ExportColumn::make('gender')
                ->label('מין')
                ->state(fn (Person $student) => $student->gender === 'B' ? 'בת' : 'בן'),

            ExportColumn::make('is_married')
                ->state(fn (Person $student) => $student->family ? 'כן' : 'לא'),

            ExportColumn::make('married_to')
                ->label('בן/בת זוג')
                ->state(function (Person $student) {
                    $marriedTo = $student->family->{$student->gender === 'B' ? 'wife' : 'husband'} ?? null;

                    if (! $marriedTo) {
                        return null;
                    }

                    $marriedToLabel = $marriedTo->gender === 'B' ? 'בת' : 'בן';

                    $parentsFamily = $marriedTo->parentsFamily?->husband?->full_name ?? '';

                    return $marriedTo->first_name
                        . ' (' . ($marriedTo->external_code_students ?? '') . ') '
                        . $marriedToLabel
                        . ($parentsFamily ? ' ' . $parentsFamily : '');
                }),
            ExportColumn::make('proposals_count')
                ->label('מספר הצעות')
                ->counts('proposals')
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your student export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
