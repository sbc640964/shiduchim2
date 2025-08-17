<?php

namespace App\Filament\Clusters\Settings\Resources\TimeSheets\Pages;

use App\Filament\Clusters\Settings\Resources\TimeSheets\TimeSheetsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeSheets extends CreateRecord
{
    protected static string $resource = TimeSheetsResource::class;
}
