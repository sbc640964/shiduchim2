<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProposal extends EditRecord
{
    protected static string $resource = ProposalResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
