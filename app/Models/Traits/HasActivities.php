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

    static public function getDefaultsActivityDescription()
    {
        if (isset(static::$defaultActivityDescription)) {
            return static::$defaultActivityDescription;
        }

        return [];
    }

    static public function getDefaultActivityDescription(string $type)
    {
        return static::getDefaultsActivityDescription()[$type] ?? null;
    }

    public function getModelActivityLabel(): string
    {
        if (method_exists($this, 'getModelLabel')) {
            return $this->getModelLabel();
        }
        return "You need add public getModelLabel() method to your model to use this method.";
    }
}
