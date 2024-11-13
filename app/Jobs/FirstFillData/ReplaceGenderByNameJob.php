<?php

namespace App\Jobs\FirstFillData;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReplaceGenderByNameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $names = [
        'שרה', 'לאה', 'ברכה', 'חיה', 'שיינדל', 'פייגא', 'צפורה', 'ציפורה', 'יוכבד',
        'הינדא', 'רבקה', 'רחל', 'גולדה', 'חנה', 'מרים', 'פריידא', 'פריידי', 'נעמי', 'מינטשא', 'צביה',
        'יפה', 'דבורה', 'תחיה', 'מלכה', 'גיטל', 'בלה', 'בלה', 'בינה', 'דנה', 'תרצה', 'בלומה', 'אסתר',
        'ביילא', 'איידל', 'רעכיל', 'בתיה', 'רעכיל', 'הינדה', 'חוה', 'דינה', 'יוטל',
        'יהודית', 'שיינא', 'יוטא', 'רייזל', 'ליבא', 'הענא', 'נעמה', 'פעסא', 'סימה',
        'מאשה', 'פעסא', 'אדלה', 'אהובה', 'איטה', 'אטה', 'איילה', 'אילה', 'אילנה', 'אנריקה', 'אנדרה',
        'אילה', 'אראלה', 'בלהה', 'ברוניה', "גב' שמחה", 'גיטה', 'גילה', 'הדסה', 'הנרתה', 'נחמה', 'זהבה', 'זיסל',
        'סופיה', 'חדוה', 'טובה', 'פנינה', 'טליה', 'טשרנה', 'ינטה', 'לביאה', 'לבנה', 'לולה', 'ליפשה',
        'מאשה', 'מגדלנה', 'מילה', 'מילכה', 'מינה', 'מלה', 'מירה', 'מנוחה', 'מרטה',
        'עטרה', 'עליזה', 'סרנה', 'שושנה', 'נינה', 'נחה', 'פוריה', 'פייגה', 'פלה',
        'פסיה', 'פרידה', 'פרומה', 'צופיה', 'צילה', 'צירה', 'קלרה', 'רוחמה', 'רוזה', 'קלרה', 'רשה',
        'שיינה', 'שפרה', 'אורית', 'שלומית', 'רות', 'רונית', 'מרגלית', 'יפעת',
    ];

    protected array $excludeNames = [
        'פתחיה',
    ];

    public function __construct()
    {
    }

    public function handle(): void
    {
        $names = array_unique($this->names);
        $excludeNames = array_unique($this->excludeNames);

        Person::query()
            ->where(function (Builder $query) use ($names) {
                $isFirst = true;
                foreach ($names as $name) {
                    $query->{$isFirst ? 'where' : 'orWhere'}('first_name', 'LIKE', "%$name%");
                    $isFirst = false;
                }
            })
            ->where(function ($query) use ($excludeNames) {
                $isFirst = true;
                foreach ($excludeNames as $name) {
                    $query->{$isFirst ? 'where' : 'orWhere'}('first_name', 'NOT LIKE', "%$name%");
                    $isFirst = false;
                }
            })
            ->update(['gender' => 'G']);

        Person::query()
            ->whereGender('G')
            ->whereNotNull('data_raw->father_in_law_external_code')
            ->select(['id', 'data_raw'])
            ->chunk(1000, function (Collection $people) {
                $people = $people->transform(function (Person $person) {
                    $person->data_raw = array_merge($person->data_raw, [
                        'father_name' => $person->data_raw['father_in_law_name'],
                        'father_in_law_name' => $person->data_raw['father_name'],
                        'father_in_law_external_code' => $person->data_raw['father_external_code'],
                        'father_external_code' => $person->data_raw['father_in_law_external_code'],
                    ]);

                    return $person;
                });

                Person::query()->upsert(
                    $people->map(fn (Person $person) => [
                        'id' => $person->id,
                        'data_raw' => Json::encode($person->data_raw),
                    ])->toArray(),
                    ['id'],
                    ['data_raw']);
            });
    }
}
