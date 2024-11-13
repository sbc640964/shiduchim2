<?php

namespace app\Filament\Clusters\Settings\Resources\SchoolResource\Pages;

use app\Filament\Clusters\Settings\Resources\SchoolResource;
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
