<?php

namespace App\Http\Controllers;

use App\Events\CallActivityEvent;
use App\Models\Call;
use App\Models\CallDiary;
use App\Models\Diary;
use App\Models\Person;
use App\Models\Phone;
use App\Models\User;
use App\Services\PhoneCallGis\ActiveCall;
use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Str;

class WebhookGisController extends Controller
{
    public function __invoke(Request $request)
    {
        $validate = \Validator::make($request->all(), [
            'key' => 'required|in:'.config('app.phonecall.in_api_key'),
            'action' => 'required',
            'original_call_id' => 'required',
            'is_outgoing' => 'required|in:incoming,outgoing',
            'from_phone' => 'required',
            'target_phone' => 'required',
            'extension' => 'nullable',
            'vendor' => 'in:gis',
        ]);

        if ($validate->fails()) {
            return response()->json(['error' => $validate->errors()], 400);
        }

        $data = $request->all();

        //Fix after the changed action name by GIS system
        if($data['action'] === 'hangup') {
            $data['action'] = 'ended';
        }

        $isOutgoing = $data['is_outgoing'] !== 'incoming';

        $phone = $this->resolvePhone($isOutgoing ? $data['target_phone'] : $data['from_phone']);

        $extension = Str::before($data['extension'] ?? '', '-');

        if(blank($extension)) {
            $extension = null;
        }

        $user = $this->resolveUser($isOutgoing ? $data['from_phone'] : $data['target_phone'], $extension);

        CallDiary::create([
            'event' => $data['action'],
            'call_id' => $data['original_call_id'],
            'direction' => $isOutgoing ? 'in' : 'out',
            'from' => $data['from_phone'],
            'to' => $data['target_phone'],
            'user_id' => $user?->id,
            'person_id' => $phone?->model instanceof Person ? $phone?->model_id : null,
            'phone_id' => $phone?->id,
            'extension' => $extension,
            'data' => $data,
        ]);

        $action = $data['action'];
        $phoneNumber = ActiveCall::normalizedPhoneNumber($isOutgoing
            ? $data['target_phone']
            : $data['from_phone']
        );

        if ($action === 'ring' && ! $isOutgoing) {

            $lockKey = 'lock:call:' . $data['original_call_id'] . ':' . $data['action'];

            $lock = Cache::lock($lockKey, 10)->block(10); // 5 שניות נעילה

            try {
                $this->createCall($data, $extension, $phoneNumber, $phone, $user);
                return 'Call created';
            } finally {
                if ($lock) {
                    $lock->release();
                }
            }
        }


        $call = $this->resolveCall($data, $extension, $phoneNumber);

        if (! $call
            && (
                ($data['action'] === 'answered' && $isOutgoing)
                || ($data['action'] === 'missed' && ! $isOutgoing)
            )
        ) {
            $call = $this->createCall(
                $data,
                $extension,
                $phoneNumber,
                $phone,
                $user,
                $isOutgoing
            );
        }



        if (! $call) {
            return 'Error: Call not found';
        }

        $callEvents = $call->data_raw;

        if(! $call->wasRecentlyCreated) {
            $callEvents['events'][] = $data;
        }


        $updateAttributes = [
            'data_raw' => $callEvents,
            'unique_id' => $data['original_call_id'],
            'extension' => $call->extension ?? $extension,
            'user_id' => !$call->extension ? ($user?->id ?? null) : $call->user_id ?? null,
        ];

        if ($action === 'answered') {
            $updateAttributes['started_at'] = now();
        }

        if ($action === 'missed') {
            $updateAttributes['finished_at'] = now();
        }

        if ($action === 'ended') {
            $updateAttributes['finished_at'] = now();
            $updateAttributes['audio_url'] = $data['record_url'];
            $updateAttributes['duration'] = $data['duration'];
        }

        $call->update($updateAttributes);

        if ($action === 'ended') {
            static::updateAllDiaries($call);
        }

        CallActivityEvent::dispatch($user, $call);

        return 'OK';
    }

    private function resolveCall($data, $extension, null|Phone|string $phone = null): ?Call
    {
        $isOutgoing = $data['is_outgoing'] !== 'incoming';

        $phoneNumber = ActiveCall::normalizedPhoneNumber($isOutgoing
            ? $data['target_phone']
            : $data['from_phone']
        );

        $call = Call::whereUniqueId($data['original_call_id'])->first();

        if ($call) {
            return $call;
        }

        if($isOutgoing) {
            $call = Call::query()
                ->where('data_raw->events[0]->ID', $data['original_call_id'])
                ->first();
        }

        return $call ?? Call::query()
            ->whereNull('finished_at')
            ->where(fn (Builder $query) => $query->where('extension', $extension)
                ->where('phone', $phoneNumber)
                ->where('direction', $isOutgoing ? 'outgoing' : 'incoming')
            )
            ->first();
    }

    private function resolvePhone(?string $phoneNumber): ?Phone
    {
        $phoneNumber = ActiveCall::normalizedPhoneNumber($phoneNumber);

        if (! $phoneNumber) {
            return null;
        }

        return Phone::query()
            ->where('number', $phoneNumber)
            ->first();
    }

    private function resolveUser(?string $phoneNumber, ?string $extension = null)
    {
        if ($extension) {
            if ($user = User::query()
                ->where('ext', $extension)
                ->first()
            ) {
                return $user;
            }
        }

        if ($phoneNumber = ActiveCall::normalizedPhoneNumber($phoneNumber)) {
            if ($phone = Phone::query()
                ->where('number', $phoneNumber)
                ->whereModelType(User::class)
                ->first()
            ) {
                return $phone->model;
            }
        }

        return null;
    }

    public static function updateAllDiaries(Call $call)
    {
        $diaries = Diary::where('data->call_id', $call->id)
            ->where('type', 'call')
            ->get();

        $duration = 0;

        if($call->duration > 0 && $diaries->isNotEmpty()){
            $duration = (int) ($call->duration / $diaries->count());
        }

        foreach ($diaries as $diary) {
            $diary->update([
                'data' => array_merge($diary->data, [
                    'file' => $call->audio_url,
                    'duration' => $duration,
                ]),
            ]);
        }
    }

    private function createCall(array $data, ?string $extension, mixed $phoneNumber, ?Phone $phone, mixed $user, $isOutgoing = false): ?Call
    {
        $call = Call::create([
            'extension' => $extension,
            'unique_id' => $data['original_call_id'],
            'phone' => $phoneNumber,
            'phone_id' => $phone?->id ?? null,
            'is_pending' => true,
            'direction' => $isOutgoing ? 'outgoing' : 'incoming',
            'user_id' => $user?->id ?? null,
            'data_raw' => [
                'events' => [
                    $data,
                ],
            ],
        ]);

        $call && CallActivityEvent::dispatch($user, $call);

        return $call;
    }
}
