<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\CallsDiariesResource\Pages;
use App\Filament\Resources\PersonResource;
use App\Filament\Resources\ProposalResource;
use App\Filament\Resources\ProposalResource\Pages\Diaries;
use App\Infolists\Components\AudioEntry;
use App\Jobs\TranscriptionCallJob;
use App\Models\Call;
use App\Models\Diary;
use App\Models\Family;
use App\Models\Person;
use App\Models\Proposal;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components as InfolistComponents;
use Filament\Infolists\Infolist;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Stringable;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class CallsDiariesResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static ?string $navigationIcon = 'iconsax-bul-call';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'שיחה';

    protected static ?string $pluralLabel = 'יומן שיחות';

    protected static ?string $modelLabel = 'שיחה';

    protected static ?string $modelPluralLabel = 'שיחות';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
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
            ), 'proposals');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(fn (Call $call) => $call->user?->name ?? 'לא ידוע')
                    ->description(fn (Call $call) => $call->extensionWithTarget(true))
                    ->label('משתמש')
                    ->searchable()
                    ->visible(auth()->user()->canAccessAllCalls()),
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
                        $query
                            ->when(
                                str($search)->test('/^\d+$/'),
                                fn (Builder $query) => $query->where('phone', 'like', "%$search%"),
                            )
                            ->where('phone', 'like', "%$search%")
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('משך')
                    ->formatStateUsing(fn ($state) => gmdate('H:i:s', $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('proposals')
                    ->label('הצעות קשורות')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->width(50)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->label('משתמש')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->visible(auth()->user()->canAccessAllCalls())
                    ->relationship('user', 'name'),

                DateRangeFilter::make('created_at')
                    ->label('תאריך')
                    ->placeholder('בחר תאריך'),
                Tables\Filters\Filter::make('filters')
                    ->form([
                        TextInput::make('target_phone')
                            ->label('מספר שחוייג')
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        if(filled($data['target_phone'])) {
                            $phone = str($data['target_phone'])->whenStartsWith('0', fn (Stringable $str) => $str->substr(1));
                            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data_raw, '$.events[0].target_phone')) LIKE ?", ["%{$phone}%"]);
                        }
                    })
            ])
            ->defaultSort('calls.created_at', 'desc')
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
                                ->successNotificationTitle('ההקלטה נשלחה לניתוח ע"י המערכת, ככל הנראה התמלול יהיה מוכן בקרוב, נסה להיכנס לכאן בעוד כמה דקות שוב :)')
                                ->action(function (Call $call, Action|InfolistComponents\Actions\Action $action) {
                                    TranscriptionCallJob::dispatch($call->id);
                                    $action->success();
                                })
                                ->visible(auth()->user()->can('ai_beta'))
                            )
                            ->html()
                    ])),
                Tables\Actions\Action::make('go-to-proposals')
                    ->size(ActionSize::ExtraSmall)
                    ->visible(fn (Call $record) => $record->getProposalContactsCount() > 0)
                    ->label('עבור להצעות')
                    ->badge(fn (Call $record) => $record->getProposalContactsCount())
                    ->url(fn (Call $record) => PersonResource::getUrl('proposals', ['record' => $record->getPersonContactId()]))
                    ->button(),
                Tables\Actions\Action::make('add_proposal_diary')
                    ->size(ActionSize::ExtraSmall)
                    ->visible(fn (Call $record) => $record->getProposalContactsCount() > 0)
                    ->label('הוסף יומן')
                    ->color(Color::Cyan)
                    ->action(function (Call $record, array $data) {
                        Diaries::createNewDiary(
                            $data,
                            Proposal::find($data['proposal']),
                                $data['side'] ?? null
                        );
                    })
                    ->steps([
                        Step::make('proposal')
                            ->label('הצעה')
                            ->schema([
                                Select::make('proposal')
                                    ->label('הצעה')
                                    ->options(fn (Call $record) => Proposal::query()
                                        ->whereHas('contacts', fn (Builder $query) => $query->where('person_id', $record->getPersonContactId()))
                                        ->get()
                                        ->mapWithKeys(fn (Proposal $proposal) => [$proposal->id => $proposal->families_names])
                                    )
                                    ->afterStateUpdated(function ($state, Call $record, Set $set, $livewire) {
                                        if($state
                                            && $side = $record->getPersonContact()
                                                ?->proposalContacts()
                                                ?->firstWhere('proposals.id', $state)
                                                ?->pivot
                                                ->side
                                        ) {
                                            $proposal = Proposal::find($state);
                                            $set('side', $side);
                                            $set('statuses.proposal', $proposal->status);
                                            $set("statuses.$side", $proposal->{"status_$side"});
                                        }
                                    })
                                    ->required()
                                    ->searchable()
                                    ->placeholder('בחר הצעה'),
                            ]),
                        Step::make('diary')
                            ->label('יומן')
                            ->columns(2)
                            ->schema(fn (Get $get, Call $record) => ProposalResource\Pages\Diaries::getDiaryFormSchema(currentCall: $record, get: $get)),
                    ])
                    ->button(),
                Tables\Actions\Action::make('call')
                    ->size(ActionSize::ExtraSmall)
                    ->color('success')
                    ->icon('iconsax-bul-call')
                    ->label('חייג')
                    ->modalWidth(MaxWidth::Large)
                    ->modalHeading(fn (Call $call) => $call->phoneModel ? null : 'יצירת מספר חדש וחיוג')
                    ->modalDescription(fn (Call $call) => $call->phoneModel ? null : "מס' הטלפון לא קיים במערכת, בחר איש קשר אליו יצורף המספר, כמו כן בחר את סוג המספר (אישי או ביתי).")
                    ->modalSubmitActionLabel('צור טלפון וחייג')
                    ->form(function (Form $form, Call $call) {

                        if ($call->phoneModel) {
                            return null;
                        }

                        return $form->schema([
                            Cluster::make([
                                Select::make('person')
                                    ->label('איש קשר')
                                    ->live()
                                    ->options(
                                        Person::query()
                                            ->take(60)
                                            ->with('father', 'fatherInLaw')
                                            ->get()
                                            ->mapWithKeys(fn (Person $person) => [$person->id => $person->select_option_html])
                                    )
                                    ->getSearchResultsUsing(fn (string $searchQuery) => Person::query()
                                        ->searchName($searchQuery)
                                        ->take(60)
                                        ->with('father', 'fatherInLaw')
                                        ->get()
                                        ->mapWithKeys(fn (Person $person) => [$person->id => $person->select_option_html])
                                    )
                                    ->createOptionForm(function (Form $form) {
                                        return $form->schema([

                                        ]);
                                    })
                                    ->columnSpan(3)
                                    ->exists('people', 'id')
                                    ->allowHtml()
                                    ->required()
                                    ->searchable()
                                    ->placeholder('בחר איש קשר'),

                                Select::make('type')
                                    ->default('personal')
                                    ->selectablePlaceholder(false)
                                    ->native(false)
                                    ->options(fn (Get $get) => ((Person::find($get('person'))?->family ?? null) ? [
                                        'personal' => 'אישי',
                                        'family' => 'משפחתי',
                                    ] : [
                                        'personal' => 'אישי',
                                    ])),
                            ])
                                ->label('איש קשר')
                                ->columns(4),
                        ]);
                    })
                    ->action(function (Call $call, $livewire, $data, Tables\Actions\Action $action) {

                        $phone = $call->phoneModel;

                        if (! $call->phoneModel && $person = Person::find($data['person'])) {
                            if ($data['type'] === 'personal') {
                                $phone = $person->phones()->create([
                                    'number' => $call->phone,
                                ]);
                            } else {
                                $phone = $person->family->phones()->create([
                                    'number' => $call->phone,
                                ]);
                            }

                            if ($phone) {
                                Call::updateModelPhones($phone);
                            }
                        }

                        if(! $phone) {
                            $action->failureNotificationTitle('לא הצלחנו לחייג למספר, יכול להיות שלא קיים המספר במאגר שלנו.');
                            $action->failure();
                        }

                        $phone->call();
                        $livewire->dispatch('refresh-calls-box');

                        $action->successNotificationTitle('השיחה נשלחה לשלוחה');
                        $action->success();
                    })
                    ->button(),
                    Tables\Actions\Action::make('diary_2')
                        ->visible(fn (Call $call) => $call->phoneModel)
                        ->tooltip('כרטיס שיחה')
                        ->iconButton()
                        ->icon('heroicon-o-cursor-arrow-rays')
                        ->modalSubmitAction(false)
                        ->slideOver()
                        ->extraModalWindowAttributes(['class' => '[&_.fi-modal-content]:p-0'])
                        /*
                        <div class="flex items-center gap-2">
                <div class="flex-shrink flex items-center">
                    <div class="rounded-full flex justify-center p-1 bg-success-100 items-center">
                        <x-iconsax-bul-call class="w-8 h-8 text-success-600"/>
                    </div>
                </div>
                <div>
                    <h3 class="font-semibold">
                        {{ $this->call?->getStatusLabel() }}
                    </h3>
                    <p class="text-xs text-gray-500 whitespace-nowrap">
                        {{ $this->call?->getDialName() }}
                    </p>
                </div>
            </div>
                         */
                        ->modalHeading(fn (Call $call) => str(\Blade::render('<div class="flex items-center gap-2">
                            <div class="flex-shrink flex items-center">
                                <div
                                 @class(["rounded-full flex justify-center p-1 items-center", "bg-success-100" => !$call->finished_at,  "bg-red-100" => $call->finished_at])
                                >
                                    <x-iconsax-bul-call @class([ "w-8 h-8", "text-success-600" => !$call->finished_at,  "text-red-600" => $call->finished_at])/>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-semibold">
                                    {{ $call?->getStatusLabel() }}
                                </h3>
                                <p class="text-xs text-gray-500 whitespace-nowrap">
                                    {{ $call?->getDialName() }}
                                </p>
                            </div>
                        </div>', ['call' => $call]))->toHtmlString())
                        ->modalContent(fn (Call $call) => str(\Blade::render('<livewire:active-call-drawer :hidden-header="true" :current-call="$call" :key="$call->id" />', ['call' => $call]))->toHtmlString())
                //                Tables\Actions\EditAction::make(),
            ])
//            ->contentGrid([
//                'sm' => 1
//            ])
            ->bulkActions([
                //                Tables\Actions\BulkActionGroup::make([
                //                    Tables\Actions\DeleteBulkAction::make(),
                //                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallsDiaries::route('/'),
        ];
    }
}
