import { bangunTemplateOutput } from '../vm/template.js';
import { DAFTAR_OPCODE_AUDIT } from '../vm/opcodes.js';
import { kompilasiRingkasUntukAudit } from '../vm/compiler.js';

export function jalankanVmBuilderAman(kode, opsi = {}) {
    const level = normalisasiLevel(opsi.level);
    const hasil = bangunTemplateOutput(level === 'max-safe' ? ringkasWhitespace(kode) : kode);
    const ringkasan = kompilasiRingkasUntukAudit(kode);

    return {
        code: hasil,
        warnings: [
            'Layer 6 custom VM diganti transparent wrapper yang dapat diaudit; daftar opcode hanya dipakai sebagai metadata audit.',
        ],
        meta: {
            enabled: false,
            opcodeCount: DAFTAR_OPCODE_AUDIT.length,
            audit: ringkasan,
        },
    };
}

function ringkasWhitespace(kode) {
    const baris = kode
        .split(/\r?\n/)
        .map((barisSaatIni) => barisSaatIni.replace(/[ \t]+$/g, ''))
        .filter((barisSaatIni, index, semuaBaris) => {
            if (barisSaatIni.trim() !== '') {
                return true;
            }

            return semuaBaris[index - 1]?.trim() !== '';
        });

    return baris.join('\n').trim();
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
