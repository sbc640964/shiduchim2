<?php

namespace App\Filament\Resources\Students\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use Str;
use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\GoldLists\GoldListResource;
use App\Filament\Resources\Students\StudentResource;
use App\Jobs\ImportCsv;
use App\Models\Person;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    return $query->whereHas('lastSubscription', function ($query) {
                        return $query
                            ->where('user_id', auth()->user()->id)
                            ->where('status', 'active');
                    });
                }),
        ], auth()->user()->can('students_subscriptions') ? [
            'subscriptions' => Tab::make()->label('מנויים')
                ->modifyQueryUsing(function ($query) {
                    return $query->whereHas('lastSubscription', function ($query) {
                        return $query->where('status', 'active');
                    });
                }),
        ] : [
            'subscriptions_pending' => Tab::make()
                ->label('מנויים ממתינים')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereHas('lastSubscription', function ($query) {
                        return $query
                            ->where('status', 'pending')
                            ->whereNull('user_id')
                            ->where('is_published', true);
                    });
                }),
        ]);
    }

    public function getExtraColumns(): array
    {
            return [
                TextColumn::make('lastSubscription.status')
                    ->badge()
                    ->visible(fn () => $this->activeTab === 'subscriptions')
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'hold' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match (Str::trim($state)) {
                        'active' => 'פעיל',
                        'hold' => 'מושהה',
                        'pending' => 'ממתין',
                        default => $state,
                    })
                    ->label('סטטוס'),
                ToggleColumn::make('lastSubscription.is_published')
                    ->label('פרסום')
                    ->sortable()
                    ->getStateUsing(fn (Person $record) => Str::trim($record->lastSubscription->status) === 'pending' ? $record->lastSubscription->is_published : null)
                    ->disabled(fn (Person $record) => Str::trim($record->lastSubscription->status) !== 'pending')
                    ->visible(fn () => $this->activeTab === 'subscriptions'),
                TextColumn::make('lastSubscription.matchmaker.name')
                    ->label('שדכן')
                    ->visible(fn () => $this->activeTab === 'subscriptions')
                    ->description(fn (Person $record) => 'יום: '. $record->lastSubscription->work_day_he),
                TextColumn::make('lastSubscription.amount')
                    ->label('סכום')
                    ->money('ILS')
                    ->description(fn (Person $record) => $record->lastSubscription->balance_payments ? "נותרו {$record->lastSubscription->balance_payments} חודשים" : null)
                    ->visible(fn () => $this->activeTab === 'subscriptions'),

                TextColumn::make('lastSubscription.next_payment_date')
                    ->label('תאריך תשלום הבא')
                    ->date('d/m/Y')
                    ->visible(fn () => $this->activeTab === 'subscriptions'),

//                TextColumn::make('lastDiary.created_at')
//                    ->sortable()
//                    ->description(fn (Person $record) => $record->lastDiary ? $record->lastDiary->created_at->diffForHumans() : null)
//                    ->tooltip(fn (Person $record) => $record->lastDiary ? $record->lastDiary->label_type.': '.$record->lastDiary->data['description'] : null)
//                    ->label('פעילות אחרונה')
//                    ->date('d/m/Y')
//                    ->visible(fn () => $this->activeTab === 'subscriptions'),

                TextColumn::make('lastSubscription.referrer.full_name')
                    ->label('מפנה')
                    ->visible(fn () => $this->activeTab === 'subscriptions'),

//                TextColumn::make('subscriptions')
//                    ->label('תקופת פרוייקט')
//                    ->formatStateUsing(function (Person $record) {
//                        $start = $record->subscriptions->where('credit_card_id', $record->billing_credit_card_id)->first()->created_at->format('m/Y');
//                        $end = $record->billing_next_date->copy()->addMonths($record->billing_balance_times - 1)->format('m/Y');
//
//                        return "$start עד $end";
//                    })
//                    ->visible(fn () => $this->activeTab === 'subscriptions'),
            ];
    }

    //    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    //    {
    //        return $query->fastPaginate($this->getTableRecordsPerPage());
    //    }
}
