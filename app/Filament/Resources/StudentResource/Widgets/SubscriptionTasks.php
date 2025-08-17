<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Models\Person;
use App\Models\Task;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class SubscriptionTasks extends BaseWidget
{
    public Person $record;

    public function createNewForm(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('description')
                ->label('תיאור')
                ->required(),

            DatePicker::make('due_date')
                ->label('תאריך יעד')
                ->default(now()->addDay())
                ->native(false)
                ->required(),
        ]);
    }

    public function newTaskAction(): CreateAction
    {
        return CreateAction::make('create')
            ->label('הוסף משימה')
            ->modalHeading('הוסף משימה')
            ->modalWidth(Width::Small)
            ->using(function (array $data) {
                return Task::create([
                    'person_id' => $this->record->id,
                    'user_id' => auth()->id(),
                    'description' => $data['description'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                ]);
            })
            ->schema(fn (Schema $schema) => $this->createNewForm($schema));
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
                IconColumn::make('completed_at')
                    ->label('הושלם')
                    ->getStateUsing(fn (Task $task) => !!$task->completed_at)
                    ->width(20)
                    ->alignCenter()
                    ->boolean(),
                TextColumn::make('description')
                    ->label('תיאור'),
            ]);
    }
}
