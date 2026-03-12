<?php

namespace App\Support;

class WithdrawalBankCatalog
{
    public static function all(): array
    {
        return [
            'bca' => ['label' => 'BCA', 'min_digits' => 10, 'max_digits' => 10],
            'bri' => ['label' => 'BRI', 'min_digits' => 15, 'max_digits' => 15],
            'bni' => ['label' => 'BNI', 'min_digits' => 10, 'max_digits' => 10],
            'mandiri' => ['label' => 'Mandiri', 'min_digits' => 13, 'max_digits' => 13],
            'cimb' => ['label' => 'CIMB Niaga', 'min_digits' => 10, 'max_digits' => 14],
            'permata' => ['label' => 'Permata', 'min_digits' => 10, 'max_digits' => 10],
            'btn' => ['label' => 'BTN', 'min_digits' => 16, 'max_digits' => 16],
            'seabank' => ['label' => 'SeaBank', 'min_digits' => 10, 'max_digits' => 10],
            'bsi' => ['label' => 'BSI', 'min_digits' => 10, 'max_digits' => 10],
        ];
    }

    public static function options(): array
    {
        return collect(self::all())
            ->map(fn (array $bank, string $code) => [
                'code' => $code,
                'label' => $bank['label'],
            ])
            ->values()
            ->all();
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function find(string $code): ?array
    {
        $banks = self::all();

        return $banks[$code] ?? null;
    }

    public static function normalizeAccountNumber(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }
}
