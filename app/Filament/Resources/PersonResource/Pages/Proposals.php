<?php

namespace App\Filament\Resources\PersonResource\Pages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\PersonResource;
use App\Filament\Resources\ProposalResource;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Proposals extends ManageRelatedRecords
{
    protected static string $resource = PersonResource::class;

    protected static string $relationship = 'proposalContacts';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-lamp-charge';

    protected static ?string $title = 'הצעות';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(ProposalResource::getColumns(true))
            ->filters([
                //
            ])
            ->headerActions([
                //                Tables\Actions\CreateAction::make()
                //                    ->label('הוסף הצעה')
                //                    ->using(function ($data, $livewire) {
                //                        $key = $livewire->record->gender === 'G' ? 'girl_id' : 'guy_id';
                //
                //                        return Proposal::create(array_merge([
                //                            'created_by' => auth()->user()->id,
                //                            $key => $livewire->record->id,
                //                        ], $data));
                //                    }),
                //                Tables\Actions\AssociateAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ProposalResource::getAddDiaryAction(),
                    ProposalResource::getAddDiaryAction('guy'),
                    ProposalResource::getAddDiaryAction('girl'),
                ]),
                ProposalResource::getCloseProposalAction(),
                ViewAction::make()
                    ->visible(fn ($record) => $record->access)
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.families.resources.proposals.view', $record->id)),
                //                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DissociateAction::make(),
                //                Tables\Actions\DeleteAction::make(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query
                    ->withAccess()
                    ->with('people.schools');
            })
            ->toolbarActions([
                BulkActionGroup::make([
                    //                    Tables\Actions\DissociateBulkAction::make(),
                    //                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
