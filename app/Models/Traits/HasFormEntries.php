<?php

namespace app\Models\Traits;

use App\Models\FormEntry;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasFormEntries
{
    public function entry(): MorphOne
    {
        return $this->morphOne(FormEntry::class, 'model');
    }

    public function entries(): MorphMany
    {
        return $this->morphMany(FormEntry::class, 'model');
    }
}
