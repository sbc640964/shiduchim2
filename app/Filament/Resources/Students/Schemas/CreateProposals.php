<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Person;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CreateProposals
{

    public static function configure(Schema $schema, Person $person, Collection $proposalsPeople): Schema
    {

        return $schema
            ->components([
                Repeater::make('proposalsPeople')
                    ->label('הצעות')
                    ->columns(1)
                    ->deletable(false)
                    ->orderColumn(false)
                    ->addable(false)
                    ->schema(function () use ($person) {
                        return [
                            Hidden::make('id'),
                            Group::make(function ($state) use ($person) {
                                $alerts = static::getAlertsProposal($state, $person);
                                return [
                                    TextEntry::make('first_name')
                                        ->label('שם')
                                        ->inlineLabel()
                                        ->weight(FontWeight::Bold)
                                        ->formatStateUsing(fn () => $state['first_name'] . ' ' . ($state['last_name'] ?? '')),
                                    TextEntry::make('father.first_name')
                                        ->inlineLabel()
                                        ->label('שם האב'),
                                    TextEntry::make('mother.first_name')
                                        ->label('שם האם')
                                        ->inlineLabel(),
                                    TextEntry::make('alerts')
                                        ->state($alerts)
                                        ->hiddenLabel()
                                        ->badge()
                                        ->icon(Heroicon::ExclamationTriangle)
                                        ->color('danger')
                                        ->size(TextSize::Medium),
                                    Toggle::make('remove_proposal')
                                        ->visible(fn ($state) => count($alerts) > 0)
                                        ->label('הסר הצעה זו')
                                        ->default(true),
                                ];
                            })->extraAttributes(['class' => '[&>div]:gap-y-2']),
                        ];
                    })
                    ->minItems(1),
            ]);
    }

    private static function getAlertsProposal($proposal, Person $person): array
    {
        $alerts = [];

        $person->loadMissing('father', 'mother');

        $personA = [$person->first_name, $person->gender === 'G' ? ($proposal['mother']['first_name'] ?? null) : ($proposal['father']['first_name'] ?? null)];
        $personB = [$proposal['first_name'], $proposal['gender'] === 'G' ? $person->mother->first_name : $person->father->first_name];

        $matchedNames = [
            'record' => $personA,
            'proposal' => $personB,
        ];

        foreach ($matchedNames as $key => $names) {
            $namesA = explode(' ', $names[0]);
            $namesB = explode(' ', $names[1]);

            $has = array_intersect($namesA, $namesB);

            if ($has) {
                $gender = $key === 'record' ? $person->gender : $proposal['gender'];
                $alerts[] = ($gender === 'G'
                    ? 'שם פרטי זהה בין המדוברת לאם המדובר'
                    : 'שם פרטי זהה בין המדובר לאבי המדוברת (') . \Arr::join($has, ', ') . ')';
            }
        }

        return $alerts;
    }
}
