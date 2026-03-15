export function kompilasiRingkasUntukAudit(kode) {
    return {
        lines: kode.split(/\r?\n/).length,
        functions: hitung(/\bfunction\b/g, kode),
        loops: hitung(/\b(for|while|repeat)\b/g, kode),
        conditionals: hitung(/\b(if|elseif)\b/g, kode),
        calls: hitung(/[A-Za-z_][A-Za-z0-9_]*\s*\(/g, kode),
    };
}

function hitung(regex, kode) {
    const cocok = kode.match(regex);
    return cocok ? cocok.length : 0;
}
