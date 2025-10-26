<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use App\Filament\Resources\Proposals\Pages\Diaries;
use App\Models\Family;
use Filament\Forms\Components\Textarea;
use App\Models\Person;
use App\Models\Proposal;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ActiveCallDrawer extends Component implements HasForms
{
    use InteractsWithForms;

    public string $activeTab = 'proposals';

    public $currentCall;

    public $hiddenHeader = false;

    public array $data = [];

    public bool $showHiddenProposals = false;

    public string $sideFamilyParent = 'G';

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.active-call-drawer');
    }

    public function updatedSideFamilyParent()
    {
        unset($this->person);
        unset($this->proposals);
        unset($this->children);
    }

    #[Computed]
    public function call()
    {
        return $this->currentCall;
    }

    #[Computed]
    public function person(): ?Person
    {
        $model = $this->call?->phoneModel?->model;

        if(!$model) {
            return null;
        }

        if($model instanceof Person) {
            return $model;
        }

        /** @var Family $model */

        return $model->{$this->sideFamilyParent === 'B' ? 'husband': 'wife'};
    }

    #[Computed]
    public function proposals()
    {
        if (!$this->person) {
            return collect();
        }

        return $this->person->proposalContacts()
            ->when($this->showHiddenProposals, fn ($query) => $query->withoutGlobalScope('withoutHidden'))->get();
    }

    #[Computed]
    public function children()
    {
        if (!$this->person) {
            return collect();
        }

        return $this->person->children;
    }

    #[On('reset-drawer-call')]
    public function resetCall(): void
    {
        unset($this->call);
    }

    public function isFamilyPhone(): bool
    {
        return $this->call?->phoneModel?->model instanceof Family;
    }

    public function toggleHiddenProposals()
    {
        $this->showHiddenProposals = !$this->showHiddenProposals;

        unset($this->proposals);
    }

    function getForms(): array
    {
        return $this->proposals->mapWithKeys(function (Proposal $proposal) {

            $side = $this->children->where('id', $proposal->guy_id)->isEmpty() ? 'girl' : 'guy';

            if(!data_get($this->data, $proposal->id)){
                $this->data[$proposal->id] = [
                    'description' => '',
                    'statuses' => [
                        'proposal' => $proposal->status,
                        $side => $proposal->{'status_'. $side},
                    ],
                ];
            }

            return ['form-'. $proposal->id => Schema::make($this)
                ->statePath('data.'. $proposal->id)
                ->components([
                    Textarea::make('description')
                        ->label('תיאור')
                        ->rules(['required', 'min:20'])
                        ->extraAlpineAttributes([
                            //ctrl+enter to submit
                            'wire:keydown.ctrl.enter' => 'saveProposalDiary('. $proposal->id .')',
                        ])
                        ->helperText(new HtmlString('<div>
<div>מינימום 20 אותיות</div>
<div>לחץ <b>Enter</b> ע"מ לרדת שורה ו <b>Ctrl+Enter</b> ע"מ לשלוח את הטופס</div>
</div>'))
                        ->validationMessages([
                            'required' => 'שדה תיאור הינו שדה חובה',
                            'min' => 'מה אתה רציני?!... תיאור פחות מ20 אותיות?! :)',
                        ])
                        ->markAsRequired()
                        ->autosize(),
                    $proposal->statusField()
                        ->hintAction(Action::make('reset_status_'. $proposal->id)
                            ->tooltip('איפוס')
                            ->extraAttributes([
                                'class' => 'hidden',
                                'wire:dirty.class' => '!block',
                                'wire:target' => 'data.'. $proposal->id . '.statuses.proposal',
                            ])
                            ->iconButton()
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->action(fn (Set $set) => $set('statuses.proposal', $proposal->status))
                        ),
                    $proposal->itemStatusField($side)
                        ->hintAction(Action::make('reset_status_'. $proposal->id)
                            ->tooltip('איפוס')
                            ->extraAttributes([
                                'class' => 'hidden',
                                'wire:dirty.class' => '!block',
                                'wire:target' => 'data.'. $proposal->id . '.statuses.' . $side,
                            ])
                            ->iconButton()
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->action(fn (Set $set) => $set('statuses.' . $side, $proposal->{'status_'. $side}))
                        ),
                ])
            ];
        })->toArray();
    }

    public function getProposalForm(Proposal $proposal): Schema
    {
        return $this->{'form-'. $proposal->id};
    }

    public function getRelevantChildren()
    {
        if(!$this->call) {
            return [];
        }
        return $this->children
            ->whereNull('spouse_id')
            ->filter(fn ($child) => $child->age > 17);
    }

    public function getProposalsFor(Person $child)
    {
        $column = $child->gender === 'B' ? 'guy_id' : 'girl_id';

        return $this->proposals
            ->where($column, $child->id);
    }

    public function saveProposalDiary(int $proposalId): void
    {
        $proposal = $this->proposals->findOrFail($proposalId);
        $data = $this->getProposalForm($proposal)->validate()['data'][$proposalId];

        $side = $this->children->where('id', $proposal->guy_id)->isEmpty() ? 'girl' : 'guy';

        $diary = Diaries::createNewDiary([
            'type' => 'call',
            'statuses' => $data['statuses'],
            'data' => [
                'spokenSide' => $this->isFamilyPhone() ? $this->sideFamilyParent : null,
                'description' => $data['description'],
                'call_id' => $this->call->id,
                'participants' => $this->call->phoneModel?->model?->id,
            ],
        ], $proposal, $side);

        $proposal->refresh();

        $this->data[$proposalId] = [
            'description' => '',
            'statuses' => [
                'proposal' => $proposal->status,
                $side => $proposal->{'status_'. $side},
        ]];

        if($diary) {
            Notification::make()
                ->title('התיעוד נשמר בהצלחה')
                ->success()
                ->send();
        }
    }

}
