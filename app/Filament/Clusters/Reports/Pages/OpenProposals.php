<?php

namespace App\Filament\Clusters\Reports\Pages;
use App\Filament\Clusters\Reports;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class OpenProposals extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Reports::class;

    protected static ?string $title = 'הצעות פתוחות';

    protected static string $routePath = 'open-proposals';

    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_reports');
    }

    public function getWidgets(): array
    {
        return [
            Reports\Pages\OpenProposalPage\OpenProposalsOverview::make(),
            Reports\Pages\OpenProposalPage\OpenProposalsTable::make(),
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DateRangePicker::make('dates')
                    ->placeholder('כל התאריכים')
                    ->label('טווח תאריכים'),
                Select::make('matchmaker')
                    ->label('שדכן')
                    ->placeholder('כל השדכנים')
                    ->searchable()
                    ->options(User::pluck('name', 'id')),
            ]);
    }
}
