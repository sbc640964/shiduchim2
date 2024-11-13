<?php

namespace App\Jobs\FirstFillData;

use App\Models\Person;
use Bus;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AbstractToBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public static int $offset = 1000;

    public function __construct(
        protected ?array $ids = [],
        protected ?string $pageNumber = null,
    ) {
    }

    public static function baseQuery(bool $onlyIds = false): Builder
    {
        return Person::query();
    }

    public static function getBatch(): PendingBatch
    {
        $idsGroups = static::baseQuery()->pluck('id')->chunk(static::$offset);

        return Bus::batch($idsGroups->map(fn ($ids) => new static(ids: $ids->toArray())));
    }

    public function getPeople()
    {
        return count($this->ids) > 0
            ? static::baseQuery()
                ->whereIn('people.id', $this->ids)
                ->get()
            : static::baseQuery()
                ->skip($this->pageNumber * static::$offset)
                ->take(static::$offset)
                ->get();
    }

    public function handle(): void
    {
    }
}
