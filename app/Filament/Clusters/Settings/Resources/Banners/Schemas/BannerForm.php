<?php

namespace App\Filament\Clusters\Settings\Resources\Banners\Schemas;

use App\Filament\Clusters\Settings\Resources\Banners\Utilities\Location;
use App\Models\Banner;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make()
                    ->components([
                        Tabs\Tab::make('general')
                            ->label('General')
                            ->columns(2)
                            ->components([
                                TextInput::make('name')->columnSpanFull(),
                                TextInput::make('heading'),
                                TextInput::make('link')
                                    ->label('Link')
                                    ->placeholder('השאר ריק כדי לא להוסיף קישור')
                                    ->url(),
                                RichEditor::make('body')->columnSpanFull(),
                                DateTimePicker::make('published_at')
                                    ->placeholder('מייד')
                                    ->native(false),
                                DateTimePicker::make('expires_at')->native(false),
                                Select::make('locations')
                                    ->columnSpanFull()
                                    ->multiple()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            $currentLocationData = collect($get('locations_data') ?? [])->values()->filter(fn ($location) => is_array($location));

                                            $currentLocationData->map(function ($location) {
                                                $location['conditions'] = array_values($location['conditions'] ?? []);
                                                return $location;
                                            });

                                            foreach ($state as $location) {
                                                //if current location data not contains element with location same location create new element in location_data with this location
                                                $isInclude = $currentLocationData->firstWhere('location', $location);
                                                if(!$isInclude) {
                                                    $currentLocationData->push([
                                                        'location' => $location,
                                                        'conditions' => []
                                                    ]);
                                                }
                                            }

                                            //remove location from current location data if it not in state
                                            $currentLocationData = $currentLocationData->filter(fn ($location) => in_array($location['location'], $state));

                                            $set('locations_data', $currentLocationData->toArray());
                                        } else {
                                            $set('locations_data', []);
                                        }
                                    })
                                    ->live()
                                    ->options(Location::$locations),
                            ]),

                        Tabs\Tab::make('conditions')
                            ->hidden(fn (Get $get) => !$get('locations'))
                            ->label('Conditions')
                            ->components(static::conditionsConfigure()),
                        Tabs\Tab::make('style')
                            ->label('Style')
                            ->components(static::styleConfigure())
                    ]),
            ]);
    }

    private static function getAvailableScopes($location)
    {

    }

    private static function conditionsConfigure(): array
    {
        return [
            Repeater::make('locations_data')
                ->columnSpanFull()
                ->minItems(0)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->itemLabel(fn ($state) => Location::getLabel($state['location']))
                ->components([
                    Hidden::make('location'),
                    Select::make('scope')
                        ->options(fn (Get $get) => collect(Filament::getResources())->mapWithKeys(fn ($resource) => [$resource => $resource::getNavigationLabel()])->toArray()),
                    //static::getAvailableScopes($get('locations'))
                    Repeater::make('conditions')
                        ->table([]),
                ]),
        ];
    }

    private static function styleConfigure(): array
    {
        return [
            FileUpload::make('config.style.image')
                ->image()
                ->directory('banners_images'),
            Select::make('config.style.icon')
                ->searchable()
                ->options(collect(Heroicon::cases())->mapWithKeys(fn ($icon) => [
                    $icon->value => view('filament.components.icon-picker.item', [
                        'label' => __('icon_picker.icons.'.$icon->name),
                        'icon' => $icon->name,
                        'set' => Heroicon::class,
                    ])->render(),
                ]))
                ->allowHtml()
            ,

            Fieldset::make('colors')
                ->components([
                    ColorPicker::make('config.style.background_color'),
                    ColorPicker::make('config.style.text_color'),
                    ColorPicker::make('config.style.icon_color'),
                    ColorPicker::make('config.style.border_color'),
                ]),
            TextInput::make('config.style.border_width')->numeric()->suffix('px'),
        ];
    }
}
