<?php

namespace App\Models\Traits;

use App\Filament\Resources\PersonResource;
use App\Infolists\Components\FamilyTable;
use App\Models\Person;
use App\Models\Proposal;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;

trait HasPersonFilamentTableColumns
{
    public static function nameColumn($fullName = true, $reverse = true, $withSpouse = true, ?string $prefix = ''): TextColumn
    {
        $attribute = $fullName
            ? ($reverse ? 'reverse_full_name' : 'full_name')
            : 'first_name';

        return TextColumn::make($prefix.$attribute)
            ->label('שם')
            ->extraAttributes(['class' => 'gap-y-0 font-medium [&_p]:font-[400] [&_p]:text-xs'])
            ->when($fullName ?? false, function ($column) {
                $column
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']);
            })
            ->when($withSpouse && ! $prefix, function ($column) {
                $column->description(function (Person $record) {
                    if ($record->spouse) {
                        return $record->spouse_info;
                    }

                    return null;
                });
            });
    }

    public static function baseColumns(?array $only = null, null|\Closure|array $callback = null): array
    {
        $columns = collect([
            'father' => TextColumn::make('father.first_name')
                ->label('שם האב')
                ->sortable()
                ->searchable(),

            'father_in_law' => TextColumn::make('fatherInLaw.full_name')
                ->label('שם חותן')
                ->sortable(['first_name', 'last_name'])
                ->searchable(['first_name', 'last_name']),

            'address' => TextColumn::make('address')
                ->label('כתובת')
                ->sortable()
                ->searchable(),

            'city' => TextColumn::make('city.name')
                ->label('עיר')
                ->sortable()
                ->searchable(),
        ]);

        if ($only && count($only)) {
            $columns = $columns->only($only);
        }

        if (is_callable($callback)) {
            $columns->each($callback);
        } elseif (is_array($callback)) {
            $columns->each(fn ($column, $key) => (is_callable($callback[$key] ?? false) && $callback[$key]($column)));
        }

        return $columns->values()->toArray();
    }

    public static function childrenColumn(?Proposal $proposal = null, ?string $side = null): TextColumn
    {
        return TextColumn::make('families')
            ->formatStateUsing(fn (Person $record) => $record->families?->sum('children_count') ?? 0)
            ->badge()
            ->alignCenter()
            ->color('gray')
            ->label('מספר ילדים')
            ->action(Action::make('children')
                ->label('פרטים')
                ->modalHeading(fn ($record) => "ילדי $record->full_name ({$record->children->count()})")
                ->infolist(function (Infolist $infolist) use ($side, $proposal) {
                    return $infolist
                        ->schema(fn (Person $record) => [
                            FamilyTable::make('children')
                                ->viewData($proposal ? [
                                    'person' => $record,
                                    'proposal' => $proposal,
                                    'side' => $side,
                                ]: [
                                    'person' => $record,
                                ])
                                ->label('ילדי המשפחה'),
                        ]);
                })
                ->slideOver()
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->icon('heroicon-o-chevron-right')
                ->action(function () {
                })
            )
            ->sortable();
    }
}
