<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use App\Exports\ProposalsExport;
use App\Filament\Exports\ProposalExporter;
use App\Filament\Resources\ProposalResource;
use App\Models\Proposal;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListProposals extends ListRecords
{
    protected static string $resource = ProposalResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth('sm'),
            ActionGroup::make([
                Action::make('export')
                    ->hidden(!auth()->user()->can('export_proposals'))
                    ->label('יצוא')
                    ->action(function () {
                        return Excel::download(new ProposalsExport, 'proposals.xlsx');
                    })
                    ->icon('iconsax-bul-document-download')
            ])
        ];
    }

    public function getTabs(): array
    {
        return [
            ...(auth()->user()->can('view_other_proposals') ? ['all' => Tab::make('כל ההצעות')] : []),
            'my' => Tab::make('כל ההצעות שלי'),
            'today' => Tab::make('לטיפול היום')
                ->icon('iconsax-bul-timer-1')
                ->modifyQueryUsing(fn ($query) => $query->whereNextTimeToday())
                ->badge(Proposal::whereNextTimeToday()->count())
                ->badgeColor('success'),
            'past' => Tab::make('זמן טיפול עבר')
                ->modifyQueryUsing(fn ($query) => $query->whereNextTimePast())
                ->badge(Proposal::whereNextTimePast()->count())
                ->badgeColor('danger')
                ->icon('iconsax-bul-timer-1'),
        ];
    }
}
