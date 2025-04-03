<?php

namespace App\Actions;

use App\Models\Subscriber;

class RunPayments
{
    public function __invoke(): void
    {
        // complete all active subscribers that end date is yesterday
        Subscriber::query()
            ->whereStatus('active')
            ->whereDate('end_date', '<', now())
            ->each(function (Subscriber $subscriber) {
                $subscriber->completeWork();
            });

        Subscriber::query()
            ->whereStatus('active')
            ->whereDate('next_payment_date', '<=', now())
            ->each(function (Subscriber $subscriber) {
                $subscriber->charge();
            });
    }
}
