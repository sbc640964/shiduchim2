<?php

namespace App\Filament\Resources\Students\Pages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Proposal;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageProposals extends ManageRelatedRecords
{
    protected static string $resource = StudentResource::class;

    protected static string $relationship = 'proposals';

    protected static string | \BackedEnum | null $navigationIcon = '';

    protected static ?string $title = 'הצעות';

    protected static ?string $navigationLabel = 'הצעות';

    public function isGirl(): bool
    {
        return $this->record->gender === 'G';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make($this->isGirl() ? 'guy_id' : 'girl_id')
                    ->relationship($this->isGirl() ? 'guy' : 'girl', 'id')
                    ->searchable(['first_name', 'last_name'])
                    ->placeholder('בחר תלמיד')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->select_option_html)
                    ->allowHtml()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->options([
                        'pending' => 'ממתין לאישור',
                        'approved' => 'אושר',
                        'rejected' => 'נדחה',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return ProposalResource::tableFilters($table)
            ->recordTitle(fn (Proposal $proposal) => $proposal->families_names)
            ->columns([
                ...ProposalResource::getColumns(true)
            ])
            ->headerActions([

            ])
            ->recordClasses(fn (Proposal $proposal) => [
                "bg-red-50 hover:bg-red-100" => $proposal->hidden_at,
//                "relative before:content-[''] before:border-s-[6px] before:z-20 before:border-red-600 before:h-full before:absolute before:start-0" => $proposal->hidden_at,
            ])
            ->toolbarActions(ProposalResource::getBulkActions())
            ->recordActions([
                ActionGroup::make([
                    ProposalResource::getAddDiaryAction(),
                    ProposalResource::getAddDiaryAction('guy'),
                    ProposalResource::getAddDiaryAction('girl'),
                ]),
                ...ProposalResource::showHideActions(),
                ProposalResource::getCloseProposalAction(),
                ViewAction::make()
                    ->visible(fn ($record) => $record->access)
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.families.resources.proposals.view', $record->id)),
                DeleteAction::make()
                    ->label('מחק')
                    ->iconButton()
                    ->icon('iconsax-bul-trash')
                    ->before(fn (Proposal $record) => $record->deleteDependencies())
                    ->tooltip('מחק הצעה')
                //                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DissociateAction::make(),
                //                Tables\Actions\DeleteAction::make(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query
                    ->withAccess()
                    ->with('lastGuyDiary', 'lastGirlDiary', 'people.schools');
            });
    }

    public function inverseGender(): string
    {
        return $this->getRecord()->gender === 'B' ? 'G' : 'B';
    }
}
