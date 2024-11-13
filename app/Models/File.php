<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    protected $fillable = [
        'name',
        'path',
        'description',
        'model_id',
        'model_type',
        'user_id',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /************* Accessors *************/

    public function getFileTypeAttribute(): string
    {
        $fileExtension = pathinfo($this->path, PATHINFO_EXTENSION);

        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
            return 'תמונה';
        }

        if (in_array($fileExtension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv'])) {
            return 'סרטון';
        }

        if (in_array($fileExtension, ['mp3', 'wav', 'ogg', 'wma', 'aac'])) {
            return 'שיר';
        }

        if (in_array($fileExtension, ['pdf'])) {
            return 'מסמך';
        }

        return 'קובץ אחר';
    }
}
