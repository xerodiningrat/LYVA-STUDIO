export function jalankanFlowFlatteningAman(kode, opsi = {}) {
    const level = normalisasiLevel(opsi.level);

    if (level === 'light') {
        return {
            code: kode,
            warnings: [
                'Layer 4 flattening penuh dinonaktifkan demi keamanan; mode light tidak menambah dead code.',
            ],
            meta: {
                deadCodeInserted: 0,
                enabled: false,
            },
        };
    }

    const deadCode = bangunDeadCode(level);

    return {
        code: deadCode + kode,
        warnings: [
            level === 'max-safe'
                ? 'Layer 4 menambah beberapa blok dead code aman, tanpa state-machine anti-analysis.'
                : 'Layer 4 diganti mode aman: hanya menambah dead code sederhana, tanpa state-machine anti-analysis.',
        ],
        meta: {
            deadCodeInserted: level === 'max-safe' ? 3 : 1,
            enabled: true,
        },
    };
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

function bangunDeadCode(level) {
    const blok = [
        [
            'local _0xdeadbeef = (math.random() > 2)',
            '    and (function() return nil end)()',
            '    or nil',
            'if _0xdeadbeef then',
            '    local _0xfakefeed = "\\116\\101\\115\\116"',
            '    _0xfakefeed = _0xfakefeed .. "\\000"',
            'end',
            '',
        ].join('\n'),
    ];

    if (level === 'max-safe') {
        blok.push([
            'do',
            '    local _0xfeedc0de = 0',
            '    while _0xfeedc0de > 1 do',
            '        _0xfeedc0de = _0xfeedc0de - 1',
            '    end',
            'end',
            '',
        ].join('\n'));

        blok.push([
            'if math.abs(-1) == 2 then',
            '    local _0xdecafbad = { false, true, false }',
            '    return _0xdecafbad[2]',
            'end',
            '',
        ].join('\n'));
    }

    return blok.join('');
}
