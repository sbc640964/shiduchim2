<?php

namespace App\Filament\Clusters\Reports\Pages\OpenProposalPage;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use App\Models\Proposal;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OpenProposalsTable extends BaseWidget
{
    use FiltersOpenProposalsPage;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('ההצעות הפתוחות')
            ->query(
                $this->baseQuery()
                    ->with(['createdByUser', 'people.father', 'people.mother', 'people.parentsFamily'])
            )
            ->columns([
                TextColumn::make('createdByUser.name')
                    ->searchable()
                    ->icon('heroicon-o-user-circle')
                    ->weight(FontWeight::Bold)
                    ->label('שדכן'),
                TextColumn::make('guy.full_name')
                    ->searchable(['first_name', 'last_name'])
                    ->label('בחור')
                    ->description(fn (Proposal $proposal) => $proposal->guy->parents_info),
                TextColumn::make('girl.full_name')
                    ->searchable(['first_name', 'last_name'])
                    ->label('בחורה')
                    ->description(fn (Proposal $proposal) => $proposal->girl->parents_info),
                TextColumn::make('opened_at')
                    ->label('תאריך פתיחה')
                    ->date('d/m/Y H:i:s'),
                TextColumn::make('closed_at')
                    ->label('תאריך סגירה')
                    ->date('d/m/Y H:i:s'),
                TextColumn::make('status_open')
                    ->label('סטטוס')
                    ->sortable()
                    ->color(fn (Proposal $record) => $record->closed_at ? 'danger' : 'success')
                    ->badge(),
                TextColumn::make('reason_closed')
                    ->label('סיבת סגירה')
                    ->searchable()
                    ->width(300)
                    ->wrap()
            ])
            ->recordActions([
                Action::make('edit-reason')
                    ->action(fn () => null)
                    ->modalWidth(Width::Small)
                    ->icon('heroicon-o-pencil')
                    ->tooltip('ערוך סיבת סגירה')
                    ->iconButton()
                    ->color('gray')
                    ->size('xs')
                    ->modalHeading('ערוך סיבת סגירה')
                    ->modalSubmitActionLabel('עדכן')
                    ->schema([
                        Textarea::make('reason')
                            ->rows(6)
                            ->default(fn (Proposal $proposal) => $proposal->reason_closed)
                            ->label('סיבת סגירה')
                    ])
            ]);
    }
}
