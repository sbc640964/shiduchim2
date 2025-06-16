<?php

namespace App\Jobs;

use App\Models\ImportRow;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunImportRowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ImportRow $row,
        protected bool $pendingOnly = true
    )
    {
    }

    public function handle(): void
    {
        $this->row->run($this->pendingOnly);
    }
}
