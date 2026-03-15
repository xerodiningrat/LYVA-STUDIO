export function jalankanFlowFlatteningAman(kode, opsi = {}) {
    const level = normalisasiLevel(opsi.level);

    if (level === 'light') {
        const deadCode = bangunDeadCode('light');

        return {
            code: deadCode + kode,
            warnings: [
                'Layer 4 tetap memakai mode aman; profile light sekarang menambah satu lapis dead code tipis tanpa state-machine.',
            ],
            meta: {
                deadCodeInserted: 1,
                enabled: true,
            },
        };
    }

    const deadCode = bangunDeadCode(level);

    return {
        code: deadCode + kode,
        warnings: [
            level === 'max-safe'
                ? 'Layer 4 menambah dead code berlapis yang lebih rapat, tetap tanpa state-machine anti-analysis.'
                : 'Layer 4 menambah dead code aman berlapis, tanpa state-machine anti-analysis.',
        ],
        meta: {
            deadCodeInserted: level === 'max-safe' ? 5 : 3,
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
    const blok = [];

    blok.push([
        'local _0xdeadbeef = (math.random() > 2)',
        '    and (function() return nil end)()',
        '    or nil',
        'if _0xdeadbeef then',
        '    local _0xfakefeed = "\\116\\101\\115\\116"',
        '    _0xfakefeed = _0xfakefeed .. "\\000"',
        'end',
        '',
    ].join('\n'));

    if (level !== 'light') {
        blok.push([
            'do',
            '    local _0xlayera = { "\\097", "\\098", "\\099" }',
            '    if #_0xlayera == 99 and _0xlayera[1] == "\\122" then',
            '        _0xlayera[4] = table.concat(_0xlayera)',
            '    end',
            'end',
            '',
        ].join('\n'));

        blok.push([
            'do',
            '    local _0xlayerb = 0',
            '    repeat',
            '        _0xlayerb = _0xlayerb + 1',
            '    until _0xlayerb > 1',
            '    if _0xlayerb == 99 then',
            '        local _0xshadow = function() return "\\120" end',
            '        _0xshadow()',
            '    end',
            'end',
            '',
        ].join('\n'));
    }

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
            'do',
            '    local _0xmesh = { one = 1, two = 2 }',
            '    if _0xmesh.one == 7 and _0xmesh.two == 9 then',
            '        for _0xindex = 1, 3 do',
            '            _0xmesh[_0xindex] = _0xindex * 4',
            '        end',
            '    end',
            'end',
            '',
        ].join('\n'));

        blok.push([
            'do',
            '    local _0xouter = false',
            '    if _0xouter and (pcall(function() return nil end)) then',
            '        local _0xinner = { "\\121", "\\122" }',
            '        _0xinner[3] = table.concat(_0xinner, "")',
            '    end',
            'end',
            '',
        ].join('\n'));
    }

    return blok.join('');
}
