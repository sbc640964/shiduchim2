<?php

namespace App\Filament\Resources\Proposals\Pages;

use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Actions\ActionGroup;
use App\Filament\Resources\Proposals\Widgets\CallOverview;
use App\Filament\Resources\Proposals\Widgets\ContactsWidget;
use App\Filament\Resources\Proposals\Widgets\DiaryListWidget;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Blade;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Actions\Call;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Proposal;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewProposal extends ViewRecord
{
    protected static string $resource = ProposalResource::class;
    protected static ?string $navigationLabel = 'מבט כללי';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-pie';

    protected function resolveRecord(int|string $key): Model
    {
        $record = parent::resolveRecord($key);

        if (! $record instanceof Proposal) {
            abort(404);
        }

        if(!session("proposal_{$record->id}_viewed")){
            session()->put("proposal_{$record->id}_viewed", true);
            $record->recordActivity('viewed');
        }


        $record->loadMissing([
            'people.father.mother',
            'people.father.father',
            'people.mother.father',
            'people.mother.mother',
            'people.city'
        ]);

        return $record;

    }

    public static function canAccess(array $parameters = []): bool
    {
        /** @var Proposal $proposal */
        $proposal = $parameters['record'];

        return $proposal && $proposal->userCanAccess() ?? false;
    }

    public static function getTabLabel(): string
    {
        return 'פרטי הצעה';
    }

    protected function getActions(): array
    {
        $text = str('<div class="bg-orange-200 text-orange-950 text-center font-bold p-2 rounded-xl border border-orange-900">"שידוך פתוח הכוונה: שכרגע אם שואלים את האבא מה קורה שידוכים? הוא אומר: כן, יש לי עכשיו כזה וכזה הצעה, והוא בודק את זה"  (יוחנן) </div>')->toHtmlString();

        return [
            DeleteAction::make()
                ->label('מחק')
                ->before(fn (Proposal $proposal) => $proposal->deleteDependencies()),

            Action::make('open')
                ->label('סמן כהצעה פתוחה')
                ->requiresConfirmation()
                ->modalContent($text)
                ->modalDescription('האם אתה בטוח שברצונך לסמן את ההצעה כפתוחה?')
                ->action(fn (Proposal $proposal) => $proposal->openProposal())
                ->visible(fn (Proposal $proposal) => auth()->user()->can('open_proposals')
                    && $proposal->opened_at === null || $proposal->closed_at !== null),

            Action::make('close')
                ->label('סמן כהצעה סגורה')
                ->modalDescription('האם אתה בטוח שברצונך לסמן את ההצעה כסגורה?')
                ->requiresConfirmation()
                ->modalContent($text)
                ->schema(fn (Schema $schema, Proposal $proposal) => $schema
                    ->components([
                        Textarea::make('description')
                            ->rules('required')
                            ->minLength(20)
                            ->label('סיבת סגירה'),
                    ]))
                ->action(fn (Proposal $proposal, array $data) => $proposal->closeProposal($data['description']))
                ->visible(fn (Proposal $proposal) => auth()->user()->can('open_proposals')
                    && $proposal->opened_at !== null && $proposal->closed_at === null),

            ActionGroup::make([
                Action::make('activities')
                    ->visible(auth()->user()->can('activity_log'))
                    ->label('היסטוריית מנוי')
                    ->icon('heroicon-o-clock')
                    ->slideOver()
                    ->modalHeading('היסטוריית הצעה')
                    ->modalContent(function () {
                        $activities = $this->getRecord()->activities()->with('user')->get();
                        $users = User::findMany([
                            ...$activities->pluck('data.old'),
                            ...$activities->pluck('data.new'),
                        ]);
                        return view('filament.resources.student-resource.widgets.subscription-activities', [
                            'activities' => $activities,
                            'users' => $users,
                        ]);
                    })
            ])
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CallOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getFooterWidgets(): array
    {
        return [
            ContactsWidget::make([
                'record' => $this->getRecord(),
                'side' => 'guy',
            ]),

            ContactsWidget::make([
                'record' => $this->getRecord(),
                'side' => 'girl',
            ]),
//
            DiaryListWidget::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Group::make([
                Section::make()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('status')
                                ->label('סטטוס')
                                ->formatStateUsing(fn (Proposal $proposal, $state) => Blade::render(
                                    <<<'HTML'
<span class="flex gap-2 justify-between w-full">
<span>סטטוס:</span>
<span>
<x-status-option-in-select
    :status="$proposal->getStatus()"
    :isColumn="true"
/>
</span>
</span>
HTML

                                    , ['proposal' => $proposal, 'state' => $state])
                                )
                                ->hiddenLabel()
//                        ->inlineLabel()
                                ->html(),
                            TextEntry::make('created_at')
                                ->date('d/m/Y')
                                ->label('תאריך יצירה'),
                            TextEntry::make('createdByUser.name')
                                ->label('נוצר על ידי')
                        ]),
                        TextEntry::make('description')
                            ->hintAction(Action::make('edit-proposal')
                                ->label('ערוך')
                                ->tooltip('עריכת הערה/תיאור')
                                ->action(fn (Proposal $proposal, array $data) => $proposal->update($data))
                                ->schema(fn (Schema $schema, Proposal $proposal) => $schema
                                    ->components([
                                        RichEditor::make('description')
                                            ->default($proposal->description)
                                            ->label('תיאור/הערה'),
                                    ])))
                            ->markdown()
                            ->label('תיאור/הערה'),
                    ])
            ])->columns(2)->columnSpanFull(),
            Grid::make()
                ->columnSpanFull()
                ->components([
                    $this->sideCard('guy'),
                    $this->sideCard('girl'),
                ])
        ]);
    }

    public function sideCard(string $side)
    {
        if (! in_array($side, ['guy', 'girl'])) {
            return null;
        }

        $label = $side === 'guy' ? 'בחור' : 'בחורה';
        $ucFirst = ucfirst($side);

        if(! $this->record->{$side}) {
            return Section::make("ה$label")
                ->columnSpan(1)
                ->columns(1)
                ->statePath($side)
                ->schema([]);
        }

        return Section::make("ה$label")
            ->columnSpan(1)
            ->columns(1)
            ->statePath($side)
            ->headerActions([
                Action::make('to_family')
                    ->label('למשפחה')
                    ->size('xs')
                    ->url(ProposalResource::getUrl('families', ['record' => $this->record->id, 'side' => $side]))
                    ->outlined(),
                Action::make('to_proposals')
                    ->label('להצעות')
                    ->badge($this->record->{$side}->loadCount('proposals')->proposals_count)
//                    ->visible($this->record->{$side}->proposals_count > 1)
                    ->size('xs')
                    ->color('gray')
                    ->badgeColor('gray')
                    ->url(StudentResource::getUrl('proposals', ['record' => $this->record->{$side}->id]), true)
                    ->outlined(),
            ])
            ->schema([
                TextEntry::make('full_name')
                    ->hiddenLabel()
                    ->extraAttributes(['class' => "[&_.fi-in-text-item]:block [&_.fi-in-text-item]:w-full [&_.max-w-max]:w-full [&_.max-w-max]:max-w-full"])
                    ->columnSpanFull()
                    ->formatStateUsing(fn (Proposal $proposal, $state) => Blade::render(
<<<'HTML'
<span class="flex justify-between w-full">
<span>{{ $state }}</span>
<span>
<x-status-option-in-select
    :status="$proposal->getStatus($side)"
    :isColumn="true"
/>
</span>
</span>
HTML

                    , ['proposal' => $proposal, 'state' => $state, 'side' => $side]))
                    ->html()
                    ->size('lg')
                    ->weight('bold')
                    ->label("שם ה$label"),

                Section::make()
                    ->extraAttributes(fn (Proposal $proposal) => [
                        'class' => '[&_svg]:w-8 [&_svg]:h-8 '.($proposal->nextDateIsPast($side)
                                ? '!bg-danger-50 !ring-danger-300/50 [&_svg]:!text-danger-500'
                                : (
                                    $proposal->nextDateIsToday($side)
                                        ? '!bg-success-50 !ring-success-300 [&_svg]:animate-bounce [&_svg]:!text-success-500'
                                        : (now()->addDay()->isSameDay($proposal->{"{$side}_next_time"})
                                        ? '!bg-warning-50 !ring-warning-300 [&_svg]:animate-bounce [&_svg]:!text-warning-500'
                                        : '!bg-gray-50')
                                )
                        ),
                    ])
                    ->columns(1)
                    ->visible(fn (Proposal $proposal) => $proposal->{"{$side}_next_time"} !== null)
                    ->schema([
                        TextEntry::make("{$side}_next_time")
                            ->state(function (Proposal $proposal) use ($ucFirst, $side) {
                                return
                                    '<div class="leading-normal">'
                                    .'המשך טיפול: '
                                    .$proposal->getNextDate($side)
                                    .'<span class="text-xs text-gray-400 ms-2">'.$proposal->{"${side}_next_time"}->format('d/m/y').'</span>'
                                    .'<div class="text-sm font-normal">'.'תיעוד אחרון ('.($proposal->{"last{$ucFirst}Diary"}?->label_type ?? '').'): '
                                    .($proposal->{"last{$ucFirst}Diary"}?->data['description'] ?? '')
                                    .'</div>'
                                    .'</div>';
                            })
                            ->html()
                            ->hiddenLabel()
                            ->icon('iconsax-bul-timer-1')
                            ->size('lg')
                            ->weight('semibold')
                            ->label('תאריך טיפול הבא'),
                    ]),

                ViewEntry::make($side)
                    ->view('filament.resources.proposal-resource.entries.side-info-view-proposal')
                    ->registerActions([
                        ...Call::getInfolistActionForProposal($this->getRecord(), $side),
                    ])
                    ->viewData([
                        'proposal' => $this->record,
                        'side' => $side,
                        'sideRecord' => $this->record->{$side},
                    ]),
            ]);
    }
}
