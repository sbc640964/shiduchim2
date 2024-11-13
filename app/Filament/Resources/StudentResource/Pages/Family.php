<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\ProposalResource\Pages\Family as ProposalResourceFamily;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class Family extends ProposalResourceFamily
{
    protected static string $resource = StudentResource::class;


    public function getRelationshipByTabName($name, ?bool $forceReturnRelationship = true, ?bool $skipOnLaw = false): Relation|Builder|null
    {
        $return = match ($name) {
            'father_siblings' => $this->unionDirectsWithInLaws($this->getOwnerRecord()->father?->parentsFamily, $skipOnLaw),
            'mother_siblings' => $this->unionDirectsWithInLaws($this->getOwnerRecord()->mother?->parentsFamily, $skipOnLaw),
            'parents' => $this->getOwnerRecord()?->parentsFamily?->people(),
            'siblings' => $this->unionDirectsWithInLaws($this->getOwnerRecord()->parentsFamily, $skipOnLaw),
            'grandparents' => $this->getOwnerRecord()?->grandparents(),
            default => null
        };


        if ($forceReturnRelationship && ! $return instanceof Relation && ! $return instanceof Builder) {
            $return = $this->getOwnerRecord()->parentsFamily?->children();
        }


        return $return;
    }
}
