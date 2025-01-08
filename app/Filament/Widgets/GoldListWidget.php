<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StudentResource;
use App\Models\Person;
use Filament\Support\Colors\Color;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\TableWidget as BaseWidget;

class GoldListWidget extends BaseWidget
{
    protected static ?int $sort = -3;

    protected static ?string $heading = 'רשימת הזהב שלך';
    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('אין לך תלמידים ברשימת הזהב')
            ->emptyStateIcon('heroicon-o-list-bullet')
            ->emptyStateDescription('המנהל עדיין לא ייחד לך תלמידים')
            ->query(
                StudentResource::getEloquentQuery()
                    ->where('billing_matchmaker', auth()->user()->id)
                    ->where('billing_status', '!=', 'married')
            )
            ->recordUrl(fn ($record) => StudentResource::getUrl('proposals', [
                'record' => $record->id,
            ]))
            ->recordClasses(fn (Person $record) => $record->billing_matchmaker_day === (now()->weekday() + 1) ? 'bg-green-50' : '')
            ->columns([
                Columns\TextColumn::make('billing_matchmaker_day')
                    ->label('יום')
                    ->badge()
                    ->color(fn (Person $record) => $record->billing_matchmaker_day === (now()->weekday() + 1) ? 'success' : Color::Sky)
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            1 => 'ראשון',
                            2 => 'שני',
                            3 => 'שלישי',
                            4 => 'רביעי',
                            5 => 'חמישי',
                            6 => 'שישי',
                            7 => 'מוצ"ש',
                            default => $state,
                        };
                    })
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('full_name')
                    ->label('שם מלא')
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['last_name', 'first_name']),

            ]);
    }
}
