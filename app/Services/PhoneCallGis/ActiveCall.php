<?php

namespace App\Services\PhoneCallGis;

use App\Models\Person;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Collection;
use Str;

class ActiveCall
{
    protected string $status = 'ring';

    protected array $events = [];

    protected array $container;

    protected string $uuid;

    public function __construct(
        protected string $extension,
        protected string $phone,
        protected bool $isOutgoing,
        protected ?string $callId = null,
        protected ?Person $person = null,
        protected ?Proposal $proposal = null,
        protected ?Collection $allProposals = null,
    ) {
        $this->uuid = Str::uuid();

        if (! $this->person) {
            $this->resolvePerson();
        }
    }

    public function container(array $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function delete(): void
    {
        $this->container = collect($this->container)->filter(function (ActiveCall $call) {
            return $call->uuid !== $this->uuid;
        })->toArray();
    }

    private function resolvePerson(): void
    {
        $this->person = Person::query()
            ->whereHas('phones', function ($query) {
                $query->where('number', $this->phone);
            })
            ->first();

        if (! $this->person) {
            return;
        }

        $this->allProposals = $this->person->proposalContacts;
    }

    public function pushEvent(array $data): bool
    {
        $externalPhone = $data['is_outgoing'] ? $data['target_phone'] : $data['from_phone'];

        if ($this->extension === static::normalizedExtension($data['extension'] ?? null)
            && ($data['original_call_id'] === $this->callId
            || (static::normalizedPhoneNumber($externalPhone) === static::normalizedPhoneNumber($this->phone)
            ))
        ) {

            if ($data['action'] === 'missed' || $data['action'] === 'ended') {
                $this->delete();

                return true;
            }
            $this->events[] = $data;
            $this->status = $data['action'];
        }

        return false;
    }

    public static function normalizedExtension(null|int|string $ext): string
    {
        return Str::before($ext, '-');
    }

    public static function normalizedPhoneNumber($phoneNumber)
    {
        if (! $phoneNumber) {
            return null;
        }

        if (Str::startsWith($phoneNumber, '972')) {
            $phoneNumber = Str::after($phoneNumber, '972');
        }

        if (! Str::startsWith($phoneNumber, '0') && Str::startsWith($phoneNumber, [5, 2, 3, 4, 8, 7])) {
            $phoneNumber = '0'.$phoneNumber;
        }

        return $phoneNumber;
    }

    public static function pushEventToAll($data): void
    {
        $cacheKey = 'current-calls/'.$data['extension'];
        $externalPhone = $data['is_outgoing'] ? $data['target_phone'] : $data['from_phone'];

        $currentCalls = \Cache::get($cacheKey) ?? [];

        $currentCalls = collect($currentCalls)->map(function (ActiveCall $call) use ($externalPhone, $data) {
            if ($call->extension === static::normalizedExtension($data['extension'] ?? null)
                && ($data['original_call_id'] === $call->callId
                || (static::normalizedPhoneNumber($externalPhone) === static::normalizedPhoneNumber($call->phone)
                ))
            ) {
                $call->pushEvent($data);
            }

            return $call;
        })->toArray();

        \Cache::put($cacheKey, $currentCalls);
    }

    public static function make(
        string $extension,
        string $phone,
        bool $isOutgoing,
        ?string $callId = null,
        ?Person $person = null,
        ?Proposal $proposal = null,
        ?Collection $allProposals = null,
    ): static {
        $currentCalls = \Cache::get('current-calls/'.$extension) ?? [];

        $currentCalls[] = $newCall = new static($extension, $phone, $isOutgoing, $callId, $person, $proposal, $allProposals);

        \Cache::put('current-calls/'.$extension, $currentCalls);

        return $newCall;
    }
}
