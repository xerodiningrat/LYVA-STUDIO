import luaparse from 'luaparse';

export const NAMA_ALAT = 'LYVA SHIELD';
export const BATAS_INPUT_BYTE = 500 * 1024;

const LEVEL_VALID = new Set(['light', 'medium', 'heavy']);
const SYNTAX_VALID = new Set(['auto', 'lua', 'luau']);
const OPSI_PARSER = Object.freeze({
    comments: true,
    ranges: true,
    luaVersion: '5.1',
    encodingMode: 'pseudo-latin1',
});

class Scope {
    constructor(parent = null) {
        this.parent = parent;
        this.bindings = [];
    }

    tambahBinding(namaAsli, namaBaru) {
        const binding = { namaAsli, namaBaru };
        this.bindings.push(binding);
        return binding;
    }

    cari(namaAsli) {
        let scopeAktif = this;

        while (scopeAktif) {
            for (let index = scopeAktif.bindings.length - 1; index >= 0; index -= 1) {
                const binding = scopeAktif.bindings[index];

                if (binding.namaAsli === namaAsli) {
                    return binding;
                }
            }

            scopeAktif = scopeAktif.parent;
        }

        return null;
    }
}

export class KesalahanObfuscator extends Error {
    constructor(pesan, status = 400) {
        super(pesan);
        this.name = 'KesalahanObfuscator';
        this.status = status;
    }
}

export function obfuscateLua(kodeMasuk, levelMasuk = 'light', syntaxMasuk = 'auto') {
    const kode = validasiKodeMasuk(kodeMasuk);
    const level = validasiLevel(levelMasuk);
    const syntax = validasiSyntax(syntaxMasuk);
    const parsed = parseLua(kode, syntax);
    const ast = parsed.ast;
    const kodeKerja = parsed.outputCode;
    const konteks = buatKonteksNama();
    const replacements = [];
    const scopeAkar = new Scope();

    praScanChunk(ast, scopeAkar, konteks);
    telusuriChunk(ast, scopeAkar, replacements, konteks);

    tambahReplacementKomentar(ast.comments, kodeKerja, replacements);

    const kodeTertransformasi = terapkanReplacements(kodeKerja, replacements);
    const kodeDirapikan = rapikanKode(kodeTertransformasi);
    const hasilAkhir = level === 'heavy'
        ? bungkusLevelHeavy(kodeDirapikan, konteks)
        : gabungkanBanner(kodeDirapikan);

    return {
        result: hasilAkhir,
        stats: {
            before: Buffer.byteLength(kode, 'utf8'),
            after: Buffer.byteLength(hasilAkhir, 'utf8'),
        },
    };
}

function validasiKodeMasuk(kodeMasuk) {
    if (typeof kodeMasuk !== 'string') {
        throw new KesalahanObfuscator('Field "code" wajib berupa string.');
    }

    const ukuran = Buffer.byteLength(kodeMasuk, 'utf8');

    if (ukuran === 0) {
        throw new KesalahanObfuscator('Kode Lua tidak boleh kosong.');
    }

    if (ukuran > BATAS_INPUT_BYTE) {
        throw new KesalahanObfuscator('Ukuran kode melebihi batas 500KB.');
    }

    return kodeMasuk;
}

function validasiLevel(levelMasuk) {
    const level = typeof levelMasuk === 'string' ? levelMasuk.toLowerCase().trim() : '';

    if (!LEVEL_VALID.has(level)) {
        throw new KesalahanObfuscator('Level obfuscation harus salah satu dari: light, medium, heavy.');
    }

    return level;
}

function validasiSyntax(syntaxMasuk) {
    const syntax = typeof syntaxMasuk === 'string' ? syntaxMasuk.toLowerCase().trim() : '';

    if (!SYNTAX_VALID.has(syntax)) {
        throw new KesalahanObfuscator('Syntax harus salah satu dari: auto, lua, luau.');
    }

    return syntax;
}

function parseLua(kode, syntaxMasuk) {
    if (syntaxMasuk === 'lua') {
        try {
            return parseDenganSyntax(kode, 'lua');
        } catch (error) {
            if (terlihatSepertiLuau(kode)) {
                throw tambahHintLuau(error);
            }
            throw error;
        }
    }

    if (syntaxMasuk === 'luau') {
        return parseDenganSyntax(kode, 'luau');
    }

    const urutan = terlihatSepertiLuau(kode)
        ? ['luau', 'lua']
        : ['lua', 'luau'];

    let kesalahanTerakhir = null;

    for (const syntax of urutan) {
        try {
            return parseDenganSyntax(kode, syntax);
        } catch (error) {
            kesalahanTerakhir = error;
        }
    }

    throw kesalahanTerakhir ?? new KesalahanObfuscator('Gagal membaca kode yang diberikan.');
}

function parseDenganSyntax(kode, syntax) {
    try {
        const sumber = syntax === 'luau'
            ? normalisasiLuauUntukParser(kode)
            : { parserCode: kode, outputCode: kode };

        return {
            ast: luaparse.parse(sumber.parserCode, OPSI_PARSER),
            kode: sumber.parserCode,
            outputCode: sumber.outputCode,
        };
    } catch (error) {
        if (syntax === 'luau') {
            try {
                const sumberFallback = normalisasiLuauFallback(kode);
                return {
                    ast: luaparse.parse(sumberFallback, OPSI_PARSER),
                    kode: sumberFallback,
                    outputCode: sumberFallback,
                };
            } catch {
                // Biarkan jatuh ke formatter error utama di bawah.
            }
        }

        throw formatKesalahanParser(error, syntax);
    }
}

function tentukanSyntax(kode, syntaxMasuk) {
    if (syntaxMasuk === 'lua' || syntaxMasuk === 'luau') {
        return syntaxMasuk;
    }

    return terlihatSepertiLuau(kode) ? 'luau' : 'lua';
}

function terlihatSepertiLuau(kode) {
    return /\btype\s+[A-Za-z_][A-Za-z0-9_]*\s*=/.test(kode)
        || /\bexport\s+type\b/.test(kode)
        || /::/.test(kode)
        || /\bcontinue\b/.test(kode)
        || /<\s*(const|close)\s*>/.test(kode)
        || /\bfunction\b[^\n]*<[^>\n]+>\s*\(/.test(kode)
        || /\blocal\s+[A-Za-z_][A-Za-z0-9_]*\s*:\s*/.test(kode)
        || /\(\s*[A-Za-z_][A-Za-z0-9_]*\s*:\s*/.test(kode);
}

function formatKesalahanParser(error, syntax) {
    const baris = Number.isInteger(error?.line) ? error.line : null;
    const kolom = Number.isInteger(error?.column) ? error.column : null;
    const posisi = baris !== null && kolom !== null
        ? `baris ${baris}, kolom ${kolom}`
        : 'posisi yang tidak diketahui';

    const labelSyntax = syntax === 'luau' ? 'Luau' : 'Lua';
    return new KesalahanObfuscator(`Gagal membaca kode ${labelSyntax} pada ${posisi}: ${error.message}`);
}

function tambahHintLuau(error) {
    if (!(error instanceof KesalahanObfuscator)) {
        return error;
    }

    return new KesalahanObfuscator(`${error.message} Coba pilih syntax "Luau" atau "Auto Detect".`, error.status);
}

function normalisasiLuauUntukParser(kode) {
    if (kode.includes('`')) {
        throw new KesalahanObfuscator('String interpolation Luau dengan backtick belum didukung.');
    }

    if (/\bdeclare\b/.test(kode)) {
        throw new KesalahanObfuscator('Statement "declare" Luau belum didukung.');
    }

    if (/\bif\b[\s\S]*?\bthen\b[\s\S]*?\belse\b/.test(kode) && /=\s*if\b/.test(kode)) {
        throw new KesalahanObfuscator('If-expression Luau belum didukung.');
    }

    const kodeTanpaCompound = normalisasiCompoundAssignmentLuau(kode);
    const tokenData = tokenizeLuau(kodeTanpaCompound);
    const replacements = [];
    const tokens = tokenData.tokens;
    const semuaToken = tokens.filter((token) => token.type !== 'newline');

    for (let index = 0; index < semuaToken.length; index += 1) {
        const token = semuaToken[index];

        if (token.value === 'continue') {
            replacements.push({
                start: token.start,
                end: token.end,
                text: padSameLength('break', token.end - token.start),
            });
        }
    }

    tandaiTypeAliases(kodeTanpaCompound, tokens, replacements);
    tandaiFunctionGenerics(kodeTanpaCompound, semuaToken, replacements);
    tandaiLocalAttributes(kodeTanpaCompound, semuaToken, replacements);
    tandaiTypeAnnotations(kodeTanpaCompound, tokens, replacements);
    tandaiTypeAssertions(kodeTanpaCompound, tokens, replacements);

    return {
        parserCode: terapkanReplacementsPanjangTetap(kodeTanpaCompound, replacements),
        outputCode: kodeTanpaCompound,
    };
}

function normalisasiLuauFallback(kode) {
    let hasil = normalisasiLuauUntukParser(kode);
    const methodProtections = [];

    hasil = hasil.replace(/([A-Za-z0-9_\])}"'])\s*:\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(/g, (match, base, nama) => {
        const token = `__LYVA_METHOD_${methodProtections.length}__`;
        methodProtections.push(`${base}:${nama}(`);
        return `${token}`;
    });

    hasil = hasil.replace(/\)\s*:\s*([A-Za-z_][A-Za-z0-9_<>,.?|&{}\[\] \t]*)/g, (match) => {
        return gantiDenganWhitespacePanjangTetap(match);
    });

    hasil = hasil.replace(/:\s*([A-Za-z_][A-Za-z0-9_<>,.?|&{}\[\] \t]*)(?=[,)=\n])/g, (match) => {
        return gantiDenganWhitespacePanjangTetap(match);
    });

    hasil = hasil.replace(/\b(type|export\s+type)[^\n]*/g, (match) => {
        return gantiDenganWhitespacePanjangTetap(match);
    });

    hasil = hasil.replace(/__LYVA_METHOD_(\d+)__/g, (_match, index) => {
        return methodProtections[Number.parseInt(index, 10)] ?? _match;
    });

    return hasil;
}

function tokenizeLuau(kode) {
    const tokens = [];
    let index = 0;

    while (index < kode.length) {
        const char = kode[index];

        if (char === '\n') {
            tokens.push({ type: 'newline', value: '\n', start: index, end: index + 1 });
            index += 1;
            continue;
        }

        if (char === '\r' || char === ' ' || char === '\t' || char === '\f' || char === '\v') {
            index += 1;
            continue;
        }

        if (char === '-' && kode[index + 1] === '-') {
            const infoLong = bacaPembukaKurungPanjang(kode, index + 2);

            if (infoLong) {
                index = lewatiStringPanjang(kode, infoLong.start, infoLong.equalsCount, tokens);
                continue;
            }

            index += 2;
            while (index < kode.length && kode[index] !== '\n') {
                index += 1;
            }
            continue;
        }

        if (char === '"' || char === '\'') {
            const start = index;
            index = lewatiStringPendek(kode, index, char);
            tokens.push({ type: 'string', value: 'string', start, end: index });
            continue;
        }

        const infoLongString = bacaPembukaKurungPanjang(kode, index);
        if (infoLongString) {
            const start = index;
            index = lewatiStringPanjang(kode, infoLongString.start, infoLongString.equalsCount, tokens);
            tokens.push({ type: 'string', value: 'string', start, end: index });
            continue;
        }

        if (adalahAwalIdentifier(char)) {
            const start = index;
            index += 1;

            while (index < kode.length && adalahBagianIdentifier(kode[index])) {
                index += 1;
            }

            const value = kode.slice(start, index);
            tokens.push({
                type: KEYWORD_LUAU.has(value) ? 'keyword' : 'identifier',
                value,
                start,
                end: index,
            });
            continue;
        }

        if (adalahDigit(char)) {
            const start = index;
            index += 1;

            while (index < kode.length && /[0-9A-Fa-fxXpP._]/.test(kode[index])) {
                index += 1;
            }

            tokens.push({ type: 'number', value: kode.slice(start, index), start, end: index });
            continue;
        }

        const operator = OPERATORS_LUAU.find((item) => kode.startsWith(item, index));
        if (operator) {
            tokens.push({ type: 'operator', value: operator, start: index, end: index + operator.length });
            index += operator.length;
            continue;
        }

        tokens.push({ type: 'punct', value: char, start: index, end: index + 1 });
        index += 1;
    }

    return { tokens };
}

function normalisasiCompoundAssignmentLuau(kode) {
    if (!/[+\-*/%^]=|\/\/=|\.\.=/.test(kode)) {
        return kode;
    }

    const { tokens } = tokenizeLuau(kode);
    const replacements = [];
    let depthKurung = 0;
    let depthKurawal = 0;
    let depthSiku = 0;

    for (let index = 0; index < tokens.length; index += 1) {
        const token = tokens[index];

        if (token.type !== 'newline') {
            if (token.value === '(') depthKurung += 1;
            if (token.value === ')') depthKurung = Math.max(0, depthKurung - 1);
            if (token.value === '{') depthKurawal += 1;
            if (token.value === '}') depthKurawal = Math.max(0, depthKurawal - 1);
            if (token.value === '[') depthSiku += 1;
            if (token.value === ']') depthSiku = Math.max(0, depthSiku - 1);
        }

        if (depthKurung !== 0 || depthKurawal !== 0 || depthSiku !== 0) {
            continue;
        }

        const operatorDasar = COMPOUND_OPERATOR_MAP.get(token.value);
        if (!operatorDasar) {
            continue;
        }

        const mulai = cariAwalStatementLuau(tokens, index, kode);
        const akhir = cariAkhirStatementLuau(tokens, index, kode);
        const sebelum = kode.slice(mulai, token.start);
        const sesudah = kode.slice(token.end, akhir);
        const indentasi = (sebelum.match(/^[ \t]*/) ?? [''])[0];
        const lhs = sebelum.trim();
        const rhs = sesudah.trim();

        if (lhs === '' || rhs === '') {
            throw new KesalahanObfuscator(`Compound assignment Luau tidak bisa dinormalisasi di dekat "${token.value}".`);
        }

        replacements.push({
            start: mulai,
            end: akhir,
            text: `${indentasi}${lhs} = ${lhs} ${operatorDasar} ${rhs}`,
        });

        while (index + 1 < tokens.length && tokens[index + 1].start < akhir) {
            index += 1;
        }
    }

    return terapkanReplacementBebas(kode, replacements);
}

const KEYWORD_LUAU = new Set([
    'and', 'break', 'continue', 'do', 'else', 'elseif', 'end', 'export', 'false', 'for',
    'function', 'if', 'in', 'local', 'nil', 'not', 'or', 'repeat', 'return', 'then',
    'true', 'type', 'until', 'while', 'typeof',
]);

const OPERATORS_LUAU = [
    '...', '::', '//=', '+=', '-=', '*=', '/=', '%=', '^=', '..=', '==', '~=', '<=', '>=', '//', '..', '->',
];

const COMPOUND_OPERATOR_MAP = new Map([
    ['+=', '+'],
    ['-=', '-'],
    ['*=', '*'],
    ['/=', '/'],
    ['%=', '%'],
    ['^=', '^'],
    ['..=', '..'],
    ['//=', '//'],
]);

const BATAS_AWAL_STATEMENT_LUAU = new Set(['then', 'do', 'else', 'elseif', 'repeat']);
const BATAS_AKHIR_STATEMENT_LUAU = new Set(['end', 'else', 'elseif', 'until']);

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
        end: cursor + 1,
    };
}

function lewatiStringPanjang(kode, index, equalsCount, tokens) {
    const penutup = `]${'='.repeat(equalsCount)}]`;
    let cursor = index + 2 + equalsCount;

    while (cursor < kode.length) {
        if (kode.startsWith(penutup, cursor)) {
            return cursor + penutup.length;
        }

        if (kode[cursor] === '\n' && Array.isArray(tokens)) {
            tokens.push({ type: 'newline', value: '\n', start: cursor, end: cursor + 1 });
        }

        cursor += 1;
    }

    return kode.length;
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

function adalahAwalIdentifier(char) {
    return /[A-Za-z_]/.test(char);
}

function adalahBagianIdentifier(char) {
    return /[A-Za-z0-9_]/.test(char);
}

function adalahDigit(char) {
    return /[0-9]/.test(char);
}

function tandaiTypeAliases(kode, tokens, replacements) {
    const signifikan = tokens.filter((token) => token.type !== 'newline');

    for (let index = 0; index < signifikan.length; index += 1) {
        const token = signifikan[index];
        const berikut = signifikan[index + 1];
        const sebelumnya = cariTokenSebelumnyaSignifikan(tokens, token.start);
        const mulaiStatement = sebelumnya === null || sebelumnya.type === 'newline' || [';', 'do', 'then', 'else', 'repeat'].includes(sebelumnya.value);

        if (!mulaiStatement) {
            continue;
        }

        if (token.value === 'type') {
            const akhir = cariAkhirStatementTipe(tokens, token.start);
            replacements.push({ start: token.start, end: akhir, text: gantiDenganWhitespacePanjangTetap(kode.slice(token.start, akhir)) });
            continue;
        }

        if (token.value === 'export' && berikut?.value === 'type') {
            const akhir = cariAkhirStatementTipe(tokens, token.start);
            replacements.push({ start: token.start, end: akhir, text: gantiDenganWhitespacePanjangTetap(kode.slice(token.start, akhir)) });
        }
    }
}

function cariAkhirStatementTipe(tokens, posisiMulai) {
    let kurung = 0;
    let kurawal = 0;
    let siku = 0;
    let sudut = 0;
    let aktif = false;

    for (const token of tokens) {
        if (token.end <= posisiMulai) {
            continue;
        }

        aktif = true;

        if (token.type === 'newline' && kurung === 0 && kurawal === 0 && siku === 0 && sudut === 0) {
            return token.start;
        }

        if (token.value === '(') kurung += 1;
        if (token.value === ')') kurung = Math.max(0, kurung - 1);
        if (token.value === '{') kurawal += 1;
        if (token.value === '}') kurawal = Math.max(0, kurawal - 1);
        if (token.value === '[') siku += 1;
        if (token.value === ']') siku = Math.max(0, siku - 1);
        if (token.value === '<') sudut += 1;
        if (token.value === '>') sudut = Math.max(0, sudut - 1);
        if (token.value === ';' && kurung === 0 && kurawal === 0 && siku === 0 && sudut === 0) {
            return token.start;
        }
    }

    if (aktif) {
        return tokens[tokens.length - 1]?.end ?? posisiMulai;
    }

    return posisiMulai;
}

function tandaiFunctionGenerics(kode, tokens, replacements) {
    for (let index = 0; index < tokens.length; index += 1) {
        if (tokens[index].value !== 'function') {
            continue;
        }

        let cursor = index + 1;
        let kedalaman = 0;

        while (cursor < tokens.length) {
            const token = tokens[cursor];

            if (token.value === '(' && kedalaman === 0) {
                break;
            }

            if (token.value === '<' && kedalaman === 0) {
                const akhir = cariPasangan(tokens, cursor, '<', '>');
                if (akhir !== -1) {
                    replacements.push({
                        start: token.start,
                        end: tokens[akhir].end,
                        text: gantiDenganWhitespacePanjangTetap(kode.slice(token.start, tokens[akhir].end)),
                    });
                }
                break;
            }

            if (token.value === '[' || token.value === '{' || token.value === '(') kedalaman += 1;
            if (token.value === ']' || token.value === '}' || token.value === ')') kedalaman = Math.max(0, kedalaman - 1);

            cursor += 1;
        }
    }
}

function tandaiLocalAttributes(kode, tokens, replacements) {
    for (let index = 0; index < tokens.length - 2; index += 1) {
        const token = tokens[index];
        const tengah = tokens[index + 1];
        const akhir = tokens[index + 2];

        if (token.value !== '<' || akhir.value !== '>') {
            continue;
        }

        if (tengah.type !== 'identifier' || !['const', 'close'].includes(tengah.value)) {
            continue;
        }

        replacements.push({
            start: token.start,
            end: akhir.end,
            text: gantiDenganWhitespacePanjangTetap(kode.slice(token.start, akhir.end)),
        });
    }
}

function tandaiTypeAnnotations(kode, tokens, replacements) {
    for (let index = 0; index < tokens.length; index += 1) {
        const token = tokens[index];

        if (token.value !== ':') {
            continue;
        }

        const sebelumnya = tokens[index - 1];
        const berikut = tokens[index + 1];

        if (!sebelumnya || !berikut) {
            continue;
        }

        if (sebelumnya.value === ')' || sebelumnya.type === 'identifier' || sebelumnya.value === '...') {
            if (berikut.value === ':') {
                continue;
            }

            if (apakahColonMethod(tokens, index)) {
                continue;
            }

            const akhir = cariAkhirEkspresiTipe(tokens, index + 1, {
                stopValues: new Set([',', ')', '=', 'in']),
                stopOnNewline: true,
            });

            if (akhir > token.start) {
                replacements.push({
                    start: token.start,
                    end: akhir,
                    text: gantiDenganWhitespacePanjangTetap(kode.slice(token.start, akhir)),
                });
            }
        }
    }
}

function tandaiTypeAssertions(kode, tokens, replacements) {
    for (let index = 0; index < tokens.length; index += 1) {
        const token = tokens[index];

        if (token.value !== '::') {
            continue;
        }

        const akhir = cariAkhirEkspresiTipe(tokens, index + 1, {
            stopValues: new Set([',', ')', ']', '}', ';', 'then', 'do', 'end', 'elseif', 'else', 'until']),
            stopOnNewline: false,
        });

        if (akhir > token.start) {
            replacements.push({
                start: token.start,
                end: akhir,
                text: gantiDenganWhitespacePanjangTetap(kode.slice(token.start, akhir)),
            });
        }
    }
}

function apakahColonMethod(tokens, colonIndex) {
    const sebelumnya = tokens[colonIndex - 1];
    const sesudah = tokens[colonIndex + 1];
    const berikutnya = tokens[colonIndex + 2];

    return (
        sebelumnya?.type === 'identifier'
            || sebelumnya?.type === 'number'
            || sebelumnya?.type === 'string'
            || [')', ']', '}'].includes(sebelumnya?.value)
    )
        && sesudah?.type === 'identifier'
        && berikutnya?.value === '(';
}

function cariAkhirEkspresiTipe(tokens, startIndex, opsi) {
    let kurung = 0;
    let kurawal = 0;
    let siku = 0;
    let sudut = 0;

    for (let index = startIndex; index < tokens.length; index += 1) {
        const token = tokens[index];

        if (token.type === 'newline' && opsi.stopOnNewline && kurung === 0 && kurawal === 0 && siku === 0 && sudut === 0) {
            return token.start;
        }

        if (token.value === '(') kurung += 1;
        if (token.value === ')') {
            if (kurung === 0 && opsi.stopValues.has(')')) {
                return token.start;
            }
            kurung = Math.max(0, kurung - 1);
            continue;
        }
        if (token.value === '{') kurawal += 1;
        if (token.value === '}') {
            if (kurawal === 0 && opsi.stopValues.has('}')) {
                return token.start;
            }
            kurawal = Math.max(0, kurawal - 1);
            continue;
        }
        if (token.value === '[') siku += 1;
        if (token.value === ']') {
            if (siku === 0 && opsi.stopValues.has(']')) {
                return token.start;
            }
            siku = Math.max(0, siku - 1);
            continue;
        }
        if (token.value === '<') sudut += 1;
        if (token.value === '>') sudut = Math.max(0, sudut - 1);

        if (kurung === 0 && kurawal === 0 && siku === 0 && sudut === 0) {
            if (opsi.stopValues.has(token.value)) {
                return token.start;
            }

            if (token.type === 'keyword' && BODY_START_KEYWORDS.has(token.value)) {
                return token.start;
            }
        }
    }

    return tokens[tokens.length - 1]?.end ?? 0;
}

const BODY_START_KEYWORDS = new Set([
    'break', 'continue', 'do', 'else', 'elseif', 'end', 'for', 'function', 'if', 'local',
    'repeat', 'return', 'then', 'until', 'while',
]);

function cariTokenSebelumnyaSignifikan(tokens, posisi) {
    let kandidat = null;

    for (const token of tokens) {
        if (token.end > posisi) {
            break;
        }

        kandidat = token;
    }

    return kandidat;
}

function cariPasangan(tokens, startIndex, buka, tutup) {
    let depth = 0;

    for (let index = startIndex; index < tokens.length; index += 1) {
        const token = tokens[index];
        if (token.value === buka) depth += 1;
        if (token.value === tutup) {
            depth -= 1;
            if (depth === 0) {
                return index;
            }
        }
    }

    return -1;
}

function cariAwalStatementLuau(tokens, operatorIndex, kode) {
    let mulai = 0;

    for (let index = operatorIndex - 1; index >= 0; index -= 1) {
        const token = tokens[index];

        if (token.type === 'newline') {
            mulai = token.end;
            break;
        }

        if (token.value === ';') {
            mulai = token.end;
            break;
        }

        if (BATAS_AWAL_STATEMENT_LUAU.has(token.value)) {
            mulai = token.end;
            break;
        }
    }

    while (mulai < kode.length && /[ \t]/.test(kode[mulai])) {
        mulai += 1;
    }

    return mulai;
}

function cariAkhirStatementLuau(tokens, operatorIndex, kode) {
    let akhir = kode.length;

    for (let index = operatorIndex + 1; index < tokens.length; index += 1) {
        const token = tokens[index];

        if (token.type === 'newline') {
            akhir = token.start;
            break;
        }

        if (token.value === ';' || BATAS_AKHIR_STATEMENT_LUAU.has(token.value)) {
            akhir = token.start;
            break;
        }
    }

    while (akhir > 0 && /[ \t]/.test(kode[akhir - 1])) {
        akhir -= 1;
    }

    return akhir;
}

function padSameLength(teks, panjang) {
    if (teks.length >= panjang) {
        return teks.slice(0, panjang);
    }

    return teks + ' '.repeat(panjang - teks.length);
}

function gantiDenganWhitespacePanjangTetap(teks) {
    return teks.replace(/[^\r\n]/g, ' ');
}

function terapkanReplacementsPanjangTetap(kode, replacements) {
    const unik = new Map();

    for (const replacement of replacements) {
        if (!replacement || replacement.start >= replacement.end) {
            continue;
        }

        unik.set(`${replacement.start}:${replacement.end}`, replacement);
    }

    const daftar = [...unik.values()].sort((a, b) => a.start - b.start);
    let cursor = 0;
    let hasil = '';

    for (const replacement of daftar) {
        if (replacement.start < cursor) {
            continue;
        }

        hasil += kode.slice(cursor, replacement.start);
        hasil += replacement.text;
        cursor = replacement.end;
    }

    hasil += kode.slice(cursor);
    return hasil;
}

function terapkanReplacementBebas(kode, replacements) {
    const unik = new Map();

    for (const replacement of replacements) {
        if (!replacement || replacement.start >= replacement.end) {
            continue;
        }

        unik.set(`${replacement.start}:${replacement.end}`, replacement);
    }

    const daftar = [...unik.values()].sort((a, b) => a.start - b.start);
    let cursor = 0;
    let hasil = '';

    for (const replacement of daftar) {
        if (replacement.start < cursor) {
            continue;
        }

        hasil += kode.slice(cursor, replacement.start);
        hasil += replacement.text;
        cursor = replacement.end;
    }

    hasil += kode.slice(cursor);
    return hasil;
}

function buatKonteksNama() {
    return {
        urutan: 0,
        sudahDipakai: new Set(),
        methodBindings: new Map(),
    };
}

function buatNamaObfuscated(konteks) {
    while (true) {
        konteks.urutan += 1;

        const nilai = (0x1f3d5b79 ^ (konteks.urutan * 0x45d9f3b)) >>> 0;
        const kandidat = `_0x${nilai.toString(16).padStart(8, '0')}`;

        if (!konteks.sudahDipakai.has(kandidat)) {
            konteks.sudahDipakai.add(kandidat);
            return kandidat;
        }
    }
}

function gabungkanBanner(kode) {
    return `${kode}`.trimEnd();
}

function bungkusLevelHeavy(kode, konteks) {
    const namaWrapper = buatNamaObfuscated(konteks);
    const isiTerindentasi = indentasiBlok(kode, '    ');

    return [
        `local ${namaWrapper} = function(...)`,
        isiTerindentasi,
        'end',
        `return ${namaWrapper}(...)`,
    ].join('\n').trimEnd();
}

function indentasiBlok(teks, indentasi) {
    return teks
        .split('\n')
        .map((baris) => (baris.length > 0 ? `${indentasi}${baris}` : indentasi))
        .join('\n');
}

function tambahReplacementKomentar(comments, kodeAsli, replacements) {
    if (!Array.isArray(comments)) {
        return;
    }

    for (const komentar of comments) {
        if (!Array.isArray(komentar.range) || komentar.range.length !== 2) {
            continue;
        }

        const [mulai, selesai] = komentar.range;
        const raw = kodeAsli.slice(mulai, selesai);

        replacements.push({
            start: mulai,
            end: selesai,
            text: gantiKomentarDenganWhitespace(raw),
        });
    }
}

function gantiKomentarDenganWhitespace(teks) {
    return teks.replace(/[^\r\n]/g, ' ');
}

function rapikanKode(kode) {
    return kode
        .replace(/\r\n/g, '\n')
        .replace(/[ \t]+\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function terapkanReplacements(kode, replacements) {
    const mapUnik = new Map();

    for (const replacement of replacements) {
        if (!replacement || typeof replacement.start !== 'number' || typeof replacement.end !== 'number') {
            continue;
        }

        if (replacement.start >= replacement.end) {
            continue;
        }

        const kunci = `${replacement.start}:${replacement.end}`;
        mapUnik.set(kunci, replacement);
    }

    const daftar = [...mapUnik.values()].sort((a, b) => a.start - b.start);
    let cursor = 0;
    let hasil = '';

    for (const replacement of daftar) {
        if (replacement.start < cursor) {
            throw new KesalahanObfuscator('Terjadi tabrakan transformasi saat memproses kode.');
        }

        hasil += kode.slice(cursor, replacement.start);
        hasil += replacement.text;
        cursor = replacement.end;
    }

    hasil += kode.slice(cursor);
    return hasil;
}

function telusuriChunk(ast, scope, replacements, konteks) {
    if (!ast || !Array.isArray(ast.body)) {
        throw new KesalahanObfuscator('AST Lua tidak valid.');
    }

    telusuriDaftarPernyataan(ast.body, scope, replacements, konteks);
}

function praScanChunk(ast, scope, konteks) {
    if (!ast || !Array.isArray(ast.body)) {
        throw new KesalahanObfuscator('AST Lua tidak valid.');
    }

    praScanDaftarPernyataan(ast.body, scope, konteks);
}

function praScanDaftarPernyataan(pernyataanList, scope, konteks) {
    if (!Array.isArray(pernyataanList)) {
        return;
    }

    for (const node of pernyataanList) {
        praScanPernyataan(node, scope, konteks);
    }
}

function praScanPernyataan(node, scope, konteks) {
    if (!node || typeof node.type !== 'string') {
        return;
    }

    switch (node.type) {
        case 'LocalStatement':
            for (const variabel of node.variables ?? []) {
                if (variabel?.type !== 'Identifier') {
                    continue;
                }

                const namaBaru = ambilAtauBuatNamaNode(variabel, konteks);
                scope.tambahBinding(variabel.name, namaBaru);
            }
            break;

        case 'LocalFunctionStatement': {
            if (node.identifier?.type === 'Identifier') {
                const namaBaru = ambilAtauBuatNamaNode(node.identifier, konteks);
                scope.tambahBinding(node.identifier.name, namaBaru);
            }

            const scopeFungsi = new Scope(scope);
            praScanParameter(node.parameters ?? [], scopeFungsi, konteks);
            praScanDaftarPernyataan(node.body ?? [], scopeFungsi, konteks);
            break;
        }

        case 'FunctionDeclaration': {
            if (node.identifier?.type === 'Identifier') {
                const bindingLama = scope.cari(node.identifier.name);
                if (bindingLama) {
                    node.identifier.__obfuscatedName = bindingLama.namaBaru;
                } else {
                    const namaBaru = ambilAtauBuatNamaNode(node.identifier, konteks);
                    scope.tambahBinding(node.identifier.name, namaBaru);
                }
            }

            praScanTargetFungsi(node.identifier, scope, konteks);
            const scopeFungsi = new Scope(scope);
            praScanParameter(node.parameters ?? [], scopeFungsi, konteks);
            praScanDaftarPernyataan(node.body ?? [], scopeFungsi, konteks);
            break;
        }

        case 'WhileStatement':
        case 'DoStatement': {
            const childScope = new Scope(scope);
            praScanDaftarPernyataan(node.body ?? [], childScope, konteks);
            break;
        }

        case 'RepeatStatement': {
            const repeatScope = new Scope(scope);
            praScanDaftarPernyataan(node.body ?? [], repeatScope, konteks);
            break;
        }

        case 'IfStatement':
            for (const clause of node.clauses ?? []) {
                const childScope = new Scope(scope);
                praScanDaftarPernyataan(clause.body ?? [], childScope, konteks);
            }
            break;

        case 'ForNumericStatement': {
            const loopScope = new Scope(scope);

            if (node.variable?.type === 'Identifier') {
                const namaBaru = ambilAtauBuatNamaNode(node.variable, konteks);
                loopScope.tambahBinding(node.variable.name, namaBaru);
            }

            praScanDaftarPernyataan(node.body ?? [], loopScope, konteks);
            break;
        }

        case 'ForGenericStatement': {
            const loopScope = new Scope(scope);

            for (const variabel of node.variables ?? []) {
                if (variabel?.type !== 'Identifier') {
                    continue;
                }

                const namaBaru = ambilAtauBuatNamaNode(variabel, konteks);
                loopScope.tambahBinding(variabel.name, namaBaru);
            }

            praScanDaftarPernyataan(node.body ?? [], loopScope, konteks);
            break;
        }

        default:
            break;
    }
}

function praScanParameter(parameters, scope, konteks) {
    for (const parameter of parameters ?? []) {
        if (parameter?.type !== 'Identifier') {
            continue;
        }

        const namaBaru = ambilAtauBuatNamaNode(parameter, konteks);
        scope.tambahBinding(parameter.name, namaBaru);
    }
}

function praScanTargetFungsi(target, scope, konteks) {
    if (!target || target.type !== 'MemberExpression') {
        return;
    }

    if (target.base?.type !== 'Identifier' || target.identifier?.type !== 'Identifier') {
        return;
    }

    const kunci = buatKunciMethod(resolveMethodBaseKey(target.base.name, scope), target.identifier.name);
    if (!konteks.methodBindings.has(kunci)) {
        konteks.methodBindings.set(kunci, buatNamaObfuscated(konteks));
    }
}

function telusuriDaftarPernyataan(pernyataanList, scope, replacements, konteks) {
    for (const node of pernyataanList) {
        telusuriPernyataan(node, scope, replacements, konteks);
    }
}

function telusuriPernyataan(node, scope, replacements, konteks) {
    if (!node || typeof node.type !== 'string') {
        return;
    }

    switch (node.type) {
        case 'AssignmentStatement':
            telusuriArray(node.variables, scope, replacements, konteks, telusuriEkspresi);
            telusuriArray(node.init, scope, replacements, konteks, telusuriEkspresi);
            break;

        case 'LocalStatement':
            telusuriArray(node.init, scope, replacements, konteks, telusuriEkspresi);

            for (const variabel of node.variables ?? []) {
                if (variabel?.type !== 'Identifier') {
                    continue;
                }

                const namaBaru = ambilAtauBuatNamaNode(variabel, konteks);
                scope.tambahBinding(variabel.name, namaBaru);
                tambahReplacementNode(variabel, namaBaru, replacements);
            }
            break;

        case 'CallStatement':
            telusuriEkspresi(node.expression, scope, replacements, konteks);
            break;

        case 'ReturnStatement':
            telusuriArray(node.arguments, scope, replacements, konteks, telusuriEkspresi);
            break;

        case 'LocalFunctionStatement':
            telusuriFungsi(node, scope, replacements, konteks);
            break;

        case 'FunctionDeclaration':
            telusuriFungsi(node, scope, replacements, konteks);
            break;

        case 'WhileStatement':
            telusuriEkspresi(node.condition, scope, replacements, konteks);
            telusuriDaftarPernyataan(node.body ?? [], new Scope(scope), replacements, konteks);
            break;

        case 'DoStatement':
            telusuriDaftarPernyataan(node.body ?? [], new Scope(scope), replacements, konteks);
            break;

        case 'RepeatStatement': {
            const repeatScope = new Scope(scope);
            telusuriDaftarPernyataan(node.body ?? [], repeatScope, replacements, konteks);
            telusuriEkspresi(node.condition, repeatScope, replacements, konteks);
            break;
        }

        case 'IfStatement':
            telusuriIfStatement(node, scope, replacements, konteks);
            break;

        case 'ForNumericStatement': {
            telusuriEkspresi(node.start, scope, replacements, konteks);
            telusuriEkspresi(node.end, scope, replacements, konteks);
            telusuriEkspresi(node.step, scope, replacements, konteks);

            const loopScope = new Scope(scope);

            if (node.variable?.type === 'Identifier') {
                const namaBaru = ambilAtauBuatNamaNode(node.variable, konteks);
                loopScope.tambahBinding(node.variable.name, namaBaru);
                tambahReplacementNode(node.variable, namaBaru, replacements);
            }

            telusuriDaftarPernyataan(node.body ?? [], loopScope, replacements, konteks);
            break;
        }

        case 'ForGenericStatement': {
            telusuriArray(node.iterators, scope, replacements, konteks, telusuriEkspresi);

            const loopScope = new Scope(scope);

            for (const variabel of node.variables ?? []) {
                if (variabel?.type !== 'Identifier') {
                    continue;
                }

                const namaBaru = ambilAtauBuatNamaNode(variabel, konteks);
                loopScope.tambahBinding(variabel.name, namaBaru);
                tambahReplacementNode(variabel, namaBaru, replacements);
            }

            telusuriDaftarPernyataan(node.body ?? [], loopScope, replacements, konteks);
            break;
        }

        case 'BreakStatement':
            break;

        default:
            throw new KesalahanObfuscator(`Tipe statement belum didukung: ${node.type}`);
    }
}

function telusuriIfStatement(node, scope, replacements, konteks) {
    for (const clause of node.clauses ?? []) {
        if (clause.type === 'IfClause' || clause.type === 'ElseifClause') {
            telusuriEkspresi(clause.condition, scope, replacements, konteks);
            telusuriDaftarPernyataan(clause.body ?? [], new Scope(scope), replacements, konteks);
            continue;
        }

        if (clause.type === 'ElseClause') {
            telusuriDaftarPernyataan(clause.body ?? [], new Scope(scope), replacements, konteks);
            continue;
        }

        throw new KesalahanObfuscator(`Tipe if-clause belum didukung: ${clause.type}`);
    }
}

function telusuriFungsi(node, scope, replacements, konteks) {
    if (!node || typeof node !== 'object') {
        return;
    }

    if (node.isLocal && node.identifier?.type === 'Identifier') {
        const namaBaru = ambilAtauBuatNamaNode(node.identifier, konteks);
        scope.tambahBinding(node.identifier.name, namaBaru);
        tambahReplacementNode(node.identifier, namaBaru, replacements);
    } else if (node.identifier) {
        telusuriTargetFungsi(node.identifier, scope, replacements, konteks);
    }

    const scopeFungsi = new Scope(scope);

    for (const parameter of node.parameters ?? []) {
        if (parameter?.type !== 'Identifier') {
            continue;
        }

        const namaBaru = ambilAtauBuatNamaNode(parameter, konteks);
        scopeFungsi.tambahBinding(parameter.name, namaBaru);
        tambahReplacementNode(parameter, namaBaru, replacements);
    }

    telusuriDaftarPernyataan(node.body ?? [], scopeFungsi, replacements, konteks);
}

function telusuriTargetFungsi(target, scope, replacements, konteks) {
    if (!target || typeof target.type !== 'string') {
        return;
    }

    if (target.type === 'Identifier') {
        telusuriIdentifier(target, scope, replacements);
        return;
    }

    if (target.type === 'MemberExpression') {
        telusuriEkspresi(target.base, scope, replacements, konteks);
        telusuriMemberIdentifier(target, scope, replacements, konteks);
        return;
    }

    if (target.type === 'IndexExpression') {
        telusuriEkspresi(target.base, scope, replacements, konteks);
        telusuriEkspresi(target.index, scope, replacements, konteks);
        return;
    }

    throw new KesalahanObfuscator(`Target function declaration belum didukung: ${target.type}`);
}

function telusuriEkspresi(node, scope, replacements, konteks) {
    if (!node || typeof node.type !== 'string') {
        return;
    }

    switch (node.type) {
        case 'Identifier':
            telusuriIdentifier(node, scope, replacements);
            break;

        case 'StringLiteral':
            tambahReplacementNode(node, encodeStringLiteral(node.value ?? ''), replacements);
            break;

        case 'NumericLiteral':
        case 'BooleanLiteral':
        case 'NilLiteral':
        case 'VarargLiteral':
            break;

        case 'UnaryExpression':
            telusuriEkspresi(node.argument, scope, replacements, konteks);
            break;

        case 'LogicalExpression':
        case 'BinaryExpression':
            telusuriEkspresi(node.left, scope, replacements, konteks);
            telusuriEkspresi(node.right, scope, replacements, konteks);
            break;

        case 'MemberExpression':
            telusuriEkspresi(node.base, scope, replacements, konteks);
            telusuriMemberIdentifier(node, scope, replacements, konteks);
            break;

        case 'IndexExpression':
            telusuriEkspresi(node.base, scope, replacements, konteks);
            telusuriEkspresi(node.index, scope, replacements, konteks);
            break;

        case 'CallExpression':
            telusuriEkspresi(node.base, scope, replacements, konteks);
            telusuriArray(node.arguments, scope, replacements, konteks, telusuriEkspresi);
            break;

        case 'TableCallExpression':
            telusuriEkspresi(node.base, scope, replacements, konteks);
            telusuriEkspresi(node.arguments, scope, replacements, konteks);
            break;

        case 'StringCallExpression':
            telusuriEkspresi(node.base, scope, replacements, konteks);
            telusuriEkspresi(node.argument, scope, replacements, konteks);
            break;

        case 'TableConstructorExpression':
            telusuriArray(node.fields, scope, replacements, konteks, telusuriFieldTabel);
            break;

        case 'FunctionDeclaration':
            telusuriFungsi(node, scope, replacements, konteks);
            break;

        default:
            throw new KesalahanObfuscator(`Tipe expression belum didukung: ${node.type}`);
    }
}

function telusuriFieldTabel(node, scope, replacements, konteks) {
    if (!node || typeof node.type !== 'string') {
        return;
    }

    switch (node.type) {
        case 'TableKey':
            telusuriEkspresi(node.key, scope, replacements, konteks);
            telusuriEkspresi(node.value, scope, replacements, konteks);
            break;

        case 'TableKeyString':
            telusuriEkspresi(node.value, scope, replacements, konteks);
            break;

        case 'TableValue':
            telusuriEkspresi(node.value, scope, replacements, konteks);
            break;

        default:
            throw new KesalahanObfuscator(`Tipe field tabel belum didukung: ${node.type}`);
    }
}

function telusuriIdentifier(node, scope, replacements) {
    const binding = scope.cari(node.name);

    if (!binding) {
        return;
    }

    tambahReplacementNode(node, binding.namaBaru, replacements);
}

function telusuriMemberIdentifier(node, scope, replacements, konteks) {
    if (!node || node.type !== 'MemberExpression') {
        return;
    }

    if (node.base?.type !== 'Identifier' || node.identifier?.type !== 'Identifier') {
        return;
    }

    const namaMethod = cariNamaMethod(resolveMethodBaseKey(node.base.name, scope), node.identifier.name, konteks);
    if (!namaMethod) {
        return;
    }

    tambahReplacementNode(node.identifier, namaMethod, replacements);
}

function telusuriArray(daftar, scope, replacements, konteks, visitor) {
    if (!Array.isArray(daftar)) {
        return;
    }

    for (const item of daftar) {
        visitor(item, scope, replacements, konteks);
    }
}

function tambahReplacementNode(node, text, replacements) {
    if (!node || !Array.isArray(node.range) || node.range.length !== 2) {
        return;
    }

    replacements.push({
        start: node.range[0],
        end: node.range[1],
        text,
    });
}

function encodeStringLiteral(nilai) {
    const buffer = Buffer.from(String(nilai), 'latin1');
    let hasil = '"';

    for (const byte of buffer.values()) {
        hasil += `\\${byte.toString().padStart(3, '0')}`;
    }

    hasil += '"';
    return hasil;
}

function ambilAtauBuatNamaNode(node, konteks) {
    if (node && typeof node === 'object' && typeof node.__obfuscatedName === 'string') {
        return node.__obfuscatedName;
    }

    const namaBaru = buatNamaObfuscated(konteks);

    if (node && typeof node === 'object') {
        node.__obfuscatedName = namaBaru;
    }

    return namaBaru;
}

function buatKunciMethod(namaBaseObfuscated, namaMethodAsli) {
    return `${namaBaseObfuscated}::${namaMethodAsli}`;
}

function cariNamaMethod(namaBaseObfuscated, namaMethodAsli, konteks) {
    return konteks.methodBindings.get(buatKunciMethod(namaBaseObfuscated, namaMethodAsli)) ?? null;
}

function resolveMethodBaseKey(namaBase, scope) {
    const binding = scope.cari(namaBase);

    if (binding) {
        return `local:${binding.namaBaru}`;
    }

    return `global:${namaBase}`;
}
