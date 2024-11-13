<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
