<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Forms\Components;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }

    protected function getSteps(): array
    {
        return [
            Step::make('פרטי תלמיד')
                ->description('הזן פרטים כללים על התלמיד')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('gender')
                        ->label('מין')
                        ->required(),

                    Components\TextInput::make('first_name')
                        ->label('שם פרטי')
                        ->string()
                        ->required(),

                    Components\TextInput::make('last_name')
                        ->label('שם משפחה')
                        ->string()
                        ->required(),
                ]),
        ];
    }
}
