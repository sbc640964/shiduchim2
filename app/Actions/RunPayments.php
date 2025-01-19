<?php

namespace App\Actions;

use App\Models\CreditCard;
use App\Models\Payment;
use App\Models\Person;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Http;

class RunPayments
{
    public function __invoke(): void
    {
        Subscriber::query()
            ->whereStatus('active')
            ->whereDate('next_payment_date', '<=', now())
            ->each(function (Subscriber $subscriber) {
                $subscriber->charge();
            });
    }

    protected function chargePerson(Person $person): void
    {
        if($person->billing_method !== 'credit_card') {
            $this->notifyAdmin($person);
            return;
        }

        $result = Http::asForm()->post('https://matara.pro/nedarimplus/Reports/Manage3.aspx', [
            'Action' => 'TashlumBodedNew',
            'MosadNumber' => config('app.nedarim.mosad'),
            'ApiPassword' => config('app.nedarim.password'),
            'Currency' => 1,
            'KevaId' => $person->billingCard->token,
            'Amount' => $person->billing_amount,
            'JoinToKevaId' => 'NoJoin',
            'Tashloumim' => 1,
        ])->json();

        $payment = Payment::create([
            "credit_card_id" => $person->billingCard->id,
            "student_id" => $person->id,
            "status" => $result['Status'],
            "amount" => $person->billing_amount,
            "paid_at" => now(),
            "description" => "Payment for " . now()->format('F Y'),
            "status_message" => $result['Message'] ?? null,
            "payment_method" => "credit_card",
            "last4" => $person->billingCard->last4,
            "transaction_id" => $result['TransactionId'] ?? null,
            "data" => $result,
        ]);

        $this->updatePerson($person, $payment);
    }

    static function createDirectDebit(Person $person, $data): CreditCard|array
    {
        $result = Http::asForm()->post('https://matara.pro/nedarimplus/V6/Files/WebServices/DebitKeva.aspx', [
            'MosadId' => config('app.nedarim.mosad'),
            'ClientName' => $person->full_name,
            'CardNumber' => $data['card'],
            'Tokef' => $data['exp'],
            'Amount' => 1,
            'Tashloumim' => 1,
            'MasofId' => 'Online',
            'CVV' => $data['cvv'],
        ])->json();

        return data_get($result, 'Status') !== 'OK'
            ? $result
            : $person->cards()->create([
                'brand' => 'UNKNOWN',
                'token' => $result['KevaId'],
                'last4' => $result['LastNum'],
                'is_active' => true,
                'data' => $result,
            ]);
    }

    private function updatePerson(Person $person, Payment $payment): void
    {
        $person->update([
            'billing_balance_times' => $person->billing_balance_times - 1,
            'billing_next_date' => $person->billing_next_date->addMonth(),
            'billing_status' => $person->billing_balance_times - 1 === 0 ? 'inactive' : 'active',
        ]);
    }

    private function notifyAdmin(Person $person)
    {
        // Email the admin
    }
}
