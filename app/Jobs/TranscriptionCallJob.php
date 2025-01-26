<?php

namespace App\Jobs;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class TranscriptionCallJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Call $call)
    {
    }

    public function handle(): void
    {
        $this->call->loadMissing('diaries.proposal.people.father', 'diaries.proposal.people.mother.father', 'phoneModel.model', 'user');
        $this->call->refreshCallText();
    }

//    public function middleware(): array
//    {
//        return [
//            new WithoutOverlapping(),
//        ];
//    }
}
