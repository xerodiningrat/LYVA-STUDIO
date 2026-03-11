<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RobloxScriptController extends Controller
{
    /**
     * @var array<string, array{label: string, filename: string, description: string}>
     */
    private array $scripts = [
        'devproduct' => [
            'label' => 'Dev Product Reporter',
            'filename' => 'DevProductSalesReporter.server.lua',
            'description' => 'Server script untuk kirim pembelian developer product ke Laravel.',
        ],
        'gamepass-server' => [
            'label' => 'Game Pass Reporter Server',
            'filename' => 'GamePassPurchaseReporter.server.lua',
            'description' => 'Server script untuk verifikasi ownership dan kirim event game pass.',
        ],
        'gamepass-client' => [
            'label' => 'Game Pass Reporter Client',
            'filename' => 'GamePassPurchase.client.lua',
            'description' => 'LocalScript untuk menangkap prompt game pass selesai dibeli.',
        ],
        'catalog' => [
            'label' => 'Product Catalog',
            'filename' => 'ProductCatalog.lua',
            'description' => 'Mapping product ID, game pass ID, nama, dan harga.',
        ],
        'remote' => [
            'label' => 'Remote Event Guide',
            'filename' => 'GamePassPurchaseSignal.remote.lua',
            'description' => 'Petunjuk nama RemoteEvent yang harus dibuat di ReplicatedStorage.',
        ],
        'readme' => [
            'label' => 'Setup Guide',
            'filename' => 'README.md',
            'description' => 'Panduan penempatan script di Roblox Studio.',
        ],
    ];

    public function index()
    {
        return view('roblox.scripts', [
            'scripts' => collect($this->scripts)->map(fn ($script, $slug) => [
                'slug' => $slug,
                ...$script,
            ])->values(),
        ]);
    }

    public function show(string $slug)
    {
        abort_unless(isset($this->scripts[$slug]), 404);

        $script = $this->scripts[$slug];

        return view('roblox.script-show', [
            'script' => [
                'slug' => $slug,
                ...$script,
            ],
            'content' => $this->renderTemplate($script['filename']),
        ]);
    }

    public function download(string $slug): StreamedResponse
    {
        abort_unless(isset($this->scripts[$slug]), 404);

        $script = $this->scripts[$slug];
        $content = $this->renderTemplate($script['filename']);

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $script['filename']);
    }

    private function renderTemplate(string $filename): string
    {
        $path = base_path('roblox/'.$filename);
        abort_unless(file_exists($path), 404);

        $content = file_get_contents($path);
        abort_if($content === false, 500, 'Failed to read Roblox script.');

        $endpoint = rtrim((string) config('app.url'), '/').'/api/roblox/sales-events';
        $ingestToken = (string) config('services.roblox.ingest_token');

        return str_replace(
            [
                'https://domainkamu.com/api/roblox/sales-events',
                'ISI_DENGAN_ROBLOX_INGEST_TOKEN',
            ],
            [
                $endpoint,
                $ingestToken !== '' ? $ingestToken : 'ISI_DENGAN_ROBLOX_INGEST_TOKEN',
            ],
            $content,
        );
    }
}
