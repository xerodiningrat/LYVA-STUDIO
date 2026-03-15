import { jalankanRenamer } from './renamer.js';
import { jalankanEncoder } from './encoder.js';
import { jalankanFlowFlatteningAman } from './flowflat.js';
import { jalankanXorAman } from './xor.js';
import { jalankanVmBuilderAman } from './vmbuilder.js';
import { jalankanAntiTamperAman } from './antitamper.js';

export const BATAS_INPUT_BYTE = 1024 * 1024;

export class PipelineError extends Error {
    constructor(message, status = 400) {
        super(message);
        this.name = 'PipelineError';
        this.status = status;
    }
}

export function jalankanPipelineProteksi(kodeMasuk, opsiMasuk = {}) {
    const kode = validasiKodeMasuk(kodeMasuk);
    const opsi = {
        syntax: normalisasiSyntax(opsiMasuk.syntax),
        level: normalisasiLevel(opsiMasuk.level),
    };

    let kodeAktif = kode;
    const warnings = [];
    const layers = [];

    for (const layer of daftarLayer()) {
        try {
            const hasil = layer.run(kodeAktif, opsi);
            kodeAktif = typeof hasil?.code === 'string' ? hasil.code : kodeAktif;
            warnings.push(...normalisasiArray(hasil?.warnings));
            layers.push({
                name: layer.name,
                status: 'ok',
                meta: hasil?.meta ?? {},
            });
        } catch (error) {
            warnings.push(`[${layer.name}] ${error.message}`);
            layers.push({
                name: layer.name,
                status: 'error',
                meta: {},
            });
        }
    }

    return {
        result: kodeAktif,
        warnings,
        layers,
        profile: opsi.level,
        syntax: opsi.syntax,
        stats: {
            before: Buffer.byteLength(kode, 'utf8'),
            after: Buffer.byteLength(kodeAktif, 'utf8'),
        },
    };
}

function daftarLayer() {
    return [
        { name: 'renamer', run: jalankanRenamer },
        { name: 'encoder', run: jalankanEncoder },
        { name: 'flowflat', run: jalankanFlowFlatteningAman },
        { name: 'xor', run: jalankanXorAman },
        { name: 'antitamper', run: jalankanAntiTamperAman },
        { name: 'vmbuilder', run: jalankanVmBuilderAman },
    ];
}

function validasiKodeMasuk(kodeMasuk) {
    if (typeof kodeMasuk !== 'string') {
        throw new PipelineError('Field "code" wajib berupa string.');
    }

    const ukuran = Buffer.byteLength(kodeMasuk, 'utf8');

    if (ukuran === 0) {
        throw new PipelineError('Kode Lua atau Luau tidak boleh kosong.');
    }

    if (ukuran > BATAS_INPUT_BYTE) {
        throw new PipelineError('Ukuran kode melebihi batas 1MB.');
    }

    return kodeMasuk;
}

function normalisasiLevel(levelMasuk) {
    const level = typeof levelMasuk === 'string' ? levelMasuk.toLowerCase().trim() : 'balanced';

    if (level === 'light' || level === 'balanced' || level === 'max-safe') {
        return level;
    }

    if (level === 'medium') return 'balanced';
    if (level === 'heavy') return 'max-safe';
    return 'balanced';
}

function normalisasiSyntax(syntaxMasuk) {
    const syntax = typeof syntaxMasuk === 'string' ? syntaxMasuk.toLowerCase().trim() : 'auto';

    if (syntax === 'lua' || syntax === 'luau') {
        return syntax;
    }

    return 'auto';
}

function normalisasiArray(nilai) {
    return Array.isArray(nilai) ? nilai.filter((item) => typeof item === 'string' && item.trim() !== '') : [];
}
