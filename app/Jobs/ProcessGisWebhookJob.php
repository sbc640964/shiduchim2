<?php

namespace App\Jobs;

use App\Events\CallActivityEvent;
use App\Http\Controllers\WebhookGisController;
use App\Models\Call;
use App\Models\CallDiary;
use App\Models\Diary;
use App\Models\Person;
use App\Models\Phone;
use App\Models\User;
use App\Models\WebhookEntry;
use App\Services\PhoneCallGis\ActiveCall;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Str;

class ProcessGisWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private WebhookEntry|null $webhook = null;

    /**
     * Create a new job instance.
     *
     * @param array $data The webhook data to process
     * @param int $webhookId The ID of the webhook entry to update upon completion
     * @throws Exception
     */
    public function __construct(
        protected array $data,
        protected int $webhookId
    ) {
    }

    /**
     * Execute the job.
     *
     * Processes the GIS webhook data:
     * - Creates call diary entries
     * - Creates or updates call records
     * - Updates diaries when calls are ended
     * - Dispatches call activity events
     */
    public function handle(): void
    {
        $this->setWebhook();

        try {
            $isOutgoing = $this->data['is_outgoing'] !== 'incoming';

            $phone = $this->resolvePhone($isOutgoing ? $this->data['target_phone'] : $this->data['from_phone']);

            $extension = Str::before($this->data['extension'] ?? '', '-');

            if(blank($extension)) {
                $extension = null;
            }

            $user = $this->resolveUser($isOutgoing ? $this->data['from_phone'] : $this->data['target_phone'], $extension);

            CallDiary::create([
                'event' => $this->data['action'],
                'call_id' => $this->data['original_call_id'],
                'direction' => $isOutgoing ? 'in' : 'out',
                'from' => $this->data['from_phone'],
                'to' => $this->data['target_phone'],
                'user_id' => $user?->id,
                'person_id' => $phone?->model instanceof Person ? $phone?->model_id : null,
                'phone_id' => $phone?->id,
                'extension' => $extension,
                'data' => $this->data,
            ]);

            $action = $this->data['action'];
            $phoneNumber = ActiveCall::normalizedPhoneNumber($isOutgoing
                ? $this->data['target_phone']
                : $this->data['from_phone']
            );

            if ($action === 'ring') {
                $lockKey = 'lock:call:' . $this->data['original_call_id'] . ':' . $this->data['action'];

                $lock = Cache::lock($lockKey, 3)->block(3);

                $call = $this->resolveCall($this->data, $extension, $phoneNumber);

                if($call && $extension) {
                    $call->update([
                        'extension' => $extension,
                        'user_id' => $user?->id ?? null,
                    ]);
                } else {
                    $this->createCall($this->data, $extension, $phoneNumber, $phone, $user, $isOutgoing);
                    $this->webhook->completed();
                    return;
                }

                if ($lock instanceof \Illuminate\Contracts\Cache\Lock) {
                    $lock->release();
                }
            }

            $call = $this->resolveCall($this->data, $extension, $phoneNumber);

            if (! $call
                && (
                    ($this->data['action'] === 'answered' && $isOutgoing)
                    || ($this->data['action'] === 'missed' && ! $isOutgoing)
                )
            ) {
                $call = $this->createCall(
                    $this->data,
                    $extension,
                    $phoneNumber,
                    $phone,
                    $user,
                    $isOutgoing
                );
            }

            if (! $call) {
                $this->webhook->setError([
                    'error' => 'Call not found',
                ]);

                return;
            }

            $callEvents = $call->data_raw;

            if(! $call->wasRecentlyCreated) {
                $callEvents['events'][] = $this->data;
            }

            $updateAttributes = [
                'data_raw' => $callEvents,
                'unique_id' => $this->data['original_call_id'],
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
                $updateAttributes['audio_url'] = $this->data['record_url'];
                $updateAttributes['duration'] = $this->data['duration'];
            }

            $call->update($updateAttributes);

            if ($action === 'ended') {
                $this->updateAllDiaries($call);
            }

            CallActivityEvent::dispatch($user, $call);

            $this->webhook->completed();
        } catch (Exception $e) {
            $this->webhook->setError([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);
        }
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

    private function updateAllDiaries(Call $call): void
    {
        WebhookGisController::updateAllDiaries($call);
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

    private function setWebhook(): void
    {
        $webhook = WebhookEntry::find($this->webhookId);

        if (!$webhook) {
            throw new Exception("Webhook entry $this->webhookId not found");
        }

        $this->webhook = $webhook;
    }
}
