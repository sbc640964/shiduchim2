<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }
}
