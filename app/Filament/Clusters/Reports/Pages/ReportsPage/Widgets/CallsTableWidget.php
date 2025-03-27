<?php

namespace App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets;

use App\Filament\Widgets\FilterReportsTrait;
use App\Infolists\Components\AudioEntry;
use App\Models\Call;
use App\Models\Diary;
use App\Models\Family;
use App\Models\Person;
use Carbon\Carbon;
use Filament\Infolists\Components as InfolistComponents;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Attributes\Reactive;

class CallsTableWidget extends BaseWidget
{
    use FilterReportsTrait;

    #[Reactive]
    public ?int $proposal = null;

    public ?array $datesRange = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('שיחות')
            ->actions([
                Tables\Actions\Action::make('speaker-recording')
                    ->iconButton()
                    ->icon('heroicon-o-speaker-wave')
                    ->modalWidth('lg')
                    ->modalHeading('הקלטת שיחה')
                    ->visible(fn (Call $call) => $call->audio_url !== null)
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('סגור')
                    ->tooltip('הקלטת שיחה')
                    ->infolist(fn (Infolist $infolist) => $infolist->schema([
                        Split::make([
                            TextEntry::make('duration')
                                ->label('משך הקלטה')
                                ->formatStateUsing(fn (Call $call) => gmdate('H:i:s', $call->duration))
                                ->inlineLabel(),
                        ]),
                        AudioEntry::make('audio_url')
                            ->state(fn (Call $call) => urldecode((string) $call->audio_url))
                            ->autoplay()
                            ->hiddenLabel(),

                        InfolistComponents\TextEntry::make('text_call')
                            ->label('טקסט שיחה')
                            ->hintAction(InfolistComponents\Actions\Action::make('refresh_call_text')
                                ->icon('heroicon-o-arrow-path')
                                ->iconButton()
                                ->tooltip('נתח מחדש את הטקסט של השיחה')
                                ->color('gray')
                                ->action(fn (Call $call) => $call->refreshCallText())
                                ->visible(auth()->user()->can('ai_beta'))
                            )
                            ->html()
                    ])),
            ])
            ->query(
                Call::query()
                    ->when($this->datesRange[0] ?? null ? $this->datesRange : null, function ($query, $dateRange) {
                        return $query->whereBetween('created_at', $dateRange);
                    })
                    ->whereHas('diaries', function ($query) {
                        $query->where('proposal_id', $this->proposal);
                    })
                    ->with(['phoneModel' => fn ($query) => $query
                        ->with(['model' => fn (MorphTo $query) => $query
                            ->morphWith([
                                Family::class => ['people' => fn ($query) => $query->withCount('proposalContacts')],
                            ])
                            ->morphWithCount([
                                Person::class => ['proposalContacts'],
                            ]),
                        ]),
                    ])
                    ->when(
                        auth()->user()->canAccessAllCalls(),
                        fn (Builder $query) => $query->with('user'),
                        fn (Builder $query) => $query->where('user_id', auth()->id())
                    )
                    ->addSelect('*')
                    ->selectSub(Diary::whereColumn('diaries.data->call_id', 'calls.id')->selectRaw(
                        'count( distinct `diaries`.`proposal_id`)'
                    ), 'proposals')
            )
            ->columns([
//                Tables\Columns\TextColumn::make('extension')
//                    ->weight(FontWeight::Bold)
//                    ->formatStateUsing(fn (Call $call) => $call->user?->name)
//                    ->description(fn (Call $call) => $call->extension)
//                    ->label('משתמש')
//                    ->visible(auth()->user()->canAccessAllCalls())
//                    ->searchable(),
                Tables\Columns\IconColumn::make('group')
                    ->icons([
                        'iconsax-bul-call-incoming' => 'incoming',
                        'iconsax-bul-call-outgoing' => 'outgoing',
                        'iconsax-bul-call-remove' => 'missed',
                    ])
                    ->width(50)
                    ->label('סוג')
                    ->tooltip(fn ($state) => match ($state) {
                        'incoming' => 'שיחה נכנסת',
                        'outgoing' => 'שיחה יוצאת',
                        'missed' => 'שיחה שלא נענתה',
                        default => null,
                    })
                    ->colors([
                        'success' => 'incoming',
                        'primary' => 'outgoing',
                        'danger' => 'missed',
                    ]),
                Tables\Columns\TextColumn::make('phone')
                    ->label('מספר')
                    ->formatStateUsing(fn ($state) => str($state)
                        ->whenStartsWith('05',
                            fn ($phone) => str($phone)->substrReplace('-', 3, 0),
                            fn ($phone) => str($phone)->substrReplace('-', 2, 0)
                        )->value())
                    ->description(function (Call $call) {
                        if ($call->phoneModel?->model) {
                            if ($call->phoneModel->model::class === Person::class) {
                                return $call->phoneModel->model->full_name;
                            } elseif ($call->phoneModel->model::class === Family::class) {
                                return "משפ' ".$call->phoneModel->model->name;
                            }

                            return 'לא ידוע';
                        }

                        return 'לא ידוע';
                    })
                    ->searchable(query: function (Builder $query, $search) {
                        $query->where('phone', 'like', "%$search%")
                            ->orWhereHas(
                                'phoneModel',
                                function (Builder $query) use ($search) {
                                    $query->whereHasMorph(
                                        'model',
                                        [Person::class, Family::class],
                                        fn(Builder $query) => $query->getModel() instanceof Person
                                            ? $query->searchName($search)
                                            : $query->where('name', 'like', "%$search%")
                                    );
                                });
                    })
                    ->weight(FontWeight::Bold)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('תאריך')
                    ->formatStateUsing(fn (Carbon $state) => $state->diffForHumans())
                    ->description(fn (Carbon $state) => $state->format('H:i:s d/m/y'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('משך')
                    ->formatStateUsing(fn ($state) => gmdate('H:i:s', $state))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('proposals')
                    ->label('הצעות קשורות')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->width(50)
                    ->sortable(),
            ]);
    }
}
