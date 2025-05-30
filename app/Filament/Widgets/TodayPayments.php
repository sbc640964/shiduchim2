<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StudentResource\Pages\Subscription;
use App\Models\Payment;
use App\Models\Subscriber;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayPayments extends BaseWidget
{

    protected static ?int $sort = -200;
    public static function canView(): bool
    {
        return Subscription::canAccess();
    }

    protected function getStats(): array
    {
        $notPay = Subscriber::whereDate('next_payment_date', "<=", today())
            ->whereIn('status', ['active', 'completed-active'])
            ->where('method', 'credit_card')
            ->sum('amount');

        $pay = Payment::query()
            ->whereDate('created_at', today())
            ->whereStatus('OK')
            ->sum('amount');

        return [
            Stat::make('תשלומים היום', \Number::format($pay,2, locale: 'he'))
                ->color($pay < $notPay ? 'danger' : 'success')
                ->icon('heroicon-o-currency-dollar')
                ->description('תשלומים שלא בוצעו היום: ' . \Number::format($notPay, 2, locale: 'he'))
        ];
    }
}
