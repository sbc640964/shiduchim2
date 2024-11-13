<?php

namespace App\Filament\Resources\PersonResource\Pages;

use App\Filament\Resources\PersonResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\Family;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->createFamilyWithPeople($data);
    }

    private function createFamilyWithPeople(array $data)
    {
        $parentsFamily = Family::find($data['parents_family_id']);
        $spouseParentsFamily = Family::find($data['spouse_parents_family_id']);

        $family = Family::create([
            'name' => $data['last_name'],
            'address' => $data['address'],
            'city_id' => $data['city_id'],
            'status' => 'married', // default status to 'married'
        ]);

        $one = $family->people()->create([
            'gender' => 'B',
            'external_code' => $data['external_code'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'current_family_id' => $family->id,
            'parents_family_id' => $parentsFamily->id ?? null,
            'father_id' => $parentsFamily->husband->id ?? null,
            'mother_id' => $parentsFamily->wife->id ?? null,
            'father_in_law_id' => $spouseParentsFamily->husband->id ?? null,
            'mother_in_law_id' => $spouseParentsFamily->wife->id ?? null,
        ]);

        $two = $family->people()->create([
            'gender' => 'G',
            'first_name' => $data['wife_first_name'],
            'last_name' => $data['last_name'],
            'current_family_id' => $family->id,
            'parents_family_id' => $spouseParentsFamily->id ?? null,
            'father_id' => $spouseParentsFamily->husband->id ?? null,
            'mother_id' => $spouseParentsFamily->wife->id ?? null,
            'father_in_law_id' => $parentsFamily->husband->id ?? null,
            'mother_in_law_id' => $parentsFamily->wife->id ?? null,
            'spouse_id' => $one->id,
        ]);

        $one->update(['spouse_id' => $two->id]);

        return $one;
    }
}
