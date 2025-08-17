<?php

namespace App\Filament\Clusters\Settings\Resources\Schools\Pages;

use App\Filament\Clusters\Settings\Resources\Schools\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }
}
