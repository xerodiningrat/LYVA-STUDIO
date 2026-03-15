<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ObfuscatorController extends Controller
{
    public function index()
    {
        $html = @file_get_contents(public_path('index.html'));

        abort_if($html === false, 500, 'Gagal memuat halaman enkripsi.');

        $injected = str_replace(
            '</head>',
            "    <script>window.LYVA_API_BASE = '/enkripsi/api';</script>\n</head>",
            $html
        );

        return response($injected, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function health(Request $request)
    {
        return $this->forward($request, 'GET', '/health');
    }

    public function obfuscate(Request $request)
    {
        return $this->forward($request, 'POST', '/obfuscate');
    }

    public function generateKey(Request $request)
    {
        return $this->forward($request, 'POST', '/generate-key');
    }

    public function checkKey(Request $request)
    {
        return $this->forward($request, 'POST', '/check-key');
    }

    public function revokeKey(Request $request)
    {
        return $this->forward($request, 'POST', '/revoke-key');
    }

    public function dashboard(Request $request)
    {
        return $this->forward($request, 'GET', '/dashboard');
    }

    public function dashboardKeys(Request $request)
    {
        return $this->forward($request, 'GET', '/dashboard/keys');
    }

    private function forward(Request $request, string $method, string $path)
    {
        try {
            $response = Http::withHeaders($this->forwardHeaders($request))
                ->timeout(120)
                ->send($method, $this->targetUrl($path), $this->requestOptions($request, $method));

            return response($response->body(), $response->status())
                ->withHeaders($this->responseHeaders($response->headers()));
        } catch (ConnectionException $exception) {
            return response()->json([
                'error' => 'Service enkripsi tidak bisa dihubungi dari Laravel.',
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    private function targetUrl(string $path): string
    {
        return rtrim((string) config('services.obfuscator.url'), '/') . $path;
    }

    private function requestOptions(Request $request, string $method): array
    {
        if ($method === 'GET') {
            return [
                'query' => $request->query(),
            ];
        }

        return [
            'json' => $request->all(),
        ];
    }

    private function forwardHeaders(Request $request): array
    {
        $headers = [
            'Accept' => $request->header('Accept', 'application/json'),
        ];

        if ($request->headers->has('Authorization')) {
            $headers['Authorization'] = (string) $request->header('Authorization');
        }

        return $headers;
    }

    private function responseHeaders(array $headers): array
    {
        $allowed = [];

        foreach (['content-type', 'www-authenticate'] as $key) {
            if (! empty($headers[$key][0])) {
                $allowed[$key] = $headers[$key][0];
            }
        }

        return $allowed;
    }
}
