<?php

namespace App\Actions;

use App\Models\Subscriber;

class RunPayments
{
    public function __invoke(): void
    {
        Subscriber::query()
            ->whereStatus('active')
            ->whereDate('next_payment_date', '<=', now())
            ->each(function (Subscriber $subscriber) {
                $subscriber->charge();
            });
    }
}
