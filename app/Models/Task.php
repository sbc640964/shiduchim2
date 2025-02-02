<?php

namespace App\Models;

use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Symfony\Component\ErrorHandler\Debug;

class Task extends Model implements Eventable
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

    public function getProposalNames()
    {
        if (! $this->proposal) {
            return null;
        }

        \Log::info($this);
        return $this->proposal->guy->last_name . ' - ' . $this->proposal->girl->last_name;
    }

    public function descriptionToCalendar()
    {
        if (! $this->proposal) {
            return $this->description;
        }

        return \Arr::join([
            $this->getProposalNames(),
            $this->description,
        ], ' | ');
    }

    public function toEvent(): array|Event
    {
        return Event::make()
            ->styles([
                'background-color' => '#fff',
                'color' => '#000',
                'border-color' => '#dddddd',
                'border-right-color' => match ($this->priority) {
                    1 => "#dddddd",
                    2 => "#e78313",
                    3 => "#df1313",
                },
                'border-width' => '1px 3px 1px 1px',
                'padding-inline-start' => '5px',
            ])
            ->title($this->description)
            ->start($this->due_date)
            ->editable()
            ->extendedProp('proposal_names', $this->getProposalNames())
            ->extendedProp('completed_at', $this->completed_at)
            ->extendedProps([
                'x-tooltip.html.max-width.350.theme.light' => $this->descriptionToCalendar(),
            ])
            ->end($this->due_date);
    }

}
