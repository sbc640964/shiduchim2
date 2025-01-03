<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Task extends Model
{
    use HasJsonRelationships;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'due_date',
        'priority',
        'proposal_id',
        'data',
        'completed_at',
        'diary_completed_id',
        'person_id',
    ];

    protected $casts = [
        'data' => 'array',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function diaryCompleted(): BelongsTo
    {
        return $this->belongsTo(Diary::class, 'diary_completed_id');
    }

    public function completed(?Diary $diary = null): bool
    {
        $this->completed_at = now();
        $this->diaryCompleted()->associate($diary);
        return $this->save();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'data->contact_to');
    }
}
