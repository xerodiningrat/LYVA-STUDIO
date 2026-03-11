# Roblox Sales Pipeline

Script di folder ini disiapkan supaya event pembelian Roblox bisa dikirim ke Laravel dan muncul di bot Discord.

## Lokasi di Roblox Studio

- `ProductCatalog.lua`
  taruh di `ReplicatedStorage/Modules/ProductCatalog`
- `GamePassPurchaseSignal.remote.lua`
  buat `RemoteEvent` bernama `GamePassPurchaseSignal` di `ReplicatedStorage`
- `DevProductSalesReporter.server.lua`
  taruh di `ServerScriptService`
- `GamePassPurchaseReporter.server.lua`
  taruh di `ServerScriptService`
- `GamePassPurchase.client.lua`
  taruh di `StarterPlayer/StarterPlayerScripts`

## Yang harus diisi

Di file server script:

- `ENDPOINT`
  isi URL Laravel kamu, contoh `https://domainkamu.com/api/roblox/sales-events`
- `INGEST_TOKEN`
  isi value `ROBLOX_INGEST_TOKEN` yang sama seperti di `.env`

Di file `ProductCatalog.lua`:

- isi mapping `DeveloperProducts`
- isi mapping `GamePasses`

## Laravel yang dipakai

Endpoint yang menerima data:

- `POST /api/roblox/sales-events`

Header:

- `X-Roblox-Token: ROBLOX_INGEST_TOKEN`

## Penting

- Aktifkan `HTTP Requests` di Roblox Studio:
  `Home > Game Settings > Security > Allow HTTP Requests`
- Untuk `Developer Product`, pakai `ProcessReceipt` di server
- Untuk `Game Pass`, pakai client prompt finish lalu verifikasi ownership di server
