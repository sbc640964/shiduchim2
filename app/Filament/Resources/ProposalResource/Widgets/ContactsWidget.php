<?php

namespace App\Filament\Resources\ProposalResource\Widgets;

use App\Filament\Actions\Call;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;

class ContactsWidget extends BaseWidget
{
    use InteractsWithRecord;

    #[Locked]
    public ?string $side = null;

    protected int|string|array $columnSpan = 1;

    public function getLabel()
    {
        return match ($this->side) {
            'guy' => 'בחור',
            'girl' => 'בחורה',
            default => ''
        };
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('שם מלא'),
                Tables\Columns\TextColumn::make('pivot.type')->label('תיאור הקשר'),
            ])
            ->actions([
                Call::tableActionDefaultPhone($this->getOwnerRecord(), $this->side),
                Call::tableAction($this->getOwnerRecord(), $this->side),
            ])
            ->heading('אנשי קשר של ה'.$this->getLabel())
            ->paginationPageOptions([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->relationship(fn (): Relation|Builder => $this->getRelationship());

    }

    //    public function getRecord() {
    //
    //    }

    private function getRelationship()
    {
        return $this->getRecord()->contacts()->wherePivot('side', $this->side);
    }

    public function getOwnerRecord()
    {
        return $this->getRecord();
    }
}
