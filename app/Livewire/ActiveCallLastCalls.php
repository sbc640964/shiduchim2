<?php

namespace App\Livewire;

use App\Models\Call;
use App\Models\Diary;
use App\Models\Family;
use App\Models\Person;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ActiveCallLastCalls extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public Call $currentCall;


    public function render()
    {
        unset($this->calls);
        return view('livewire.active-call-last-calls');
    }

    public function getQuery()
    {
        return Call::query()
            ->where(fn($q) => $q
                ->where('phone', $this->currentCall->phone)
                ->orWhereRelation('phoneModel', function (Builder $query) {

                    if(!$this->currentCall->phoneModel) {
                        return;
                    }

                    $type = $this->currentCall->phoneModel->model::class;
                    $id = $this->currentCall->phoneModel->model->id;

                    if ($type === Person::class) {
                        $query->whereHasMorph('model', [Person::class], fn($query) => $query->where('id', $id)
                        )->orWhereHasMorph('model', [Family::class], fn($query) => $query->whereHas('people', fn($query) => $query->where('id', $id)
                        )
                        );
                    } elseif ($type === Family::class) {
                        $people = $this->currentCall->phoneModel->people()->pluck('id')->toArray();
                        $query->whereHasMorph('model', [Person::class], fn($query) => $query->whereIn('id', $people)
                        )->orWhereHasMorph('model', [Family::class], fn($query) => $query->where('id', $id)
                        );
                    }
                })
            )
            ->where('user_id', $this->currentCall->user_id)
            ->where('id', '<>', $this->currentCall->id)
            ->orderBy('created_at', 'desc')
            ->with([
                'phoneModel.model',
                'diaries.proposal.people.father',
                'diaries.proposal.people.mother'
            ]);
    }

    public function table(Table $table): Table
    {
        return $table->query($this->getQuery())
            ->paginationPageOptions([10, 25, 50, 100])
            ->queryStringIdentifier('recent-calls')
            ->defaultPaginationPageOption(10)
            ->recordClasses('[&>div>div]:py-0 [&>div>div>div>div]:px-0 ')
            ->columns([
                Columns\Layout\Grid::make(1)->schema([
                    Columns\ViewColumn::make('cell')
                        ->extraAttributes([
                            'class' => 'w-full',
                        ])
                        ->view('filament.tables.columns.call-cell')
                ])
            ])
            ->filters([
                Filter::make('only_answered')
                    ->label('נענו')
                    ->default(true)
                    ->query(fn (Builder $query) => $query->whereNotNull('started_at')),
                Filter::make('only_with_diaries')
                    ->label('עם תיעוד')
                    ->default(true)
                    ->query(fn (Builder $query) => $query->whereHas('diaries'))
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }
}
