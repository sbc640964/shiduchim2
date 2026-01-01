<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteComment extends Model
{
    protected $fillable = [
        'note_id',
        'author_id',
        'body',
    ];

    protected $casts = [
        'id' => 'integer',
        'note_id' => 'integer',
        'author_id' => 'integer',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
