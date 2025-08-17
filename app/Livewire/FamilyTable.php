<?php

namespace App\Livewire;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Filament\Resources\ProposalResource\Pages\Family;
use App\Models\Person;
use App\Models\Proposal;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class FamilyTable extends Component implements HasTable, HasForms, HasInfolists, HasActions
{
    use InteractsWithActions;
    use InteractsWithInfolists;
    use InteractsWithForms;
    use InteractsWithTable;

    public Person $person;
    public ?Proposal $proposal = null;
    public ?string $side = null;
    public function render()
    {
        return view('livewire.family-table');
    }

    public function mount(Person $person, ?Proposal $proposal = null, ?string $side = null)
    {
        $this->person = $person;
        $this->proposal = $proposal;
        $this->side = $side;
    }

    public function table(Table $table): Table
    {
        return Family::familyTable($table, $this->proposal, $this->side, false)
            ->query($this->person->family->children()->getQuery());
    }
}
