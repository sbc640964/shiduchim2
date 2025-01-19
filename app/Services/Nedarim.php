<?php

namespace App\Services;

use App\Models\CreditCard;
use App\Models\Person;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use phpDocumentor\Reflection\Types\Boolean;
use function Laravel\Prompts\select;

class Nedarim
{
    static public function post(array $data = [], $url = null)
    {
        $mosadId = config('app.nedarim.mosad');
        $password = config('app.nedarim.password');

        if (!$mosadId || !$password) {
            throw new InvalidArgumentException("Invalid configuration for Nedarim API.");
        }

        return Http::asForm()->post($url ?? 'https://matara.pro/nedarimplus/Reports/Manage3.aspx', array_merge([
            'MosadId' => $mosadId,
            'ApiPassword' => $password,
        ], $data))->json();
    }

    static public function reefundTransaction(int $id, int|float $amount)
    {
        return self::post([
            'TransactionId' => $id,
            'RefundAmount' => $amount,
            'Action' => 'RefundTransaction',
        ]);
    }

    static function chargeDirectDebit(int $directDebitId, int|float $amount, bool $joinToDirectDebit = false, $currency = 'ILS')
    {
        $currencyMapping = [
            'ILS' => 1,
            'USD' => 2,
            'EUR' => 3,
        ];

        if (!array_key_exists($currency, $currencyMapping)) {
            throw new InvalidArgumentException('Invalid currency code.');
        }

        $currency = $currencyMapping[$currency];

        return static::post([
            'Action' => 'TashlumBodedNew',
            'Currency' => $currency,
            'KevaId' => $directDebitId,
            'Amount' => $amount,
            'JoinToKevaId' => $joinToDirectDebit ? 'Join' : 'NoJoin',
            'Tashloumim' => 1,
        ]);
    }

    /**
     * יצירת חיוב ישיר
     *
     * @param  Person  $person  אובייקט אדם להעברת פרטים אישיים
     * @param  array<string, string|int>  $data  מערך של פרטי כרטיס אשראי [card, exp, cvv]
     * @return CreditCard|array<string, mixed> כרטיס אשראי או מערך המייצג פרטים
     */
    static function createDirectDebit(Person $person, array $data): CreditCard|array
    {
        if (empty($data['card']) || empty($data['exp']) || empty($data['cvv'])) {
            throw new InvalidArgumentException('Missing required card details.');
        }

        return static::post([
            'ClientName' => $person->full_name,
            'CardNumber' => $data['card'],
            'Tokef' => $data['exp'],
            'Amount' => 1,
            'Tashloumim' => 1,
            'MasofId' => 'Online',
            'CVV' => $data['cvv'],
        ], 'https://matara.pro/nedarimplus/V6/Files/WebServices/DebitKeva.aspx');
    }
}
