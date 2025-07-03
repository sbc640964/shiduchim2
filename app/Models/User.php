<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasActivities;
use Awcodes\FilamentGravatar\Gravatar;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens,
        HasFactory,
        HasPanelShield,
        HasRoles,
        HasActivities,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ext',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static array $defaultActivityDescription = [
        'login' => 'התחבר למערכת',
        'logout' => 'התנתק מהמערכת',
        'update' => 'עדכן את הפרופיל שלו',
    ];

    public function getModelLabel(): string
    {
        return $this->name;
    }

    public function proposals()
    {
        return $this->belongsToMany(Proposal::class, 'user_proposal')
            ->withPivot('timeout');
    }

    public function timeDiaries(): HasMany
    {
        return $this->hasMany(TimeDiary::class);
    }

    public function activeTime() : ?TimeDiary
    {
        return $this->timeDiaries()->whereNull('end_at')->first();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if($this->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        return $this->can('access_panel');
    }

    ///permissions

    public function canAccessAllTimeSheets(): bool
    {
        return $this->can('access_all_time_sheets');
    }

    public function canAccessAllCalls(): bool
    {
        return $this->can('access_all_calls');
    }

    public function getYouOrNameAttribute(): string
    {
        return $this->id === auth()->id() ? 'אתה' : $this->name;
    }

    public function getAvaterUriAttribute()
    {
        return Gravatar::get(
            email: $this->email,
            size: 200,
            default: 'robohash',
        );
    }

    public function chatRooms()
    {
        return $this->belongsToMany(Discussion::class, 'discussion_user');
    }

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }

    public function activeSubscriber()
    {
        return $this->subscribers()->where('end_date', '>', now())->first();
    }
}
