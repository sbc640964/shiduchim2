<?php

namespace App\Models\Traits;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasActivities
{

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function recordActivity(string $type, ?array $data = [], ?string $description = null): void
    {
        $this->activities()->create([
            'user_id' => auth()->id() ?? null,
            'type' => $type,
            'description' => $description ?? $this->getDefaultActivityDescription($type),
            'data' => $data,
        ]);
    }

    static public function getDefaultActivityDescription(string $type)
    {
        if(isset(static::$defaultActivityDescription)) {
            return static::$defaultActivityDescription[$type] ?? null;
        }

        return null;
    }
}
