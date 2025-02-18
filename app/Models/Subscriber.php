<?php

namespace App\Models;

use App\Models\Traits\HasActivities;
use App\Services\Nedarim;
use Carbon\Carbon;
use Carbon\Traits\Localization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;

class Subscriber extends Model
{
    use HasActivities;

    protected $fillable = [
        'person_id',
        'payer_id',
        'method',
        'credit_card_id',
        'referrer_id',
        'status',
        'error',
        'payments',
        'balance_payments',
        'start_date',
        'end_date',
        'next_payment_date',
        'notes',
        'is_published',
        'user_id',
        'work_day',
        'amount',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static array $defaultActivityDescription = [
        'run' => 'הפעלת מנוי',
        'hold' => 'השהיית מנוי',
        'completed' => 'השלמת מנוי',
        'cancel' => 'ביטול מנוי',
        'update' => 'עדכון מנוי',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscriber $subscriber) {
//            if($subscriber->start_date && !$subscriber->next_payment_date)
//                $subscriber->next_payment_date = $subscriber->start_date->copy();
        });

        static::saving(function (Subscriber $subscriber) {
            if($subscriber->transactions()->doesntExist()){
//                if($subscriber->start_date && $subscriber->isDirty('start_date'))
//                    $subscriber->next_payment_date = $subscriber->start_date->copy();

                if($subscriber->payments && $subscriber->isDirty('payments'))
                    $subscriber->balance_payments = $subscriber->payments;
            }
        });

        static::saved(function (Subscriber $subscriber) {
            if($subscriber->next_payment_date
                && $subscriber->isActive()
                && $subscriber->next_payment_date->isToday()
                && $subscriber->transactions()->doesntExist()
                && $subscriber->balance_payments > 0
            ) {
                $subscriber->charge();
            }
        });
    }

    public function charge(?bool $force = false, ?bool $joinTheDirectDebit = true): void
    {
        if($this->method !== 'credit_card') {
            // $this->notifyAdmin($subscriber);
            return;
        }

        if (! $this->balance_payments && ! $force) {
            $this->update([
                'status' => 'completed',
                'error' => 'התשלומים נגמרו',
            ]);
            return;
        }

        $result = Nedarim::chargeDirectDebit($this->creditCard->token, $this->amount);

        $payment = $this->transactions()->create([
            "credit_card_id" => $this->credit_card_id,
            "student_id" => $this->student->getKey(),
            "status" => $result['Status'],
            "amount" => $this->amount,
            "paid_at" => now(),
            "description" => $joinTheDirectDebit ? ("תשלום עבור " . Carbon::make($this->next_payment_date)->locale('he')->translatedFormat('F-Y') . ($force ? '*' : '')) : "זוהי פעולה יזומה שלא צורפה להוראת הקבע",
            "status_message" => $result['Message'] ?? null,
            "payment_method" => "credit_card",
            "last4" => $this->creditCard->last4,
            "transaction_id" => $result['TransactionId'] ?? null,
            "data" => $result,
            "is_join" => $joinTheDirectDebit,
        ]);

        $payment && $joinTheDirectDebit && $payment->status === 'OK' && $this->update([
            'balance_payments' => $this->balance_payments - 1,
            'next_payment_date' => $this->next_payment_date ? $this->next_payment_date->copy()->addMonth() : null,
        ]);

    }

    public function getToOptionsSelect()
    {
        $strings = [
            $this->start_date?->format('d/m/Y') ?? 'לא הוגדר תאריך התחלה',
            '-',
            $this->start_date ? $this->end_date->format('d/m/Y') : '',
            $this->isCurrent() ? 'נוכחי' : '',
        ];

        return implode(' ', array_filter($strings));
    }

    public function isCurrent(): bool
    {
        return
            $this->start_date && $this->end_date &&
            now()->isBetween($this->start_date, $this->end_date);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'next_payment_date' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function matchmaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function lastTransaction()
    {
        return $this->transactions()->one()->latestOfMany();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getWorkDayHeAttribute()
    {
        return match ($this->work_day) {
            1 => 'ראשון',
            2 => 'שני',
            3 => 'שלישי',
            4 => 'רביעי',
            5 => 'חמישי',
            6 => 'שישי',
            7 => 'שבת',
            default => null,
        };
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function allowActivation(): bool
    {
        return in_array($this->status, [ 'pending', 'hold', 'inactive' ])
            && $this->payments > 0
            && $this->user_id
            && $this->start_date
            && $this->next_payment_date;
    }

    public function subPayment(?int $num = 1): void
    {
        $this->update([
            'balance_payments' => $this->balance_payments + $num,
            'next_payment_date' => $this->next_payment_date->subMonths($num),
        ]);
    }
}
