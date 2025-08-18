<?php

namespace App\Filament\Tables;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Person;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        /*
         * ->getSearchResultsUsing(fn (string $search, Person $person) => Person::searchName($search, $person->gender === 'B' ? 'G' : 'B')
                ->single()
                ->with('father')
                ->limit(50)
                ->get()
                ->pluck('select_option_html', 'id')
//      )
         */


        return $table
            ->paginationPageOptions([5])
            ->query(fn (): Builder => Person::query()
                ->single()
                ->with(StudentResource::withRelationship())
            )
            ->modifyQueryUsing(function (Builder $query) use ($table) {
                if ($gender = $table->getArguments()['gender'] ?? null) {
                    $query->whereGender($gender ?? 'B');
                }
                StudentResource::modifyTableQuery($query);
            })
            ->columns(StudentResource::getTableColumns())
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
