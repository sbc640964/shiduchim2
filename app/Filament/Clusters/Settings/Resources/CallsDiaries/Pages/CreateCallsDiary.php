<?php

namespace App\Filament\Clusters\Settings\Resources\CallsDiaries\Pages;

use App\Filament\Clusters\Settings\Resources\CallsDiaries\CallsDiariesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCallsDiary extends CreateRecord
{
    protected static string $resource = CallsDiariesResource::class;
}
