<?php

use App\Models\Person;
use App\Models\Subscriber;
use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Person::query()
            ->whereNotNull('billing_status')
            ->where('billing_status', '!=', 'married')
            ->each(function (Person $person) {
                Subscriber::create([
                    'person_id' => $person->id,
                    'payer_id' => $person->billing_payer_id,
                    'method' => $person->billing_method,
                    'credit_card_id' => $person->billing_credit_card_id,
                    'referrer_id' => $person->billing_referrer_id,
                    'status' => $person->billing_status,
                    'error' => null,
                    'payments' => $person->billing_balance_times,
                    'balance_payments' => $person->billing_balance_times,
                    'start_date' => $person->billing_start_date,
                    'end_date' => $person->billing_start_date?->copy()->addMonths($person->billing_balance_times) ?? null,
                    'next_payment_date' => $person->billing_next_date,
                    'notes' => $person->billing_notes,
                    'is_published' => $person->billing_published,
                    'user_id' => $person->billing_matchmaker,
                    'work_day' => $person->billing_matchmaker_day,
                    'amount' => $person->billing_amount,
                ])
                    ->transactions()
                    ->saveMany($person->legacyPayments);
            });
    }

    public function down(): void
    {

    }
};
