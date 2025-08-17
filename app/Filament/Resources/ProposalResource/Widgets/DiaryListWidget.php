<?php

namespace App\Filament\Resources\ProposalResource\Widgets;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\ProposalResource\Pages\Diaries;
use App\Filament\Resources\ProposalResource\Traits\DiariesComponents;
use App\Models\Diary;
use App\Models\Form;
use App\Models\Proposal;
use App\Models\Task;
use Carbon\CarbonInterface;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;

class DiaryListWidget extends BaseWidget
{
    use DiariesComponents,
        InteractsWithRecord {
            table as protected tableComponent;
            getRecord as protected getRecordBase;
        }

    protected int|string|array $columnSpan = 2;
    protected static bool $isLazy = false;

    #[Reactive]
    public $datesRange = null;

    public function getRecord(): Model
    {
        if(is_int($this->record)) {
            $this->record = Proposal::findOrFail($this->record);
        }

        return $this->getRecordBase();
    }

    #[On('updateProposalInReportsPage')]
    function updateRecord($id): void
    {
        $this->record = $id;
    }

    public function table(Table $table): Table
    {
        if(!$this->record->guy || !$this->record->girl) {
            return $table;
        }

        return $this->tableComponent($table)
            ->heading('יומן פעילות')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->when($this->datesRange[0] ?? null ? $this->datesRange : null, function ($query, $date_range) {
                    return $query->whereBetween('created_at', $date_range);
                });
            })
            ->headerActions([
                Action::make('create-diary')
                    ->label('הוסף תיעוד')
                    ->model(Diary::class)
                    ->action(fn ($data) => Diaries::createNewDiary($data, $this->getRecord(), $data['side'] ?? null))
                    ->schema(fn ($form) => $this->form($form)),

                Action::make('create-task')
                    ->label('הוסף משימה')
                    ->model(Task::class)
                    ->action(function (array $data, self $livewire) {
                        $livewire->getRecord()->tasks()->create(array_merge($data, [
                            'user_id' => auth()->id(),
                        ]));
                    })
                    ->modalWidth(Width::Small)
                    ->schema([
                        Select::make('type')
                            ->label('סוג')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->default('regular')
                            ->options([
                                'regular' => 'רגילה',
                                'contact' => 'יצירת קשר',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if(filled($state) && blank($get('description'))) {
                                    $set('description', match ($state) {
                                        'regular' => '',
                                        'contact' => 'ליצור קשר עם...',
                                        default => 'לגשת לפינת הקפה, להירגע דקה או שניים ולחזור עם כל המרץ לשידוך... הדחף שלי הוא המנוע של ההורים לסגור היום!!!',
                                    });
                                }
                            })
                            ->required(),
                        Textarea::make('description')
                            ->placeholder(fn (Get $get) => match ($get('type')) {
                                default => 'לגשת לפינת הקפה, להירגע דקה או שניים ולחזור עם כל המרץ לשידוך... הדחף שלי הוא המנוע של ההורים לסגור היום!!!',
                            })
                            ->rows(6)
                            ->autosize()
                            ->columnSpanFull()
                            ->helperText('אין צורך להוסיף את שמות המשפחות השמות יופיעו באופן אוטומטי בתחילת התיאור בלוח המשימות.')
                            ->label('תיאור')
                            ->live()
                            ->default(fn (Get $get) => $get('description'))
                            ->required(),
                        DateTimePicker::make('due_date')
                            ->label('תאריך יעד')
                            ->placeholder('בחר תאריך')
                            ->firstDayOfWeek(CarbonInterface::SUNDAY)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->time()
                            ->date()
                            ->seconds(false)
                            ->minDate(now()->addDay()->startOfDay())
                            ->default(now()->addDay()->setTime(9, 0))
                            ->required(),
                        Select::make('priority')
                            ->label('עדיפות')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->default('1')
                            ->options([
                                '0' => 'נמוכה',
                                '1' => 'בינונית',
                                '2' => 'גבוהה',
                            ])
                            ->required(),
                        Select::make('data.contact_to')
                            ->visible(fn (Get $get) => $get('type') === 'contact')
                            ->required()
                            ->label('איש קשר')
                            ->searchable()
                            ->allowHtml()
                            ->getSearchResultsUsing(function (string $query) {
                                return $this->getRecord()->contacts()->searchName($query)->get()->pluck('select_option_html', 'id');
                            })
                    ]),
            ])
            ->paginationPageOptions([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->relationship(fn (): Relation|Builder => $this->getRelationship())
            ->recordAction(function (Model $record, Table $table): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $table->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action->record($record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    if ($action->getUrl()) {
                        continue;
                    }

                    return $action->getName();
                }

                return null;
            })
            ->recordUrl(function (Model $record, Table $table): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $table->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action->record($record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    $url = $action->getUrl();

                    if (! $url) {
                        continue;
                    }

                    return $url;
                }

                return null;
            });

    }

    //    public function getRecord() {
    //
    //    }

    private function getRelationship()
    {
        return $this->getRecord()->diaries();
    }

    public function getOwnerRecord()
    {
        return $this->getRecord();
    }
}
