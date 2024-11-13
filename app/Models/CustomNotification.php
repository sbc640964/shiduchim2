<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class CustomNotification extends Model
{
    use HasJsonRelationships;

    protected $fillable = [
        'title',
        'body',
        'type',
        'status',
        'recipients',
        'data',
        'sent_at',
        'scheduled_at',
        'user_id',
    ];

    protected $casts = [
        'data' => 'array',
        'recipients' => 'array',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at');
    }


    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }

    public function recipientsUsers(): BelongsToJson
    {
        return $this->belongsToJson(User::class, 'recipients', 'id');
    }

    public function sent()
    {
        $users = $this->recipientsUsers()->get();

        $users->each->notify(Notification::make()
            ->title($this->title)
            ->body($this->body)
            ->toDatabase()
        );

        $users->each->notify(Notification::make()
            ->title($this->title)
            ->body($this->body)
            ->toBroadcast()
        );
    }
}
