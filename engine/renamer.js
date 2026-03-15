import { obfuscateLua } from '../obfuscator.js';

const BANNER_RE = /^-- Generated using [^\r\n]+\r?\n?/;

export function jalankanRenamer(kode, opsi = {}) {
    const syntax = normalisasiSyntax(opsi.syntax);
    const level = normalisasiLevel(opsi.level);
    const hasil = obfuscateLua(kode, level, syntax);
    const kodeTanpaBanner = hasil.result.replace(BANNER_RE, '');
    const warnings = [];

    warnings.push('Komentar dan petunjuk inline dihapus dari output untuk semua profile.');
    warnings.push('Layer 1 dan Layer 2 dijalankan lewat parser aman: rename local symbol + string byte escape.');
    warnings.push('Nama fungsi top-level dan member table di file yang sama ikut di-rename agresif; pastikan tidak ada file lain yang masih memanggil nama lama.');

    return {
        code: kodeTanpaBanner,
        warnings,
        meta: {
            syntax,
            level,
            before: hasil.stats.before,
            after: hasil.stats.after,
        },
    };
}

function normalisasiSyntax(syntaxMasuk) {
    const syntax = typeof syntaxMasuk === 'string' ? syntaxMasuk.toLowerCase().trim() : 'auto';

    if (syntax === 'lua' || syntax === 'luau') {
        return syntax;
    }

    return 'auto';
}

function normalisasiLevel(levelMasuk) {
    const level = typeof levelMasuk === 'string' ? levelMasuk.toLowerCase().trim() : 'balanced';

    if (level === 'max-safe' || level === 'heavy') {
        return 'heavy';
    }

    if (level === 'light') {
        return 'medium';
    }

    return 'medium';
}
