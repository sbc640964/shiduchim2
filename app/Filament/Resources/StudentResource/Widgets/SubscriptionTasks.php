<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Models\Person;
use App\Models\Task;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class SubscriptionTasks extends BaseWidget
{
    public Person $record;

    public function createNewForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('description')
                ->label('תיאור')
                ->required(),

            Forms\Components\DatePicker::make('due_date')
                ->label('תאריך יעד')
                ->default(now()->addDay())
                ->native(false)
                ->required(),
        ]);
    }

    public function newTaskAction(): Tables\Actions\CreateAction
    {
        return Tables\Actions\CreateAction::make('create')
            ->label('הוסף משימה')
            ->modalHeading('הוסף משימה')
            ->modalWidth(MaxWidth::Small)
            ->using(function (array $data) {
                return Task::create([
                    'person_id' => $this->record->id,
                    'user_id' => auth()->id(),
                    'description' => $data['description'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                ]);
            })
            ->form(fn (Form $form) => $this->createNewForm($form));
    }

    public function table(Table $table): Table
    {

        return $table
            ->heading('משימות')
            ->emptyStateIcon('iconsax-bul-gas-station')
            ->emptyStateActions([$this->newTaskAction()->name('create-empty-task')])
            ->emptyStateHeading("אפ' משימה אחת אין!")
            ->emptyStateDescription('אין משימות, הוסף חדש ותתחיל להזיז ת\'עניינים')
            ->headerActions([
                $this->newTaskAction()->visible(fn () => Task::where('person_id', $this->record->id)->exists()),
            ])
            ->query(
                Task::query()
                    ->where('person_id', $this->record->id)
            )
            ->columns([
                Tables\Columns\IconColumn::make('completed_at')
                    ->label('הושלם')
                    ->getStateUsing(fn (Task $task) => !!$task->completed_at)
                    ->width(20)
                    ->alignCenter()
                    ->boolean(),
                Tables\Columns\TextColumn::make('description')
                    ->label('תיאור'),
            ]);
    }
}
