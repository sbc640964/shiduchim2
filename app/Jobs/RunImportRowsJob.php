<?php

namespace App\Jobs;

use App\Models\ImportRow;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunImportRowsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $rowsIds
    )
    {
    }

    public function handle(): void
    {
        $rows = ImportRow::whereIn('id', $this->rowsIds)->get();
        $rows->each->run(queue: true);
    }
}
