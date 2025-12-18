<?php

namespace App\Listeners;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Kirschbaum\Commentions\Events\UserIsSubscribedToCommentableEvent;

class SendSubscribedUserNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
    }

    public function handle(UserIsSubscribedToCommentableEvent $event): void
    {
        $event->user->notify(
            Notification::make()
                ->title('עדכון חדש בתגובות על '. $event->comment->commentable->full_name)
                ->body($event->comment->body)
                ->actions([
                    Action::make('get_to_page')
                        ->button()
                        ->label('צפה בתגובה')
                        ->url($event->comment->commentable->getCommentUrl())
                ])
                ->toBroadcast(),
        );
    }
}
