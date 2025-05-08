<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\NotifyUserOfCompletedExportNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationsAfterExportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected User $user, protected string $filePath)
    {

    }

    public function handle(): void
    {
        $this->user->notify(
            new NotifyUserOfCompletedExportNotification($this->filePath)
        );
    }
}
