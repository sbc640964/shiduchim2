<?php

namespace App\Models;

use App\Services\Nedarim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class Payment extends Model
{
    protected $fillable = [
        "credit_card_id",
        "student_id",
        "status",
        "amount",
        "paid_at",
        "description",
        "status_message",
        "payment_method",
        "last4",
        "transaction_id",
        'subscriber_id',
        "data",
    ];

    protected $casts = [
        "data" => "array",
        "paid_at" => "datetime",
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function refund($amount, $changeTimes, $changeNextTime, $comments)
    {
        $result = Nedarim::refundTransaction($this->transaction_id, $amount);

        if($result['Result'] === 'OK') {
            $this->update([
                'status' => 'refunded',
                'status_message' => $this->status_message . ' | ' . $comments,
            ]);

            if($changeTimes || $changeNextTime) {
                $this->subscriber->update([
                    'balance_payments' => $changeTimes ? $this->subscriber->balance_payments - 1 : $this->subscriber->balance_payments,
                    'next_payment_date' => $changeNextTime ? $this->subscriber->next_payment_date->subMonth() : $this->subscriber->next_payment_date,
                ]);
            }
        }

        return $result;
    }

    public function cancel(string $comments = 'ביטול')
    {
        $result = Http::asForm()->post('https://matara.pro/nedarimplus/Reports/Manage3.aspx', [
            'Action' => 'DeletedAllowedTransaction',
            'MosadId' => config('app.nedarim.mosad'),
            'ApiPassword' => config('app.nedarim.password'),
            'TransactionId' => $this->transaction_id,
        ])->json();

        if($result['Result'] === 'OK') {
            $this->update([
                'status' => 'cancelled',
                'status_message' => $this->status_message . ' | ' . $comments,
            ]);
        }

        return $result;
    }
}
