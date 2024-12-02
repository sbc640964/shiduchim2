<?php

namespace App\Services\Imports\Students;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class Importer implements WithHeadingRow
{
    use Importable;

    static function fields(): array
    {
        return [
            [
                'name' => 'married_code',
                'label' => 'קוד נישואין',
                'rules' => ['string', 'nullable'],
                'guesses' => ['קוד נישואין', 'נשוי', 'נשואה'],
            ],
            [
                'name' => 'external_code',
                'label' => 'מזהה אישי',
                'rules' => ['integer', 'nullable'],
                'guesses' => ['מזהה אישי', "מס'", 'מספר אישי', 'מספר', 'קוד תלמיד'],
            ],
            [
                'name' => 'first_name',
                'label' => 'שם פרטי',
                'rules' => ['string', 'nullable'],
                'guesses' => ['שם פרטי', 'שם פרטי תלמיד', 'שם פרטי תלמידה', 'שם פרטי תלמיד/ה'],
            ],
            [
                'name' => 'last_name',
                'label' => 'שם משפחה',
                'rules' => ['string', 'nullable'],
                'guesses' => ['משפחה', 'שם משפחה', "משפ'"],
            ],
            [
                'name' => 'phone',
                'label' => 'טלפון',
                'rules' => ['string', 'nullable'],
                'guesses' => ['טלפון', 'טלפון בית'],
            ],
            [
                'name' => 'mother_phone',
                'label' => 'טלפון אמא',
                'rules' => ['string', 'nullable'],
                'guesses' => ['נייד אם', 'טלפון אם', 'פלאפון אם'],
            ],
            [
                'name' => 'father_phone',
                'label' => 'טלפון אבא',
                'rules' => ['string', 'nullable'],
                'guesses' => ['נייד אב', 'טלפון אב', 'פלאפון אב'],
            ],
            [
                'name' => 'school',
                'label' => 'בית ספר',
                'rules' => ['string', 'nullable'],
                'guesses' => ['בית ספר', 'מוסד לימודים'],
            ],
            [
                'name' => 'prev_school',
                'label' => 'בית ספר קודם',
                'rules' => ['string', 'nullable'],
                'guesses' => ['קודם', 'בית ספר קודם'],
            ],
            [
                'name' => 'mother_name',
                'label' => 'שם אמא',
                'rules' => ['string', 'nullable'],
                'guesses' => ['שם אמא', 'שם אם'],
            ],
            [
                'name' => 'born_date',
                'label' => 'תאריך לידה',
                'rules' => ['date', 'nullable'],
                'guesses' => ['ת.ז.', 'ת.ז. לועזי', 'תאריך לידה'],
            ],
            [
                'name' => 'address',
                'label' => 'כתובת',
                'rules' => ['string', 'nullable'],
                'guesses' => ['כתובת', 'כתובת מגורים'],
            ],
            [
                'name' => 'city',
                'label' => 'עיר',
                'rules' => ['string', 'nullable'],
                'guesses' => ['עיר', 'ישוב'],
            ],
            [
                'name' => 'gender',
                'label' => 'מין',
                'rules' => ['string'],
                'guesses' => ['מין', "בן/בת"],
            ],
            [
                'name' => 'class',
                'label' => 'כיתה',
                'rules' => ['string', 'nullable'],
                'guesses' => ['כיתה', 'שיעור'],
            ],
            [
                'name' => 'synagogue',
                'label' => 'בית כנסת אבא',
                'rules' => ['string', 'nullable'],
                'guesses' => ['בית כנסת אבא', 'בית כנסת', 'שטיבל'],
            ],
            [
                'name' => 'code_ichud',
                'label' => 'קוד איחוד',
                'rules' => ['string', 'nullable'],
                'guesses' => ['קוד איחוד', 'איחוד'],
            ],
            [
                'name' => 'father_code_ichud',
                'label' => 'קוד איחוד אבא',
                'rules' => ['string', 'nullable'],
                'guesses' => ['קוד איחוד אבא', 'איחוד אבא'],
            ],
            [
                'name' => 'mothers_father_code_ichud',
                'label' => 'קוד איחוד הורי אם',
                'rules' => ['string', 'nullable'],
                'guesses' => ['קוד איחוד הורי אם', 'איחוד הורי אם'],
            ]
        ];
    }
}
