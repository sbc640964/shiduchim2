<?php

namespace App\Filament\Resources\StudentResource\Pages;

use DB;
use Arr;
use Exception;
use App\Filament\Resources\StudentResource;
use App\Models\Person;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected static ?string $navigationLabel = 'ערוך תלמיד';

    protected static string | \BackedEnum | null $navigationIcon = '';

    protected function getActions(): array
    {
        return [
            //DeleteAction::make(),
        ];
    }

    public function handleRecordUpdate(Model|Person $record, array $data): Model
    {

        DB::beginTransaction();

        try {
            $record->load('parentsFamily');

            $record->fill(Arr::only($data, [
                'first_name',
                'last_name',
                'parents_family_id',
                'born_at',
                'gender',
                'father_id',
                'mother_id',
                'info'
            ]));

            $record->info_private = array_merge(($record->info_private ?? []), $data['info_private']);

            $patentsFamilyChanged = $record->isDirty('patents_family_id') ||
                $record->isDirty('father_id') ||
                $record->isDirty('mother_id');

            $record->save();

            $record->refresh();

            $record->parentsFamily->update(array_filter([
                'address' => $data['family_address'] ?? null,
                'city_id' => $data['family_city_id'] ?? null,
            ], fn($value) => $value !== null));

            if($record->parentsFamily->husband) {
                /* @var Person $father */
                $father = $record->parentsFamily->husband;

                ($data['father_first_name'] ?? null) && $father->update([
                    'first_name' => $data['father_first_name'],
                ]);

                ($data['father_synagogue_id'] ?? null) &&
                    $father->schools()->syncWithoutDetaching([
                        $data['father_synagogue_id']
                    ]);
            }

            if($record->parentsFamily->wife && ($data['mother_first_name'] ?? null)) {
                $record->parentsFamily->wife->update([
                    'first_name' => $data['mother_first_name'],
                ]);
            }


            if($patentsFamilyChanged) {
                $record->load(['proposals' => fn ($q) => $q->with(['contacts' => fn(MorphToMany $query) =>
                    $query
                        ->wherePivotIn('type', ['אמא', 'אבא'])
                        ->wherePivot('side', $record->gender === 'G' ? 'girl' : 'guy')
                ])]);

                $record->proposals->each(function ($proposal) use ($record) {
                    $proposal->contacts->each(function ($contact) use ($record) {
                        $contact->pivot->person_id = $contact->pivot->type === 'אמא'
                            ? $record->mother_id
                            : $record->father_id;
                        $contact->pivot->save();
                    });
                });
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $record;
    }
}
