<?php

namespace App\Models;

use App\Events\MessageCreatedEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discussion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'parent_id',
        'user_id',
        'content',
        'is_popup',
        'image_hero',
    ];

    protected $touches = ['parent'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Discussion::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Discussion::class, 'parent_id');
    }

    public function lastReadAt(): HasOne
    {
        return $this->children()
            ->readAt()
            ->latest()
            ->havingNull('read_at')
            ->one();

    }

    public function lastChildren(): HasOne
    {
        return $this->hasOne(Discussion::class, 'parent_id')->ofMany('created_at', 'max');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usersAssigned(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discussion_user');
    }

    public function usersAsRead(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discussion_read_user')
            ->withPivot('read_at');
    }

    public function otherUsersAsRead(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discussion_read_user')
            ->withPivot('read_at')
            ->where('id', '!=', auth()->id());
    }

    public function scopeReadAt(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if(!$user) {
            return $query;
        }

        return $query->addSelect(['read_at' => \DB::query()
            ->select('read_at')
            ->from('discussion_read_user')
            ->whereColumn('discussion_read_user.discussion_id', 'discussions.id')
            ->where('discussion_read_user.user_id', $user->id)
        ]);
    }

    public function scopeIsUnreadAnyChild(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if(!$user) {
            return $query;
        }

        return $query->addSelect(['is_unread' =>
            \DB::query()->selectRaw('COUNT(*)')
                ->from('discussion_read_user')
                ->whereColumn('discussion_id', 'discussions.id')
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->whereExists(function ($query) {
                    $query->select('id')
                        ->from('discussions')
                        ->whereColumn('parent_id', 'discussions.id');
                })
        ]);
    }

    protected function casts(): array
    {
        return [
            'is_popup' => 'boolean',
        ];
    }

    public function markAsRead(?User $user = null): void
    {
        $user = $user ?? auth()->user();

        if ($this->usersAsRead()->where('user_id', $user->id)->exists()) {
            return;
        }

        $this->usersAsRead()->attach($user->id, ['read_at' => now()]);

        broadcast(
            new MessageCreatedEvent($this, 'read-user')
        );
    }
}
