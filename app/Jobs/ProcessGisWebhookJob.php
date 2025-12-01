<?php

namespace App\Jobs;

use App\Events\CallActivityEvent;
use App\Http\Controllers\WebhookGisController;
use App\Models\Call;
use App\Models\CallDiary;
use App\Models\Person;
use App\Models\Phone;
use App\Models\User;
use App\Models\WebhookEntry;
use App\Services\PhoneCallGis\ActiveCall;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Str;

class ProcessGisWebhookJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private WebhookEntry|null $webhook = null;

    /**
     * Create a new job instance.
     *
     * @param array $data The webhook data to process
     * @param int $webhookId The ID of the webhook entry to update upon completion
     * @throws \Exception
     */
    public function __construct(
        protected array $data,
        protected int   $webhookId
    )
    {
    }

    public function uniqueId(): string
    {
        return 'call:'.$this->data['linkedid'];
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

        $isOutgoing = $this->data['is_outgoing'] !== 'incoming';

        $phone = $this->resolvePhone($isOutgoing ? $this->data['target_phone'] : $this->data['from_phone']);

        $extension = Str::before($this->data['extension'] ?? '', '-');

        if (blank($extension)) {
            $extension = null;
        }

        $user = $this->resolveUser($isOutgoing ? $this->data['from_phone'] : $this->data['target_phone'], $extension);

        CallDiary::create([
            'event' => $this->data['action'],
            'call_id' => $this->data['original_call_id'],
            'direction' => $isOutgoing ? 'out' : 'in',
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

//        $lockKey = 'lock:call:' . $this->data['linkedid'];
//        $lock = Cache::lock($lockKey, 5);

        try {
            $this->processCall($action, $extension, $phoneNumber, $phone, $user, $isOutgoing);
//            $lock->block(
//                0,
//                fn() => $this->processCall($action, $extension, $phoneNumber, $phone, $user, $isOutgoing, $lock)
//            );
        } catch (\Exception $e) {
            $this->webhook->setError([
                'message' => 'Error processing webhook (Lock error): ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);

//            if ($lock->get()) {
//                $lock->release();
//            }
        }
    }

    private function resolveCall($data, $extension): ?Call
    {
        $isOutgoing = $data['is_outgoing'] !== 'incoming';

        $phoneNumber = ActiveCall::normalizedPhoneNumber($isOutgoing
            ? $data['target_phone']
            : $data['from_phone']
        );

        $call = Call::query()
            ->whereIn(
                'unique_id',
                array_filter([$data['linkedid'] ?? null, $data['original_call_id'] ?? null])
            )
            ->first();

        if ($call) {
            return $call;
        }

        if ($isOutgoing) {
            $call = Call::query()
                ->where('data_raw->events[0]->ID', $data['original_call_id'])
                ->first();
        }

        if (!$call && !$extension) {
            return null;
        }

        return $call ?? Call::query()
            ->whereNull('finished_at')
            ->where(fn(Builder $query) => $query->where('extension', $extension)
                ->where('phone', $phoneNumber)
                ->where('direction', $isOutgoing ? 'outgoing' : 'incoming')
            )
            ->first();
    }

    private function resolvePhone(?string $phoneNumber): ?Phone
    {
        $phoneNumber = ActiveCall::normalizedPhoneNumber($phoneNumber);

        if (!$phoneNumber) {
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

    private function createCall(array $data, ?string $extension, ?string $phoneNumber, ?Phone $phone, ?User $user, $isOutgoing = false): ?Call
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
            throw new \Exception("Webhook entry $this->webhookId not found");
        }

        $this->webhook = $webhook;
    }

    public function tags()
    {
        return ['GIS', 'webhook', 'call:' . $this->data['linkedid'], 'from:' . ($this->data['from_phone'] ?? 'unknown')];
    }

    private function processCall(string $action, ?string $extension, string $phoneNumber, ?Phone $phone, ?User $user, bool $isOutgoing): void
    {
        try {
            $call = $this->resolveCall($this->data, $extension);

            if (!$call) {
                $call = $this->createCall(
                    $this->data,
                    $extension,
                    $phoneNumber,
                    $phone,
                    $user,
                    $isOutgoing
                );
            }

            $call->extension = $extension ?? $call->extension;
            $call->user_id = $user?->id ?? $call->user_id;
            $call->unique_id = $this->data['linkedid'] ?? $this->data['original_call_id'];

            if (!$call->wasRecentlyCreated) {
                $callEvents = $call->data_raw ?? ['events' => []];

                if (!isset($callEvents['events']) || !is_array($callEvents['events'])) {
                    $callEvents['events'] = [];
                }

                $callEvents['events'][] = $this->data;
                $call->data_raw = $callEvents;
            }

            if ($action === 'answered') {
                $call->started_at = now();
            }
            if ($action === 'missed') {
                $call->finished_at = now();
            }

            if ($action === 'ended') {
                $call->finished_at = now();
                $call->duration = $this->data['duration'];
                $call->audio_url = $this->data['record_url'];
                $this->updateAllDiaries($call);
            }

            CallActivityEvent::dispatch($user, $call);

            $this->webhook->completed();
        } catch (\Exception $e) {
            $this->webhook->setError([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
