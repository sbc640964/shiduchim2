<?php

namespace App\Filament\Resources\Students\Pages;

use Exception;
use Filament\Actions\BulkAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\Filter;
use Filament\Schemas\Components\Group;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint\Operators\IsMinOperator;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Form as FormModel;
use App\Models\Person as Student;
use App\Models\Proposal;
use DB;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class AddProposal extends ListRecords
{
    use InteractsWithRecord;

    protected static string $resource = StudentResource::class;

    protected static ?string $title = 'חיפוש הצעה';

    protected static ?string $navigationLabel = 'חיפוש הצעה';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-magnifying-glass-plus';

    public function mount(): void
    {
        $record = func_get_args()[0] ?? null;

        $this->record = $this->resolveRecord($record);

        parent::mount();
    }

    protected function getTableQuery(): ?Builder
    {
        return StudentResource::modifyTableQuery(Student::query())
            ->with(StudentResource::withRelationship())
//            ->single()
//            ->where(fn ($query) => $query
//                ->whereDoesntHave('families')
//                ->orWhereRelation('family', fn (Builder $query) => $query->where('status', '!=', 'married')
//                ))
            ->where('gender', '!=', $this->getRecord()->gender);
    }

    public function table(Table $table): Table
    {
        return StudentResource::table($table)
            ->deferLoading()
            ->deselectAllRecordsWhenFiltered(false)
            ->recordUrl(null)
            ->paginationPageOptions([10, 25, 50, 100, 200])
            ->toolbarActions([
                BulkAction::make('create-proposals')
                    ->label('צור הצעות')
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading("המערכת תיצור באופן אוטו' הצעות לכלל המסומנים")
                    ->action(function (Collection $records, BulkAction $action) {
                        $records->each(function (Student $student) {
                            $this->addProposal($student);
                        });

                        $action->successRedirectUrl(StudentResource::getUrl('proposals', ['record' => $this->getRecord()->id]));
                        $action->success();
                    }),
            ])
            ->recordActions([
                Action::make('add-proposal')
                    ->action(function (Student $student, Action $action) {
                        $this->addProposal($student, $action);
                    })
                    ->icon('heroicon-o-plus')
                    ->label('')
                    ->hidden(fn (Student $student) => $student->proposals_exists === true)
                    ->color('success')
                    ->modalHeading(fn (Student $student) => 'הוספת הצעה ל'.$this->getRecord()->full_name.' עם '.$student->full_name)
                    ->requiresConfirmation(),
                ...FormModel::getActions('students'),
                ActionGroup::make([
                    ViewAction::make()
                        ->label('צפייה')
                        ->icon('heroicon-o-eye')
                        ->slideOver()
                        ->url(null),
                    EditAction::make()
                        ->label('עריכה')
                        ->icon('heroicon-o-pencil')
                        ->slideOver()
                        ->url(null),
                ]),
            ]);

        //            ->checkIfRecordIsSelectableUsing(function (Student $student) {
        //                return $student->proposals_exists === false;
        //            })
    }

    private function getFilters()
    {

        return [
            Filter::make('table_columns')
                ->columnSpanFull()
                ->columns(1)
                ->query(function (Builder $query, array $data) {
                    return $query
                        ->when($data['first_name'] ?? null, fn (Builder $query, $value) => $query->where('first_name', 'like', "%$value%"))
                        ->when($data['last_name'] ?? null, fn (Builder $query, $value) => $query->where('last_name', 'like', "%$value%"))
                        ->when($data['father_first_name'] ?? null, fn (Builder $query, $value) => $query->whereRelation('father', 'first_name', 'like', "%$value%"));
                })
                ->schema([
                    Group::make([
                        TextInput::make('last_name')
                            ->placeholder('שם משפחה')
                            ->label('שם משפחה'),
                        TextInput::make('first_name')
                            ->placeholder('שם פרטי')
                            ->label('שם פרטי'),
                        TextInput::make('father_first_name')
                            ->placeholder('שם האב')
                            ->label('שם האב'),
                    ])->columns(6)->hiddenLabel(),
                ]),
        ];

        return [
            QueryBuilder::make()
                ->label('סינון מתקדם')
                ->constraints([
                    TextConstraint::make('first_name')
                        ->label('שם פרטי'),

                    SelectConstraint::make('last_name')
                        ->multiple()
                        ->options($this->getTableQuery()->pluck('last_name', 'last_name')->toArray())
                        ->searchable()
                        ->label('שם משפחה'),

                    RelationshipConstraint::make('tags')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->modifyRelationshipQueryUsing(function (Builder $query) {
                                    $query->addSelect('name->he as name');
                                })
                                ->titleAttribute('name')
                                ->searchable()
                                ->multiple()
                        )
                        ->label('תגיות')
                        ->multiple(),

                    RelationshipConstraint::make('school')
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->searchable()
                                ->multiple()
                                ->preload()
                        )
                        ->label('מוסד לימודים'),

                    RelationshipConstraint::make('proposals')
//                        ->selectable(
//                            Constraints\RelationshipConstraint\Operators\IsRelatedToOperator::make()
//                                ->native(false)
//                            ->titleAttribute('id'),
//                        )
                        ->label('הצעות')
                        ->multiple(),

                    NumberConstraint::make('age')
                        ->label('גיל')
                        ->operators([
                            IsMinOperator::make()
                                ->query(function (Builder $query, $settings, $column, $isInverse) {
                                    $date = now()->hebcal()->subYears((int) $settings['number']);

                                    $query->whereDate('born_at', '<=', $date->georgianDate()->startOfDay());
                                }),
                        ]),
                ]),
        ];
    }

    public function addProposal(Student $student, null|Action|BulkAction $action = null): void
    {
        $student->loadExists(['proposals' => function (Builder $query) use ($student) {
            $query->where('created_by', auth()->id())
                ->where($student->gender === 'G' ? 'guy_id' : 'girl_id', $this->getRecord()->id);
        }]);

        //A lock that will not allow the same offer (both people) to be created within 10 seconds

        $lock = cache()->lock('add-proposal-'.$this->getRecord()->id.'-'.$student->id, 10);

        if (! $lock->get()) {
            if ($action) {
                $action->failureNotificationTitle('הוספת הצעה נכשלה');
                $action->failureNotification(fn (Notification $notification) => $notification->body('הצעה זו נוצרה בזמן האחרון'));
                $action->failure();
            }
            return;
        }

        // check if the proposal already exists
        if($student->proposals_exists) {
            if ($action) {
                $action->failureNotificationTitle('הוספת הצעה נכשלה');
                $action->failureNotification(fn (Notification $notification) => $notification->body('הצעה זו כבר קיימת'));
                $action->failure();
            }
            return;
        }

        DB::beginTransaction();

        try {
            $proposal = Proposal::createWithPeopleAndContacts([
                'created_by' => auth()->id(),
            ], collect([
                $this->getRecord(),
                $student,
            ]));

            if (auth()->user()->hasAnyRole(config('app.auto_role_access_proposal'))) {
                $proposal->users()->syncWithoutDetaching([auth()->id()]);
            }

            if ($action) {
                $action->successRedirectUrl(ProposalResource::getUrl('view', ['record' => $proposal->id]));
                $action->success();
            }

            DB::commit();
        } catch (Exception|Throwable $e) {
            DB::rollBack();

            if ($action) {
                $action->failureNotificationTitle('הוספת הצעה נכשלה');
                $action->failureNotification(fn(Notification $notification) => $notification->body($e->getMessage()));
                $action->failure();
            }

            context([
                'record' => $this->getRecord(),
                'student' => $student,
                'exception' => $e,
                'action' => $action,
            ]);

            throw $e;
        } finally {
            $lock->release();
        }
    }
}
