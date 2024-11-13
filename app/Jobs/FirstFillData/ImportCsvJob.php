<?php

namespace App\Jobs\FirstFillData;

use App\Imports\PeopleImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SkipsFailures;

    public int $timeout = 160;

    public function __construct()
    {
    }

    public function handle(): void
    {
        (new PeopleImport())->import('upload-all-data.csv');
    }
}
