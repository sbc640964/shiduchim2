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

    /**
     * @param string $comments
     * @param float|int|null $amount
     * @return mixed
     */

    public function refund(string $comments, float|int|null $amount = null): mixed
    {
        $amount = $amount ?? $this->amount;

        $result = Nedarim::reefundTransaction($this->transaction_id, $amount);

        if($result['Result'] === 'OK') {
            $this->update([
                'status' => 'refunded',
                'status_message' => ($this->status_message ?? '') . ' | ' . $comments,
            ]);

            $this->subscriber->subPayment();
        }

        return $result;
    }

    public function cancel(string $comments = 'ביטול')
    {
        $actionData = [
            'Action' => 'DeletedAllowedTransaction',
            'MosadId' => config('app.nedarim.mosad'),
            'ApiPassword' => config('app.nedarim.password'),
            'TransactionId' => $this->transaction_id,
        ];

        $result = Http::asForm()->post('https://matara.pro/nedarimplus/Reports/Manage3.aspx', $actionData);

        if($result->status() !== 200) {
            $numberError = rand(1000, 9999);
            \Log::error("Failed to cancel transaction ($numberError)", [
                'action' => 'cancel transaction',
                'request_session' => request(),
                'transaction_id' => $this->transaction_id,
                'request' => $actionData,
                'response' => $result->body(),
            ]);

            return [
                'Result' => 'Error',
                'Message' => "Failed to cancel transaction (billing service error $numberError)",
            ];
        }

        $result = $result->json();

        if($result['Result'] === 'OK') {
            $this->update([
                'status' => 'cancelled',
                'status_message' => ($this->status_message ?? '') . ' | ' . $comments,
            ]);

            $this->subscriber->subPayment();
        }

        return $result;
    }
}
