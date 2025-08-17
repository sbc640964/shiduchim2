<?php

namespace App\Models;

use Str;
use App\Services\PhoneCallGis\CallPhone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Phone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'model',
        'model_type',
        'model_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /************** METHODS **************/

    public function call(?Proposal $proposal = null, ?Person $person = null): array
    {
        $currenCall = (new CallPhone())->call($this->number);

        Call::create([
            'extension' => auth()->user()->ext,
            'unique_id' => $currenCall['ID'],
            'phone' => $this->number,
            'user_id' => auth()->id(),
            'phone_id' => $this->id,
            'is_pending' => true,
            'direction' => 'outgoing',
            'data_raw' => [
                'events' => [
                    $currenCall,
                ],
            ],
        ]);

        return $currenCall;
    }

    public function getNumberAttribute($value): string
    {
        if (!Str::startsWith($value, '0')) {
            return '00' . $value;
        }

        return $value;
    }
}
