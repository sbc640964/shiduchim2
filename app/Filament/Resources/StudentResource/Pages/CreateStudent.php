<?php

namespace App\Filament\Resources\StudentResource\Pages;

use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\StudentResource;
use Filament\Forms\Components;
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
                    TextInput::make('gender')
                        ->label('מין')
                        ->required(),

                    TextInput::make('first_name')
                        ->label('שם פרטי')
                        ->string()
                        ->required(),

                    TextInput::make('last_name')
                        ->label('שם משפחה')
                        ->string()
                        ->required(),
                ]),
        ];
    }
}
