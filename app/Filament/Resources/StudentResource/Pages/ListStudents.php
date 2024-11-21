<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\StudentResource;
use App\Jobs\ImportCsv;
use App\Models\Person;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                ImportAction::make('import-students')
                    ->importer(StudentImporter::class)
                    ->job(ImportCsv::class)
                    ->label('ייבוא תלמידים'),
            ]),
        ];
    }

    public function getTabs(): array
    {
        return array_merge([
            'all' => Tab::make()->label('כל התלמידים'),
            'gold_list' => Tab::make()->label('רשימת הזהב שלי')
                ->modifyQueryUsing(function ($query) {
                    return $query->where('billing_matchmaker', auth()->user()->id)
                        ->where('billing_status', 'active');
                }),
        ], auth()->user()->can('students_subscriptions') ? [
            'subscriptions' => Tab::make()->label('מנויים')
                ->modifyQueryUsing(function ($query) {
                    return $query->whereNotNull('billing_status');
                }),
        ] : [
            'subscriptions_pending' => Tab::make()
                ->label('מנויים ממתינים')
                ->modifyQueryUsing(function ($query) {
                    return $query->whereNull('billing_matchmaker')
                        ->where('billing_published', true);
                }),
        ]);
    }

    public function getExtraColumns(): array
    {
            return [
                TextColumn::make('billing_status')
                    ->badge()
                    ->visible(fn () => $this->activeTab === 'subscriptions')
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'hold' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match (\Str::trim($state)) {
                        'active' => 'פעיל',
                        'hold' => 'מושהה',
                        'pending' => 'ממתין',
                        default => $state,
                    })
                    ->label('סטטוס'),
                ToggleColumn::make('billing_published')
                    ->label('פרסום')
                    ->sortable()
                    ->getStateUsing(fn (Person $record) => \Str::trim($record->billing_status) === 'pending' ? $record->billing_published : null)
                    ->disabled(fn (Person $record) => \Str::trim($record->billing_status) !== 'pending')
                    ->visible(fn () => $this->activeTab === 'subscriptions'),
                TextColumn::make('billingMatchmaker.name')
                    ->label('שדכן')
                    ->visible(fn () => $this->activeTab === 'subscriptions')
                    ->description(fn (Person $record) => 'יום: '.match ($record->billing_matchmaker_day) {
                            1 => 'ראשון',
                            2 => 'שני',
                            3 => 'שלישי',
                            4 => 'רביעי',
                            5 => 'חמישי',
                            6 => 'שישי',
                            7 => 'מוצ"ש',
                            default => $record->billing_matchmaker_day,
                        }),
                TextColumn::make('billing_amount')
                    ->label('סכום')
                    ->money('ILS')
                    ->description(fn (Person $record) => $record->billing_balance_times ? "נותרו $record->billing_balance_times חודשים" : null)
                    ->visible(fn () => $this->activeTab === 'subscriptions'),

                TextColumn::make('billing_next_date')
                    ->label('תאריך תשלום הבא')
                    ->date('d/m/Y')
                    ->visible(fn () => $this->activeTab === 'subscriptions'),

                TextColumn::make('lastDiary.created_at')
                    ->sortable()
                    ->description(fn (Person $record) => $record->lastDiary ? $record->lastDiary->created_at->diffForHumans() : null)
                    ->tooltip(fn (Person $record) => $record->lastDiary ? $record->lastDiary->label_type.': '.$record->lastDiary->data['description'] : null)
                    ->label('פעילות אחרונה')
                    ->date('d/m/Y')
                    ->visible(fn () => $this->activeTab === 'subscriptions'),

                TextColumn::make('billingReferrer.full_name')
                    ->label('מפנה')
                    ->visible(fn () => $this->activeTab === 'subscriptions'),

                TextColumn::make('subscriptions')
                    ->label('תקופת פרוייקט')
                    ->formatStateUsing(function (Person $record) {
                        $start = $record->subscriptions->where('credit_card_id', $record->billing_credit_card_id)->first()->created_at->format('m/Y');
                        $end = $record->billing_next_date->copy()->addMonths($record->billing_balance_times - 1)->format('m/Y');

                        return "$start עד $end";
                    })
                    ->visible(fn () => $this->activeTab === 'subscriptions'),
            ];
    }

    //    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    //    {
    //        return $query->fastPaginate($this->getTableRecordsPerPage());
    //    }
}
