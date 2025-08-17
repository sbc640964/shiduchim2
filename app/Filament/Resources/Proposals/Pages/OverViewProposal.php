<?php

namespace App\Filament\Resources\Proposals\Pages;

use App\Filament\Resources\Proposals\ProposalResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class OverViewProposal extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProposalResource::class;

    protected string $view = 'filament.resources.proposal-resource.pages.over-view-proposal';

    protected static ?string $title = 'סקירה כללית';

    protected static ?string $navigationLabel = 'סקירה כללית';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }
}
