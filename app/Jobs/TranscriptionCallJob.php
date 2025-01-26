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

class TranscriptionCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Call $call)
    {
    }

    public function handle(): void
    {
        $this->call->load(['diaries' => fn($q) => $q->whereHas("proposal", fn($qq) => $qq->whereHas("people"))]);
        $this->call->diaries->load('proposal.people.father', 'proposal.people.mother.father');
        $this->call->loadMissing('phoneModel.model', 'user');
        $this->call->refreshCallText();
    }

//    public function middleware(): array
//    {
//        return [
//            new WithoutOverlapping(),
//        ];
//    }
}
