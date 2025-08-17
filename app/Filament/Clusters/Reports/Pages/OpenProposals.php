<?php

namespace App\Filament\Clusters\Reports\Pages;
use Filament\Pages\Dashboard;
use App\Filament\Clusters\Reports\Pages\OpenProposalPage\OpenProposalsOverview;
use App\Filament\Clusters\Reports\Pages\OpenProposalPage\OpenProposalsTable;
use Filament\Schemas\Schema;
use App\Filament\Clusters\Reports\ReportsCluster;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class OpenProposals extends Dashboard
{
    use HasFiltersForm;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $title = 'הצעות פתוחות';

    protected static string $routePath = 'open-proposals';

    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_reports');
    }

    public function getWidgets(): array
    {
        return [
            OpenProposalsOverview::make(),
            OpenProposalsTable::make(),
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateRangePicker::make('dates')
                    ->icon('heroicon-s-x-mark')
                    ->disableClear(false)
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
