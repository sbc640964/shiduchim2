<?php

namespace App\Models;

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'owner_id',
        'visibility',
        'category',
        'content',
        'call_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'documentable_id' => 'integer',
        'owner_id' => 'integer',
        'call_id' => 'integer',
        'visibility' => NoteVisibility::class,
        'category' => NoteCategory::class,
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Users the note owner shared this private note with (view-only + can comment).
     *
     * @return BelongsToMany<User>
     */
    public function sharedWithUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'note_user')
            ->withTimestamps();
    }

    /**
     * @return HasMany<NoteComment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(NoteComment::class);
    }

    /**
     * @return MorphMany<File>
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->visibility === NoteVisibility::Public) {
            return true;
        }

        if ($this->isOwnedBy($user)) {
            return true;
        }

        return $this->sharedWithUsers()
            ->whereKey($user->id)
            ->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query
                ->where('visibility', NoteVisibility::Public)
                ->orWhere('owner_id', $user->id)
                ->orWhereHas('sharedWithUsers', fn (Builder $query) => $query->whereKey($user->id));
        });
    }
}
