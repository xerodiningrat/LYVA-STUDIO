export function jalankanEncoder(kode, opsi = {}) {
    const level = normalisasiLevel(opsi.level);
    const hasil = obfuscateAngkaPadaKode(kode, level);

    return {
        code: hasil.code,
        warnings: hasil.warnings,
        meta: {
            numbersChanged: hasil.count,
            level,
        },
    };
}

function obfuscateAngkaPadaKode(kode, level) {
    let hasil = '';
    let index = 0;
    let jumlahAngka = 0;
    const warnings = [];

    while (index < kode.length) {
        const char = kode[index];

        if (char === '"' || char === '\'') {
            const akhir = lewatiStringPendek(kode, index, char);
            hasil += kode.slice(index, akhir);
            index = akhir;
            continue;
        }

        if (char === '-' && kode[index + 1] === '-') {
            const infoPanjang = bacaPembukaKurungPanjang(kode, index + 2);

            if (infoPanjang) {
                const akhir = lewatiStringPanjang(kode, infoPanjang.start, infoPanjang.equalsCount);
                hasil += kode.slice(index, akhir);
                index = akhir;
                continue;
            }

            let akhir = index + 2;
            while (akhir < kode.length && kode[akhir] !== '\n') {
                akhir += 1;
            }

            hasil += kode.slice(index, akhir);
            index = akhir;
            continue;
        }

        const infoStringPanjang = bacaPembukaKurungPanjang(kode, index);
        if (infoStringPanjang) {
            const akhir = lewatiStringPanjang(kode, infoStringPanjang.start, infoStringPanjang.equalsCount);
            hasil += kode.slice(index, akhir);
            index = akhir;
            continue;
        }

        if (adalahAwalAngka(kode, index)) {
            const akhir = lewatiAngka(kode, index);
            const token = kode.slice(index, akhir);
            const tokenObfuscated = ubahAngkaJikaAman(token, level);

            if (tokenObfuscated !== token) {
                jumlahAngka += 1;
            }

            hasil += tokenObfuscated;
            index = akhir;
            continue;
        }

        hasil += char;
        index += 1;
    }

    if (level === 'light') {
        warnings.push('Layer 3 memakai obfuscation angka konservatif agar tetap stabil di Roblox Studio.');
    }

    return {
        code: hasil,
        count: jumlahAngka,
        warnings,
    };
}

function ubahAngkaJikaAman(token, level) {
    if (!/^\d+$/.test(token)) {
        return token;
    }

    const angka = Number.parseInt(token, 10);

    if (!Number.isSafeInteger(angka) || angka < 0) {
        return token;
    }

    if (angka === 0) return '(1-1)';
    if (angka === 1) return '1';
    if (angka === 2) return '(1+1)';

    if (angka <= 9) {
        return `(${angka - 1}+1)`;
    }

    if (angka % 2 === 0 && angka >= 20) {
        const separuh = Math.floor(angka / 2);
        return `(${bentukDasar(separuh, level)}*${bentukDasar(2, level)})`;
    }

    const pecahan = pecahKePangkatDua(angka);
    if (pecahan.length > 1) {
        return `(${pecahan.map((item) => bentukDasar(item, level)).join('+')})`;
    }

    return bentukDasar(angka, level);
}

function bentukDasar(angka, level) {
    if (angka === 0) return '(1-1)';
    if (angka === 1) return '1';
    if (angka === 2) return '2';
    if (angka <= 9 || level === 'light') return String(angka);

    if (angka % 2 === 0 && angka >= 20) {
        return `(${Math.floor(angka / 2)}*2)`;
    }

    const pecahan = pecahKePangkatDua(angka);
    if (pecahan.length > 1) {
        return `(${pecahan.join('+')})`;
    }

    return String(angka);
}

function pecahKePangkatDua(angka) {
    const hasil = [];
    let sisa = angka;
    let bit = 1;

    while (bit <= sisa) {
        bit *= 2;
    }

    bit = Math.floor(bit / 2);

    while (bit >= 1) {
        if (sisa >= bit) {
            hasil.push(bit);
            sisa -= bit;
        }

        bit = Math.floor(bit / 2);
    }

    return hasil;
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

function bacaPembukaKurungPanjang(kode, index) {
    if (kode[index] !== '[') {
        return null;
    }

    let cursor = index + 1;
    let equalsCount = 0;

    while (kode[cursor] === '=') {
        equalsCount += 1;
        cursor += 1;
    }

    if (kode[cursor] !== '[') {
        return null;
    }

    return {
        start: index,
        equalsCount,
    };
}

function lewatiStringPendek(kode, index, quote) {
    let cursor = index + 1;

    while (cursor < kode.length) {
        if (kode[cursor] === '\\') {
            cursor += 2;
            continue;
        }

        cursor += 1;

        if (kode[cursor - 1] === quote) {
            return cursor;
        }
    }

    return kode.length;
}

function lewatiStringPanjang(kode, index, equalsCount) {
    const penutup = `]${'='.repeat(equalsCount)}]`;
    let cursor = index + 2 + equalsCount;

    while (cursor < kode.length) {
        if (kode.startsWith(penutup, cursor)) {
            return cursor + penutup.length;
        }

        cursor += 1;
    }

    return kode.length;
}

function adalahAwalAngka(kode, index) {
    const char = kode[index];

    if (!/[0-9]/.test(char)) {
        return false;
    }

    const prev = kode[index - 1] ?? '';
    const next = kode[index + 1] ?? '';

    // Jangan sentuh digit yang merupakan bagian dari identifier seperti _0x123abc atau value2.
    if (/[A-Za-z0-9_]/.test(prev)) {
        return false;
    }

    // Hindari memecah literal desimal seperti .5
    if (prev === '.' && /[0-9]/.test(next)) {
        return false;
    }

    return true;
}

function lewatiAngka(kode, index) {
    let cursor = index + 1;

    while (cursor < kode.length && /[0-9A-Fa-fxXpP._]/.test(kode[cursor])) {
        cursor += 1;
    }

    return cursor;
}
