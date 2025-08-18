<?php

namespace App\Livewire;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\ViewColumn;
use App\Models\Call;
use App\Models\Family;
use App\Models\Person;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ActiveCallLastCalls extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
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
                        $people = $this->currentCall->phoneModel->model->people()->pluck('id')->toArray();
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
                Grid::make(1)->schema([
                    ViewColumn::make('cell')
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
            ->recordActions([
                // ...
            ])
            ->toolbarActions([
                // ...
            ]);
    }

    public function getTooltip($proposal): string
    {
        $route = route('filament.families.resources.proposals.view', ['record' => $proposal['proposal_id']]);

        return <<<HTML
        <div class='text-xs grid grid-cols-2 divide-x rtl:divide-x-reverse gap-x-4 gap-y-1'>
            <div class='p-1 pe-2'>
                <div class='font-semibold'>הבחור</div>
                <p class='font-normal'>{$proposal['guy_info']}</p>
            </div>
            <div class='p-1 ps-2'>
                <div class='font-semibold'>הבחורה</div>
                <p class='font-normal'>{$proposal['girl_info']}</p>
            </div>
            <div class="col-span-2 text-center text-xs text-gray-500 !border-s-0">
                <span class="font-semibold">סטטוס:</span> {$proposal['status']}
                <a
                    href='$route'
                    wire:navigate
                    class='text-xs text-blue-600 hover:text-blue-800 flex items-center'
                >
                    <x-icon name='lucide-eye' class='inline size-4 me-1' />
                    הצג פרטים
                </a>
        </div>
    </div>
HTML;
    }
}
