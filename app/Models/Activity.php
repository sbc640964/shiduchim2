<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function getSubjectLabel(): string
    {
        return $this->subject?->getModelActivityLabel() ?? 'Unknown Subject';
    }

    function getSubjectTypeLabel(): string
    {
        return self::mapSubjectTypeLabel($this->subject_type);
    }

    static function mapSubjectTypeLabel($subjectType): string
    {
        return match ($subjectType) {
            'App\Models\Proposal' => 'הצעה',
            'App\Models\Person' => 'אדם',
            'App\Models\Diary' => 'יומן',
            'App\Models\Subscriber' => 'מנוי',
            'App\Models\User' => 'משתמש',
            default => $subjectType,
        };
    }

    function renderDataToTable(array|string|null $data = null, ?string $key = null): ?string
    {
        if(!$data && method_exists($this->subject, 'renderColumnActivityData')) {
            return $this->subject->renderColumnActivityData($this);
        }

        $isFirstData = $data ? 'ps-2' : '';
        $data = $data ?? $this->data;

        if( is_string($data)){
            return $this->getValue($data, $key);
        }

        if (is_array($data) && count($data) > 0) {
            $render =  collect($data)->map(function ($value, $key) use ($isFirstData) {
                return "<div><strong>{$this->getTranslateKey($key)}:</strong> {$this->renderDataToTable($value, $key)}</div>";
            })->implode('');

            return "<div class='text-xs text-gray-600 $isFirstData'>$render</div>";
        }

        return null;
    }

    public function getTranslateKey(string $key): string
    {
        if(! $key) {
            return '';
        }

        if(method_exists($this->subject, 'getActivityDataTranslateKey')) {
            return $this->subject->getActivityDataTranslateKey($key);
        }

        $keys = [
            'start_date' => 'תאריך התחלה',
            'end_date' => 'תאריך סיום',
            'next_payment_date' => 'תאריך תשלום הבא',
            'closed_proposal_id' => 'הצעה שסגרה',
            'spouse_id' => 'בן/בת זוג',
            'old_status_family' => 'סטטוס משפחתי ישן',
            'old_status' => 'סטטוס ישן',
            'person_id' => 'אדם',
            'status' => 'סטטוס',
            'payments' => 'תשלומים',
            'balance_payments' => 'יתרת תשלומים',
            'old' => 'ישן',
            'new' => 'חדש',
            'updated_at' => 'עודכן ב',
        ];

        return $keys[$key] ?? $key;
    }

    public function getValue(string $value, string $key): string
    {
        return match($key) {
            'start_date', 'end_date', 'next_payment_date' => $value ? Carbon::make($value)->format('Y-m-d') : '',
            'updated_at' => $value ? Carbon::make($value)->format('Y-m-d H:i') : '',
            default => $value,
        };
    }
}
