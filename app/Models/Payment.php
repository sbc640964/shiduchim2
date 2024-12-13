<?php

namespace App\Models;

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

    public function refund($amount, $changeTimes, $changeNextTime, $comments)
    {
        $result = Http::asForm()->post('https://matara.pro/nedarimplus/Reports/Manage3.aspx', [
            'Action' => 'RefundTransaction',
            'MosadId' => config('app.nedarim.mosad'),
            'ApiPassword' => config('app.nedarim.password'),
            'TransactionId' => $this->transaction_id,
            'RefundAmount' => $amount,
        ])->json();

        if($result['Result'] === 'OK') {
            $this->update([
                'status' => 'refunded',
                'status_message' => $this->status_message . ' | ' . $comments,
            ]);

            if($changeTimes || $changeNextTime) {
                $this->student->update([
                    'billing_balance_times' => $changeTimes ? $this->student->billing_balance_times - 1 : $this->student->billing_balance_times,
                    'billing_next_date' => $changeNextTime ? $this->student->billing_next_date->subMonth() : $this->student->billing_next_date,
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
