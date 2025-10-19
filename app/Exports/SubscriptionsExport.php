<?php

namespace App\Exports;

use App\Models\Subscriber;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubscriptionsExport implements FromCollection, WithHeadings, ShouldQueue
{
    use Exportable, SerializesModels;

    public User $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        $users = User::query()
            ->role('שדכן')->get();

        return Subscriber::query()
            ->with("student", "matchmaker", 'activities', 'creditCard', 'payer')
            ->with(["transactions" => function ($query) {
                $query->where('status', 'OK');
            }])
            ->get()
            ->transform(function (Subscriber $subscriber) use ($users) {
                $person = $subscriber->student;
                $matchmaker = $subscriber->matchmaker;
                $payments = $subscriber->transactions;

                $replaceMatchmaker = $subscriber->activities->where('type', 'replace_matchmaker')->last();

                return [
                    'id' => $subscriber->id,
                    'external_code_students' => $person->external_code_students,
                    'name' => $person->full_name,
                    'birth_date' => $person->born_at?->format("d/m/Y") ?? '',
                    'brith_date_he' => $person->born_at?->hebcal()?->hebrewDate(withQuotes: true) ?? '',
                    'age' => $person->age,
                    'school' => $person->school->first()?->name ?? '',
                    'created_at' => $subscriber->created_at->format("d/m/Y"),
                    'status' => $subscriber->statusLabel(),

                    'matchmaker_created_at' => optional($matchmaker)->created_at,
                    'matchmaker_name' => optional($matchmaker)->name,
                    'is_renewed' => $subscriber->activities->where('type', 'update')
                            ->filter(function ($activity) {
                                return data_get($activity->data, 'new.end_date')
                                    && Carbon::make($activity->data['new']['end_date'])->gt($activity->data['old']['end_date']);
                            })->isNotEmpty(),
                    'matchmaker_is_changed' => $replaceMatchmaker ? 'כן' : 'לא',
                    'matchmaker_old_name' => $replaceMatchmaker ? $users->find($replaceMatchmaker->data['old'])->first()?->name : '',
                    'start_at' => $subscriber->start_date?->format("d/m/Y") ?? '',
                    'end_at' => $subscriber->end_date?->format("d/m/Y") ?? '',
                    'end_reason' => $subscriber->activities->where('type', 'cancel')->first()?->description ?? '',
                    'working_days' => $subscriber->work_day_he,

                    'payment_created_at' => optional($payments)->pluck('created_at')->map(fn ($date) => $date->format('d/m/Y'))->implode(','),
                    'payment_amount' => $subscriber->amount ?? 0,
                    'payment_total' => $payments->sum('amount') ?? 0,
                    'payment_balance' => ($subscriber->balance_payments * $subscriber->amount) ?? 0,
                    'payer' => $subscriber->payer->full_name ?? '',
                    'credit_card' => $subscriber->creditCard->last4 ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return array(
            'id' => 'מזהה',
            'external_code_students' => 'מזהה אישי',
            'name' => 'שם התלמיד',
            'birth_date' => 'תאריך לידה',
            'brith_date_he' => 'תאריך לידה עברי',
            'age' => 'גיל',
            'school' => 'מוסד',
            'created_at' => 'תאריך רישום (מועד קליטה ראשוני)',
            'status' => 'סטטוס',

            'matchmaker_created_at' => 'תאריך חיבור לשדכן',
            'matchmaker_name' => 'שם השדכן',
            'is_renewed' => 'האם התקופה התארכה',
            'matchmaker_is_changed' => 'האם הוחלף שדכן באמצע',
            'matchmaker_old_name' => 'שם השדכן הקודם (אם רלוונטי)',
            'start_at' => 'תאריך תחילת עבודה',
            'end_at' => 'תאריך סיום עבודה',
            'end_reason' => 'סיבת ביטול',
            'working_days' => 'יום עבודה לשדכן',

            'payment_created_at' => 'תאריך חיוב',
            'payment_amount' => 'סכום',
            'payment_total' => 'סך הכול שולם',
            'payment_balance' => 'יתרה לתשלום',
            'payer' => 'שם המשלם',
            'credit_card' => 'מספר כרטיס אחרון',
        );
    }
}
