<?php

namespace App\Services\PhoneCallGis;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

class CallPhone
{
    public static string $baseUrl = 'https://api.phonecall.co/pbx/gisapi.php';

    public function authParams(): array
    {
        return [
            'key' => config('app.phonecall.api_key'),
            'tenant' => config('app.phonecall.api_tenant'),
            'format' => 'json',
        ];
    }

    public function getDefaultParams(?array $params = []): array
    {
        return array_merge_recursive($params, $this->authParams());
    }

    public function getExtensions(): Collection
    {
        return collect($this->get([
            'reqtype' => 'INFO',
            'info' => 'extensions',
        ])->json())->map(function ($ext) {
            return collect($ext)->filter(fn ($value, $key) => ! is_numeric($key));
        });
    }

    public function getExtensionState(int $ext): Collection
    {
        return $this->get([
            'reqtype' => 'INFO',
            'ext' => $ext,
            'info' => 'extstate'
        ])->collect();
    }

    public function call($number)
    {
        return $this->get([
            'reqtype' => 'DIAL',
            'source' => auth()->user()->ext,
            'dest' => $number,
            'account' => 'SOURCE',
            'autoanswer' => 'yes',
            'nofollow' => 'yes',
            'recording' => 'yes',
        ])->json();
    }

    public function getCallId($firstId): array
    {
        return $this->get([
            'reqtype' => 'INFO',
            'info' => 'call',
            'id' => $firstId,
        ])->json();
    }

    public function getRecording($id)
    {
        return $this->get([
            'reqtype' => 'INFO',
            'info' => 'recording',
            'id' => $id,
        ])->body();
    }

    public function get($params): PromiseInterface|Response
    {
        return \Http::get(self::$baseUrl, $this->getDefaultParams($params));
    }
}
