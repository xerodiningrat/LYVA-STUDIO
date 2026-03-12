<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class DuitkuService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function createTransaction(array $payload): array
    {
        $merchantCode = $this->merchantCode();
        $apiKey = $this->apiKey();
        $amount = (int) Arr::get($payload, 'paymentAmount', 0);
        $merchantOrderId = (string) Arr::get($payload, 'merchantOrderId', '');

        if ($amount <= 0 || $merchantOrderId === '') {
            throw new RuntimeException('Payload Duitku belum lengkap.');
        }

        $body = [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => (string) Arr::get($payload, 'paymentMethod', config('services.duitku.payment_method', 'VC')),
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => (string) Arr::get($payload, 'productDetails', 'VIP Title Purchase'),
            'additionalParam' => (string) Arr::get($payload, 'additionalParam', ''),
            'merchantUserInfo' => (string) Arr::get($payload, 'merchantUserInfo', ''),
            'customerVaName' => (string) Arr::get($payload, 'customerVaName', 'VIP Title Buyer'),
            'email' => (string) Arr::get($payload, 'email', ''),
            'phoneNumber' => (string) Arr::get($payload, 'phoneNumber', ''),
            'itemDetails' => Arr::get($payload, 'itemDetails', []),
            'callbackUrl' => (string) Arr::get($payload, 'callbackUrl', ''),
            'returnUrl' => (string) Arr::get($payload, 'returnUrl', ''),
            'expiryPeriod' => (int) Arr::get($payload, 'expiryPeriod', 60),
        ];

        $body['signature'] = md5($merchantCode.$merchantOrderId.$amount.$apiKey);

        $response = $this->request($this->transactionUrl(), $body);
        $json = $response->json();

        if (! is_array($json) || ! isset($json['paymentUrl'])) {
            throw new RuntimeException('Duitku tidak mengembalikan payment URL yang valid.');
        }

        return $json;
    }

    public function checkTransaction(string $merchantOrderId): array
    {
        $merchantCode = $this->merchantCode();
        $apiKey = $this->apiKey();

        $response = $this->request($this->transactionStatusUrl(), [
            'merchantCode' => $merchantCode,
            'merchantOrderId' => $merchantOrderId,
            'signature' => md5($merchantCode.$merchantOrderId.$apiKey),
        ]);

        $json = $response->json();

        if (! is_array($json) || ! isset($json['statusCode'])) {
            throw new RuntimeException('Duitku tidak mengembalikan status transaksi yang valid.');
        }

        return $json;
    }

    public function verifyCallbackSignature(string $merchantCode, string $amount, string $merchantOrderId, string $signature): bool
    {
        return hash_equals(
            md5($merchantCode.$amount.$merchantOrderId.$this->apiKey()),
            $signature,
        );
    }

    public function buildSyntheticEmail(string $identifier): string
    {
        $domain = trim((string) config('services.duitku.customer_email_domain', 'payments.lyva.local'));
        $local = Str::of($identifier)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        return ($local !== '' ? $local : 'discord.user').'@'.$domain;
    }

    private function request(string $url, array $payload): Response
    {
        $response = $this->http
            ->timeout(15)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        if (! $response->successful()) {
            $message = trim((string) $response->body());
            throw new RuntimeException(
                sprintf('Request Duitku gagal [%s]%s', $response->status(), $message !== '' ? ' '.$message : ''),
            );
        }

        return $response;
    }

    private function transactionUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry'
            : 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry';
    }

    private function transactionStatusUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus'
            : 'https://passport.duitku.com/webapi/api/merchant/transactionStatus';
    }

    private function merchantCode(): string
    {
        $merchantCode = trim((string) config('services.duitku.merchant_code'));

        if ($merchantCode === '') {
            throw new RuntimeException('DUITKU_MERCHANT_CODE belum diisi.');
        }

        return $merchantCode;
    }

    private function apiKey(): string
    {
        $apiKey = trim((string) config('services.duitku.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('DUITKU_API_KEY belum diisi.');
        }

        return $apiKey;
    }

    private function isSandbox(): bool
    {
        return (bool) config('services.duitku.sandbox', true);
    }
}
