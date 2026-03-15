import 'dotenv/config';
import express from 'express';
import fs from 'node:fs/promises';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

import { BATAS_INPUT_BYTE, PipelineError, jalankanPipelineProteksi } from './engine/pipeline.js';

const app = express();
const PORT = Number.parseInt(process.env.PORT ?? '3000', 10);
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const publicDir = path.join(__dirname, 'public');
const keysFilePath = path.join(__dirname, 'keys.json');
const rateLimitStore = new Map();
const BATAS_REQUEST_PER_MENIT = 20;
const WINDOW_MS = 60 * 1000;
const ADMIN_PASSWORD = process.env.LYVA_LICENSE_ADMIN_PASSWORD ?? 'lyva-admin';
const LUAOBFUSCATOR_API_KEY = String(process.env.LUAOBFUSCATOR_API_KEY ?? '').trim();
const LUAOBFUSCATOR_BASE_URL = 'https://api.luaobfuscator.com/v1';
const LYVA_LICENSE_PUBLIC_URL = String(process.env.LYVA_LICENSE_PUBLIC_URL ?? '').trim();

app.disable('x-powered-by');

app.use((req, res, next) => {
    res.setHeader('Referrer-Policy', 'no-referrer');
    res.setHeader('X-Content-Type-Options', 'nosniff');
    next();
});

app.use(batasiRequestPerIp);

app.use(express.json({
    limit: `${Math.floor(BATAS_INPUT_BYTE / 1024)}kb`,
    strict: true,
}));

app.use(express.static(publicDir));

app.get('/', (_req, res) => {
    res.sendFile(path.join(publicDir, 'index.html'));
});

app.get('/health', (_req, res) => {
    res.json({
        ok: true,
        name: 'LYVA SHIELD PRO',
        maxInputBytes: BATAS_INPUT_BYTE,
        rateLimitPerMinute: BATAS_REQUEST_PER_MENIT,
        features: ['obfuscate', 'license-generator', 'license-dashboard'],
        providers: {
            local: true,
            luaobfuscator: LUAOBFUSCATOR_API_KEY !== '',
        },
    });
});

app.post('/obfuscate', async (req, res, next) => {
    try {
        const { code, level, syntax, license, provider } = req.body ?? {};
        const providerAktif = normalisasiProvider(provider);
        const hasil = providerAktif === 'luaobfuscator'
            ? await obfuscateDenganLuaObfuscator(code, { level, syntax })
            : jalankanPipelineProteksi(code, { level, syntax });
        validasiHasilObfuscateAman(code, hasil.result, providerAktif);
        const responsePayload = {
            result: hasil.result,
            stats: hasil.stats,
            warnings: hasil.warnings,
            layers: hasil.layers,
            profile: hasil.profile,
            syntax: hasil.syntax,
            provider: providerAktif,
            license: null,
        };

        if (license?.enabled === true) {
            const generatedLicense = await siapkanLicenseOtomatis(req, license);
            responsePayload.result = renderLicenseBootstrap(generatedLicense) + hasil.result;
            responsePayload.license = {
                enabled: true,
                autoInjected: true,
                key: generatedLicense.key,
                escapedKey: generatedLicense.escapedKey,
                createdAt: generatedLicense.createdAt,
                configSnippet: `LICENSE_KEY = "${generatedLicense.escapedKey}",`,
                mapsName: generatedLicense.mapsName,
                gameId: generatedLicense.gameId,
                placeId: generatedLicense.placeId,
                owner: generatedLicense.owner,
            };
            responsePayload.stats = {
                ...hasil.stats,
                after: Buffer.byteLength(responsePayload.result, 'utf8'),
            };
            responsePayload.warnings = [
                ...hasil.warnings,
                `License otomatis ditanam untuk map ${generatedLicense.mapsName}.`,
            ];
        }

        res.json(responsePayload);
    } catch (error) {
        next(error);
    }
});

app.post('/generate-key', async (req, res, next) => {
    try {
        const payload = validasiGenerateKeyPayload(req.body ?? {});
        const keys = await bacaKeys();
        const createdAt = new Date().toISOString();
        const key = await buatKeyUnik(keys);
        const escapedKey = encodeLuaByteEscape(key);

        keys.push({
            key,
            mapsName: payload.mapsName,
            gameId: payload.gameId,
            placeId: payload.placeId,
            owner: payload.owner,
            active: true,
            createdAt,
        });

        await simpanKeys(keys);

        res.json({
            key,
            escapedKey,
            createdAt,
            configSnippet: `LICENSE_KEY = "${escapedKey}",`,
        });
    } catch (error) {
        next(error);
    }
});

app.post('/check-key', async (req, res, next) => {
    try {
        const payload = validasiCheckKeyPayload(req.body ?? {});
        const keys = await bacaKeys();
        const found = keys.find((item) => item.key === payload.key);

        if (!found) {
            return res.json({ valid: false, reason: 'Key tidak ditemukan.', mapsName: null });
        }

        if (found.active !== true) {
            return res.json({ valid: false, reason: 'Key sudah dinonaktifkan.', mapsName: found.mapsName });
        }

        if (String(found.gameId) !== payload.gameId) {
            return res.json({ valid: false, reason: 'GameId tidak cocok.', mapsName: found.mapsName });
        }

        if (String(found.placeId) !== payload.placeId) {
            return res.json({ valid: false, reason: 'PlaceId tidak cocok.', mapsName: found.mapsName });
        }

        return res.json({
            valid: true,
            reason: 'OK',
            mapsName: found.mapsName,
        });
    } catch (error) {
        next(error);
    }
});

app.post('/revoke-key', async (req, res, next) => {
    try {
        const payload = validasiRevokePayload(req.body ?? {});

        if (payload.adminPassword !== ADMIN_PASSWORD) {
            throw new PipelineError('Admin password salah.', 403);
        }

        const keys = await bacaKeys();
        const found = keys.find((item) => item.key === payload.key);

        if (!found) {
            return res.json({ success: false });
        }

        found.active = false;
        await simpanKeys(keys);

        return res.json({ success: true });
    } catch (error) {
        next(error);
    }
});

app.get('/dashboard', async (req, res, next) => {
    try {
        if (!lolosBasicAuth(req)) {
            res.setHeader('WWW-Authenticate', 'Basic realm="LYVA License Dashboard"');
            return res.status(401).send('Butuh autentikasi.');
        }

        const keys = await bacaKeys();
        return res.send(renderDashboard(keys));
    } catch (error) {
        next(error);
    }
});

app.use((req, res) => {
    res.status(404).json({
        error: `Route ${req.method} ${req.path} tidak ditemukan.`,
    });
});

app.use((error, _req, res, _next) => {
    const kesalahan = normalisasiError(error);

    res.status(kesalahan.status).json({
        error: kesalahan.message,
    });
});

if (prosesUtamaDipanggilLangsung()) {
    app.listen(PORT, () => {
        console.log(`LYVA SHIELD PRO aktif di http://localhost:${PORT}`);
    });
}

export default app;

function prosesUtamaDipanggilLangsung() {
    if (typeof process.env.pm_id !== 'undefined') {
        return true;
    }

    const target = process.argv[1];

    if (!target) {
        return false;
    }

    return path.resolve(target) === __filename;
}

function normalisasiError(error) {
    if (error instanceof PipelineError) {
        return {
            status: error.status ?? 400,
            message: error.message,
        };
    }

    if (error?.type === 'entity.too.large') {
        return {
            status: 413,
            message: 'Payload terlalu besar. Maksimal 1MB.',
        };
    }

    if (error instanceof SyntaxError && 'body' in (error ?? {})) {
        return {
            status: 400,
            message: 'Format JSON tidak valid.',
        };
    }

    console.error('[LYVA SHIELD PRO]', error);

    return {
        status: 500,
        message: 'Terjadi kesalahan internal saat memproses permintaan.',
    };
}

function batasiRequestPerIp(req, res, next) {
    const ip = ambilIp(req);
    const sekarang = Date.now();
    const data = rateLimitStore.get(ip);

    if (!data || sekarang >= data.resetAt) {
        rateLimitStore.set(ip, {
            count: 1,
            resetAt: sekarang + WINDOW_MS,
        });
        return next();
    }

    if (data.count >= BATAS_REQUEST_PER_MENIT) {
        const retryAfter = Math.max(1, Math.ceil((data.resetAt - sekarang) / 1000));
        res.setHeader('Retry-After', retryAfter);
        return res.status(429).json({
            error: 'Terlalu banyak request dari IP ini. Coba lagi sebentar.',
        });
    }

    data.count += 1;
    rateLimitStore.set(ip, data);
    return next();
}

function ambilIp(req) {
    const forwarded = req.headers['x-forwarded-for'];

    if (typeof forwarded === 'string' && forwarded.trim() !== '') {
        return forwarded.split(',')[0].trim();
    }

    return req.ip || req.socket.remoteAddress || 'unknown';
}

function normalisasiProvider(providerMasuk) {
    const provider = typeof providerMasuk === 'string'
        ? providerMasuk.toLowerCase().trim()
        : 'local';

    if (provider === 'luaobfuscator' || provider === 'luaobfuscator.com') {
        return 'luaobfuscator';
    }

    return 'local';
}

function validasiHasilObfuscateAman(kodeAsli, kodeHasil, providerAktif) {
    if (typeof kodeAsli !== 'string' || typeof kodeHasil !== 'string') {
        return;
    }

    const sourceMemakaiMxConfig = /\bMX_Config\b/.test(kodeAsli);
    const hasilMenyisipkanMxConfig = /WaitForChild\(\s*["']MX_Config["']\s*\)/.test(kodeHasil);

    if (!sourceMemakaiMxConfig && hasilMenyisipkanMxConfig) {
        const saran = providerAktif === 'luaobfuscator'
            ? 'Gunakan Local Engine untuk script MX.'
            : 'Coba ulangi dengan Local Engine atau source yang belum pernah di-transform.'

        throw new PipelineError(
            `Output terdeteksi tidak aman: hasil obfuscation menyisipkan require("MX_Config") padahal source tidak memakainya. ${saran}`,
            422,
        );
    }
}

async function bacaKeys() {
    await pastikanKeysFileAda();
    const raw = await fs.readFile(keysFilePath, 'utf8');
    const parsed = JSON.parse(raw);

    if (!Array.isArray(parsed)) {
        throw new PipelineError('Format keys.json tidak valid.', 500);
    }

    return parsed;
}

async function simpanKeys(keys) {
    await fs.writeFile(keysFilePath, `${JSON.stringify(keys, null, 2)}\n`, 'utf8');
}

async function pastikanKeysFileAda() {
    try {
        await fs.access(keysFilePath);
    } catch {
        await fs.writeFile(keysFilePath, '[]\n', 'utf8');
    }
}

async function buatKeyUnik(keys) {
    const sudahAda = new Set(keys.map((item) => item.key));

    while (true) {
        const key = [
            'LYVA',
            buatSegmenKey(),
            buatSegmenKey(),
            buatSegmenKey(),
            buatSegmenKey(),
        ].join('-');

        if (!sudahAda.has(key)) {
            return key;
        }
    }
}

function buatSegmenKey() {
    return crypto.randomBytes(2).toString('hex').toUpperCase();
}

function validasiGenerateKeyPayload(payload) {
    return {
        mapsName: validasiStringField(payload.mapsName, 'mapsName'),
        gameId: validasiStringField(payload.gameId, 'gameId'),
        placeId: validasiStringField(payload.placeId, 'placeId'),
        owner: validasiStringField(payload.owner, 'owner'),
    };
}

function validasiAutoLicensePayload(payload) {
    const parsed = validasiGenerateKeyPayload(payload);
    const serverUrlRaw = typeof payload.serverUrl === 'string'
        ? payload.serverUrl.trim()
        : '';

    return {
        ...parsed,
        serverUrl: serverUrlRaw !== '' ? serverUrlRaw : null,
    };
}

function validasiCheckKeyPayload(payload) {
    return {
        key: validasiStringField(payload.key, 'key'),
        gameId: validasiStringField(payload.gameId, 'gameId'),
        placeId: validasiStringField(payload.placeId, 'placeId'),
    };
}

function validasiRevokePayload(payload) {
    return {
        key: validasiStringField(payload.key, 'key'),
        adminPassword: validasiStringField(payload.adminPassword, 'adminPassword'),
    };
}

function validasiStringField(nilai, nama) {
    const hasil = typeof nilai === 'string' ? nilai.trim() : String(nilai ?? '').trim();

    if (hasil === '') {
        throw new PipelineError(`Field "${nama}" wajib diisi.`);
    }

    return hasil;
}

function lolosBasicAuth(req) {
    const header = req.headers.authorization;

    if (typeof header !== 'string' || !header.startsWith('Basic ')) {
        return false;
    }

    const decoded = Buffer.from(header.slice(6), 'base64').toString('utf8');
    const separatorIndex = decoded.indexOf(':');

    if (separatorIndex === -1) {
        return false;
    }

    const username = decoded.slice(0, separatorIndex);
    const password = decoded.slice(separatorIndex + 1);

    return username === 'admin' && password === ADMIN_PASSWORD;
}

function renderDashboard(keys) {
    const rows = keys.map((item) => `
        <tr>
            <td>${escapeHtml(item.key)}</td>
            <td>${escapeHtml(item.mapsName)}</td>
            <td>${escapeHtml(String(item.gameId))}</td>
            <td>${escapeHtml(String(item.placeId))}</td>
            <td>${escapeHtml(item.owner)}</td>
            <td>${item.active ? 'Active' : 'Revoked'}</td>
            <td>${escapeHtml(item.createdAt)}</td>
        </tr>
    `).join('');

    return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LYVA License Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #0f172a; color: #e5eefc; }
        h1 { margin-bottom: 8px; }
        p { color: #9fb3d1; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #111c31; }
        th, td { padding: 12px; border: 1px solid #25314b; text-align: left; }
        th { background: #18253b; }
        tr:nth-child(even) { background: #132036; }
    </style>
</head>
<body>
    <h1>LYVA License Dashboard</h1>
    <p>Total key: ${keys.length}</p>
    <table>
        <thead>
            <tr>
                <th>Key</th>
                <th>Map</th>
                <th>GameId</th>
                <th>PlaceId</th>
                <th>Owner</th>
                <th>Status</th>
                <th>CreatedAt</th>
            </tr>
        </thead>
        <tbody>${rows}</tbody>
    </table>
</body>
</html>`;
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function encodeLuaByteEscape(text) {
    const buffer = Buffer.from(String(text), 'latin1');
    let result = '';

    for (const byte of buffer.values()) {
        result += `\\${byte.toString().padStart(3, '0')}`;
    }

    return result;
}

async function siapkanLicenseOtomatis(req, payload) {
    const parsed = validasiAutoLicensePayload(payload);
    const keys = await bacaKeys();
    const createdAt = new Date().toISOString();
    const key = await buatKeyUnik(keys);
    const escapedKey = encodeLuaByteEscape(key);
    const serverUrl = tentukanLicenseServerUrl(req, parsed.serverUrl);

    keys.push({
        key,
        mapsName: parsed.mapsName,
        gameId: parsed.gameId,
        placeId: parsed.placeId,
        owner: parsed.owner,
        active: true,
        createdAt,
    });

    await simpanKeys(keys);

    return {
        ...parsed,
        key,
        escapedKey,
        createdAt,
        serverUrl,
    };
}

function tentukanLicenseServerUrl(req, serverUrlManual) {
    const kandidat = [
        serverUrlManual,
        LYVA_LICENSE_PUBLIC_URL,
        ambilUrlDariRequest(req),
    ];

    for (const item of kandidat) {
        const normalized = normalisasiLicenseServerUrl(item);
        if (normalized) {
            return normalized;
        }
    }

    return 'https://lyvaindonesia.my.id/enkripsi/api';
}

function ambilUrlDariRequest(req) {
    if (!req) {
        return null;
    }

    const forwardedProtoRaw = req.headers?.['x-forwarded-proto'];
    const forwardedHostRaw = req.headers?.['x-forwarded-host'];
    const proto = typeof forwardedProtoRaw === 'string' && forwardedProtoRaw.trim() !== ''
        ? forwardedProtoRaw.split(',')[0].trim()
        : req.protocol;
    const host = typeof forwardedHostRaw === 'string' && forwardedHostRaw.trim() !== ''
        ? forwardedHostRaw.split(',')[0].trim()
        : req.get('host');

    if (!host) {
        return null;
    }

    return `${proto || 'https'}://${host}`;
}

function normalisasiLicenseServerUrl(rawUrl) {
    if (typeof rawUrl !== 'string' || rawUrl.trim() === '') {
        return null;
    }

    const cleaned = rawUrl.trim().replace(/\/+$/, '');
    const tanpaApiCheck = cleaned.replace(/\/check-key$/i, '');
    const lower = tanpaApiCheck.toLowerCase();

    if (
        lower.includes('127.0.0.1') ||
        lower.includes('localhost') ||
        lower.includes('0.0.0.0')
    ) {
        return null;
    }

    if (lower.endsWith('/enkripsi/api')) {
        return tanpaApiCheck;
    }

    if (lower.endsWith('/api')) {
        return tanpaApiCheck;
    }

    if (lower.endsWith('/enkripsi')) {
        return `${tanpaApiCheck}/api`;
    }

    return `${tanpaApiCheck}/enkripsi/api`;
}

function renderLicenseBootstrap(license) {
    const encodedHttpService = encodeLuaByteEscape('HttpService');
    const encodedRunService = encodeLuaByteEscape('RunService');
    const encodedWarnPrefix = encodeLuaByteEscape('[LYVA LICENSE] ');
    const encodedCheckPath = encodeLuaByteEscape('/check-key');
    const encodedServerUrl = encodeLuaByteEscape(license.serverUrl);
    const encodedContentType = encodeLuaByteEscape('Content-Type');
    const encodedJson = encodeLuaByteEscape('application/json');
    const encodedLicenseDown = encodeLuaByteEscape('Server license tidak bisa diakses selama 30 detik. Script dihentikan.');
    const encodedInvalid = encodeLuaByteEscape('License tidak valid.');
    const encodedStudioBypass = encodeLuaByteEscape('Studio bypass aktif. License check dilewati.');

    return [
        `local _0xlyva_http=game:GetService("${encodedHttpService}")`,
        `local _0xlyva_run=game:GetService("${encodedRunService}")`,
        `local _0xlyva_cfg={key="${license.escapedKey}",path="${encodedCheckPath}",url="${encodedServerUrl}",grace=30,retry=5}`,
        'local function _0xlyva_fail(_0xmsg)local _0xfull="' + encodedWarnPrefix + '"..tostring(_0xmsg) warn(_0xfull) error(_0xfull,0) end',
        'local function _0xlyva_decode(_0xresponse)local _0xok,_0xbody=pcall(function() return _0xlyva_http:JSONDecode(_0xresponse.Body or "{}") end) if not _0xok or type(_0xbody)~="table" then return nil end return _0xbody end',
        'local function _0xlyva_request() return _0xlyva_http:RequestAsync({Url=_0xlyva_cfg.url.._0xlyva_cfg.path,Method="POST",Headers={["' + encodedContentType + '"]="' + encodedJson + '"},Body=_0xlyva_http:JSONEncode({key=_0xlyva_cfg.key,gameId=tostring(game.GameId),placeId=tostring(game.PlaceId)})}) end',
        'if _0xlyva_run:IsStudio() then warn("' + encodedWarnPrefix + encodedStudioBypass + '") else local _0xlyva_started=os.clock() while true do local _0xok,_0xresponse=pcall(_0xlyva_request) if _0xok and _0xresponse and _0xresponse.Success then local _0xbody=_0xlyva_decode(_0xresponse) if _0xbody and _0xbody.valid==true then break end _0xlyva_fail((_0xbody and _0xbody.reason) or "' + encodedInvalid + '") end if (os.clock()-_0xlyva_started)>=_0xlyva_cfg.grace then _0xlyva_fail("' + encodedLicenseDown + '") end task.wait(_0xlyva_cfg.retry) end end',
        '',
    ].join('\n');
}

async function obfuscateDenganLuaObfuscator(code, opsi = {}) {
    const sumber = validasiSourceObfuscate(code);

    if (LUAOBFUSCATOR_API_KEY === '') {
        throw new PipelineError('Provider luaobfuscator.com belum dikonfigurasi di server.', 503);
    }

    const sessionId = await uploadSessionLuaObfuscator(sumber);
    const payload = bangunPayloadLuaObfuscator(opsi.level);
    const obfuscated = await jalankanObfuscateLuaObfuscator(sessionId, payload);

    return {
        result: obfuscated,
        stats: {
            before: Buffer.byteLength(sumber, 'utf8'),
            after: Buffer.byteLength(obfuscated, 'utf8'),
        },
        warnings: [
            'Kode diproses lewat provider eksternal luaobfuscator.com.',
            'Konfigurasi plugin eksternal saat ini memakai preset aman berbasis profile yang dipilih.',
        ],
        layers: [
            {
                name: 'luaobfuscator',
                status: 'ok',
                meta: {
                    provider: 'luaobfuscator.com',
                    profile: typeof opsi.level === 'string' ? opsi.level : 'balanced',
                },
            },
        ],
        profile: typeof opsi.level === 'string' ? opsi.level : 'balanced',
        syntax: typeof opsi.syntax === 'string' ? opsi.syntax : 'auto',
    };
}

function validasiSourceObfuscate(code) {
    if (typeof code !== 'string') {
        throw new PipelineError('Field "code" wajib berupa string.', 400);
    }

    const ukuran = Buffer.byteLength(code, 'utf8');
    if (ukuran === 0) {
        throw new PipelineError('Kode Lua atau Luau tidak boleh kosong.', 400);
    }

    if (ukuran > BATAS_INPUT_BYTE) {
        throw new PipelineError('Payload terlalu besar. Maksimal 1MB.', 413);
    }

    return code;
}

async function uploadSessionLuaObfuscator(code) {
    const response = await fetch(`${LUAOBFUSCATOR_BASE_URL}/obfuscator/newscript`, {
        method: 'POST',
        headers: {
            apikey: LUAOBFUSCATOR_API_KEY,
            'content-type': 'text',
        },
        body: code,
    });

    const data = await bacaJsonAman(response);
    if (!response.ok || !data?.sessionId) {
        throw new PipelineError(
            data?.message || `Gagal membuat session luaobfuscator.com (${response.status}).`,
            502,
        );
    }

    return String(data.sessionId);
}

async function jalankanObfuscateLuaObfuscator(sessionId, payload) {
    const response = await fetch(`${LUAOBFUSCATOR_BASE_URL}/obfuscator/obfuscate`, {
        method: 'POST',
        headers: {
            apikey: LUAOBFUSCATOR_API_KEY,
            sessionId,
            'content-type': 'application/json',
        },
        body: JSON.stringify(payload),
    });

    const data = await bacaJsonAman(response);
    if (!response.ok || typeof data?.code !== 'string' || data.code === '') {
        throw new PipelineError(
            data?.message || `Gagal obfuscate via luaobfuscator.com (${response.status}).`,
            502,
        );
    }

    return data.code;
}

function bangunPayloadLuaObfuscator(levelMasuk) {
    const level = typeof levelMasuk === 'string' ? levelMasuk.toLowerCase().trim() : 'balanced';
    const payload = {
        MinifiyAll: true,
    };

    if (level === 'max-safe' || level === 'heavy') {
        payload.Virtualize = true;
    }

    return payload;
}

async function bacaJsonAman(response) {
    const text = await response.text();

    try {
        return JSON.parse(text);
    } catch {
        return {
            message: text || null,
        };
    }
}
