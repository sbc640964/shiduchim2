<?php

namespace App\Jobs\FirstFillData\Students;

use App\Imports\StudentsImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportCsvStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 160;

    public function handle(): void
    {
        (new StudentsImport())->import('students-initial.csv');
    }
}
