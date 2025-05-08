<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Storage;

class NotifyUserOfCompletedExportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $filePath;
    public string $endDatetimeToDownload;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->endDatetimeToDownload = now()->addHour()->format('Y-m-d H:i:s');
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('');
    }

    public function toDatabase(User $notifiable): array
    {
        return $this->baseNotification()
            ->getDatabaseMessage();
    }

    public function baseNotification(): FilamentNotification
    {

        $date = Carbon::make($this->endDatetimeToDownload);

        $url = Storage::disk('s3')->temporaryUrl(
            'exports/'.$this->filePath,
            now()->addHour(),
        );

        return FilamentNotification::make()
            ->title('קובץ הייצוא - מנויים - מוכן')
            ->success()
            ->body('קובץ הייצוא מוכן להורדה, הקובץ זמין לגישה בשעה הקרובה (עד '. $date->format('H:i') .').')
            ->actions([
                Action::make('download')
                    ->label('הורד')
                    ->hidden(now()->gt($date->format('H:i')))
                    ->url($url, true)
            ]);
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return $this->baseNotification()
            ->getBroadcastMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
