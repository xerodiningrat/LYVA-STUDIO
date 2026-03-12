<?php

namespace App\Http\Controllers;

use App\Models\VipTitlePayment;
use App\Services\Payments\DuitkuService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DuitkuPaymentController extends Controller
{
    public function callback(Request $request, DuitkuService $duitku): Response
    {
        $validated = $request->validate([
            'merchantCode' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'string', 'max:255'],
            'merchantOrderId' => ['required', 'string', 'max:255'],
            'signature' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'resultCode' => ['nullable', 'string', 'max:32'],
        ]);

        if (
            ! empty($validated['merchantCode'])
            && ! empty($validated['amount'])
            && ! empty($validated['signature'])
            && ! $duitku->verifyCallbackSignature(
                $validated['merchantCode'],
                $validated['amount'],
                $validated['merchantOrderId'],
                $validated['signature'],
            )
        ) {
            abort(401, 'Invalid Duitku signature.');
        }

        $payment = VipTitlePayment::query()
            ->with('claim')
            ->where('merchant_order_id', $validated['merchantOrderId'])
            ->firstOrFail();

        $this->syncPaymentStatus($payment, $validated, $duitku);

        return response('OK');
    }

    public function return(Request $request, DuitkuService $duitku): Response
    {
        $merchantOrderId = (string) $request->query('merchantOrderId', '');

        $payment = $merchantOrderId !== ''
            ? VipTitlePayment::query()->with('claim')->where('merchant_order_id', $merchantOrderId)->first()
            : null;

        if ($payment) {
            $this->syncPaymentStatus($payment, $request->all(), $duitku);
        }

        $status = $payment?->status ?? 'unknown';
        $title = match ($status) {
            'paid' => 'Pembayaran berhasil',
            'pending' => 'Pembayaran sedang diproses',
            'expired' => 'Pembayaran kadaluarsa',
            'failed' => 'Pembayaran gagal',
            default => 'Status pembayaran belum ditemukan',
        };

        $description = match ($status) {
            'paid' => 'Pembayaran title sudah masuk. Kalau pemain sedang online di map yang sesuai, title akan diproses otomatis.',
            'pending' => 'Tunggu konfirmasi dari Duitku beberapa saat lagi, lalu bot akan memproses title otomatis.',
            'expired' => 'Invoice pembayaran sudah kadaluarsa. Buat pembelian baru dari panel bot.',
            'failed' => 'Pembayaran tidak berhasil. Coba ulang dari panel bot.',
            default => 'Nomor pesanan tidak ditemukan atau belum tercatat.',
        };

        return response(<<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title}</title>
  <style>
    body{margin:0;font-family:system-ui,sans-serif;background:#08111f;color:#eef4ff;display:grid;place-items:center;min-height:100vh;padding:24px}
    .card{max-width:640px;width:100%;background:#101b30;border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:28px;box-shadow:0 18px 60px rgba(0,0,0,.35)}
    h1{margin:0 0 12px;font-size:28px}
    p{margin:0 0 8px;line-height:1.7;color:#c8d4ea}
    code{color:#8de0ff}
  </style>
</head>
<body>
  <div class="card">
    <h1>{$title}</h1>
    <p>{$description}</p>
    <p>Merchant Order ID: <code>{$merchantOrderId}</code></p>
  </div>
</body>
</html>
HTML, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function syncPaymentStatus(VipTitlePayment $payment, array $payload, DuitkuService $duitku): void
    {
        $statusResponse = $duitku->checkTransaction($payment->merchant_order_id);
        $statusCode = (string) ($statusResponse['statusCode'] ?? '');
        $resultCode = (string) ($payload['resultCode'] ?? '');
        $isPaid = $statusCode === '00' || $resultCode === '00';
        $isExpired = str_contains(strtolower((string) ($statusResponse['statusMessage'] ?? '')), 'expired')
            || str_contains(strtolower((string) $resultCode), 'expired');

        $payment->update([
            'duitku_reference' => $payload['reference'] ?? $payment->duitku_reference,
            'status' => $isPaid ? 'paid' : ($isExpired ? 'expired' : 'pending'),
            'paid_at' => $isPaid ? ($payment->paid_at ?? now()) : $payment->paid_at,
            'callback_payload' => array_filter([
                ...($payment->callback_payload ?? []),
                'callback' => $payload,
                'transaction_status' => $statusResponse,
            ], static fn ($value) => $value !== null),
        ]);

        $claim = $payment->claim;
        if (! $claim) {
            return;
        }

        if ($isPaid) {
            $claim->update([
                'status' => 'pending',
                'meta' => array_filter([
                    ...($claim->meta ?? []),
                    'payment_status' => 'paid',
                    'payment_reference' => $payment->duitku_reference,
                    'payment_amount' => $payment->amount,
                    'payment_paid_at' => optional($payment->paid_at)->toIso8601String(),
                ], static fn ($value) => $value !== null),
            ]);

            return;
        }

        if ($isExpired) {
            $claim->update([
                'status' => 'payment_expired',
                'meta' => array_filter([
                    ...($claim->meta ?? []),
                    'payment_status' => 'expired',
                ], static fn ($value) => $value !== null),
            ]);
        }
    }
}
