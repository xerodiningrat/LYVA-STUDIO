<style>
    @import url('https://fonts.bunny.net/css?family=space-grotesk:500,700|jetbrains-mono:400,600,700');

    :root {
        color-scheme: dark;
        --studio-bg: #04101f;
        --studio-panel: rgba(7, 18, 36, 0.8);
        --studio-panel-strong: rgba(4, 12, 26, 0.94);
        --studio-line: rgba(148, 206, 255, 0.14);
        --studio-line-strong: rgba(148, 206, 255, 0.26);
        --studio-text: #eef6ff;
        --studio-muted: #8fa7c7;
        --studio-accent: #7bdfff;
        --studio-accent-2: #7ff7c4;
        --studio-accent-3: #ffcf7d;
        --studio-danger: #ff8f9d;
        --studio-shadow: 0 28px 80px rgba(0, 0, 0, 0.34);
        --studio-display: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif;
        --studio-mono: "JetBrains Mono", ui-monospace, monospace;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        min-height: 100%;
    }

    body {
        margin: 0;
        background:
            radial-gradient(circle at 12% 0%, color-mix(in srgb, var(--studio-accent) 18%, transparent), transparent 28%),
            radial-gradient(circle at 88% 10%, color-mix(in srgb, var(--studio-accent-2) 16%, transparent), transparent 26%),
            linear-gradient(180deg, #040b18 0%, var(--studio-bg) 54%, #030a15 100%);
        color: var(--studio-text);
        font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
    }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 44px 44px;
        opacity: 0.12;
        mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.96), transparent 97%);
    }

    a {
        color: inherit;
        text-decoration: none;
    }

    .studio-shell {
        position: relative;
        max-width: 1440px;
        margin: 0 auto;
        padding: clamp(1rem, 3vw, 1.65rem);
    }

    .studio-shell > * {
        position: relative;
        z-index: 1;
    }

    .studio-glow-a,
    .studio-glow-b,
    .studio-glow-c {
        position: absolute;
        border-radius: 999px;
        filter: blur(20px);
        pointer-events: none;
        z-index: 0;
    }

    .studio-glow-a {
        top: -3rem;
        left: -2rem;
        width: 15rem;
        height: 15rem;
        background: radial-gradient(circle, color-mix(in srgb, var(--studio-accent) 24%, transparent), transparent 70%);
    }

    .studio-glow-b {
        top: 10rem;
        right: -4rem;
        width: 17rem;
        height: 17rem;
        background: radial-gradient(circle, color-mix(in srgb, var(--studio-accent-2) 18%, transparent), transparent 70%);
    }

    .studio-glow-c {
        bottom: 8rem;
        left: 28%;
        width: 13rem;
        height: 13rem;
        background: radial-gradient(circle, color-mix(in srgb, var(--studio-accent-3) 14%, transparent), transparent 72%);
    }

    .studio-topbar,
    .studio-hero,
    .studio-panel,
    .studio-card,
    .studio-notice {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--studio-line);
        background: linear-gradient(180deg, var(--studio-panel), rgba(5, 13, 27, 0.94));
        box-shadow: var(--studio-shadow);
        backdrop-filter: blur(18px);
    }

    .studio-topbar::after,
    .studio-hero::after,
    .studio-panel::after,
    .studio-card::after,
    .studio-notice::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            radial-gradient(circle at var(--mx, 75%) var(--my, 0%), color-mix(in srgb, var(--studio-accent) 18%, transparent), transparent 28%),
            radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--studio-accent-2) 12%, transparent), transparent 24%);
    }

    .studio-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-radius: 1.55rem;
    }

    .studio-brand {
        display: flex;
        align-items: center;
        gap: 0.95rem;
    }

    .studio-brand-mark {
        width: 3.15rem;
        height: 3.15rem;
        display: grid;
        place-items: center;
        border-radius: 1rem;
        background: linear-gradient(135deg, var(--studio-accent), var(--studio-accent-2));
        color: #05101f;
        font: 800 0.94rem/1 var(--studio-display);
        text-transform: uppercase;
        letter-spacing: 0.16em;
        box-shadow: 0 12px 30px color-mix(in srgb, var(--studio-accent) 24%, transparent);
    }

    .studio-brand h1,
    .studio-hero h2,
    .studio-panel h3,
    .studio-card h3,
    .studio-table-title {
        margin: 0;
        font-family: var(--studio-display);
        letter-spacing: -0.03em;
    }

    .studio-brand h1 {
        font-size: clamp(1.1rem, 2vw, 1.35rem);
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .studio-brand p,
    .studio-hero p,
    .studio-copy,
    .studio-muted,
    .studio-panel p,
    .studio-card p {
        color: var(--studio-muted);
        line-height: 1.78;
    }

    .studio-brand p {
        margin: 0.18rem 0 0;
        font-size: 0.84rem;
    }

    .studio-nav {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.7rem;
    }

    .studio-nav a,
    .studio-button,
    .studio-button-ghost,
    .studio-button-danger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        font-weight: 700;
        transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }

    .studio-nav a,
    .studio-button-ghost {
        padding: 0.72rem 1rem;
        border: 1px solid color-mix(in srgb, var(--studio-accent) 16%, transparent);
        background: rgba(255, 255, 255, 0.04);
    }

    .studio-button,
    .studio-nav a.studio-nav-active {
        padding: 0.76rem 1.08rem;
        border: 0;
        color: #03101b;
        background: linear-gradient(135deg, var(--studio-accent), color-mix(in srgb, var(--studio-accent-2) 74%, white 0%));
        box-shadow: 0 12px 28px color-mix(in srgb, var(--studio-accent) 20%, transparent);
    }

    .studio-button-danger {
        padding: 0.76rem 1.08rem;
        color: var(--studio-danger);
        border: 1px solid color-mix(in srgb, var(--studio-danger) 28%, transparent);
        background: color-mix(in srgb, var(--studio-danger) 10%, transparent);
    }

    .studio-nav a:hover,
    .studio-button:hover,
    .studio-button-ghost:hover,
    .studio-button-danger:hover {
        transform: translateY(-2px);
    }

    .studio-hero {
        margin-top: 1.15rem;
        padding: clamp(1.2rem, 3vw, 1.55rem);
        border-radius: 2rem;
    }

    .studio-grid,
    .studio-hero-grid,
    .studio-panel-grid,
    .studio-form-grid,
    .studio-stats-grid,
    .studio-list-grid,
    .studio-chip-grid {
        display: grid;
        gap: 1rem;
    }

    .studio-hero-grid {
        grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.92fr);
        align-items: start;
        gap: 1.2rem;
    }

    .studio-panel-grid {
        margin-top: 1.2rem;
        grid-template-columns: minmax(340px, 0.92fr) minmax(0, 1.08fr);
        align-items: start;
    }

    .studio-form-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .studio-stats-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-top: 1.15rem;
    }

    .studio-list-grid {
        margin-top: 1rem;
    }

    .studio-chip-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .studio-kicker,
    .studio-label,
    .studio-pill,
    .studio-chip,
    .studio-note {
        font-family: var(--studio-mono);
        text-transform: uppercase;
        letter-spacing: 0.15em;
    }

    .studio-kicker,
    .studio-label {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        border-radius: 999px;
        padding: 0.48rem 0.82rem;
        border: 1px solid color-mix(in srgb, var(--studio-accent) 18%, transparent);
        background: rgba(255, 255, 255, 0.04);
        color: var(--studio-accent);
        font-size: 0.69rem;
        font-weight: 700;
    }

    .studio-kicker::before,
    .studio-label::before {
        content: "";
        width: 0.48rem;
        height: 0.48rem;
        border-radius: 999px;
        background: var(--studio-accent-2);
        box-shadow: 0 0 16px color-mix(in srgb, var(--studio-accent-2) 86%, transparent);
    }

    .studio-pill,
    .studio-chip,
    .studio-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid color-mix(in srgb, var(--studio-accent) 14%, transparent);
        background: rgba(255, 255, 255, 0.05);
        color: #dfeaff;
        padding: 0.48rem 0.82rem;
        font-size: 0.68rem;
        font-weight: 700;
    }

    .studio-badge-ok {
        color: var(--studio-accent-2);
        background: color-mix(in srgb, var(--studio-accent-2) 12%, transparent);
        border-color: color-mix(in srgb, var(--studio-accent-2) 24%, transparent);
    }

    .studio-badge-warn {
        color: var(--studio-accent-3);
        background: color-mix(in srgb, var(--studio-accent-3) 12%, transparent);
        border-color: color-mix(in srgb, var(--studio-accent-3) 24%, transparent);
    }

    .studio-badge-off {
        color: #dbe5fb;
        background: rgba(255, 255, 255, 0.06);
    }

    .studio-badge-danger {
        color: var(--studio-danger);
        background: color-mix(in srgb, var(--studio-danger) 12%, transparent);
        border-color: color-mix(in srgb, var(--studio-danger) 24%, transparent);
    }

    .studio-hero h2 {
        margin-top: 0.95rem;
        font-size: clamp(2.55rem, 5vw, 4.75rem);
        line-height: 0.92;
    }

    .studio-hero h2 span {
        display: block;
        background: linear-gradient(90deg, var(--studio-accent), #f3fbff 54%, var(--studio-accent-2));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .studio-hero p {
        margin-top: 0.95rem;
        max-width: 48rem;
    }

    .studio-panel,
    .studio-card {
        padding: 1.12rem;
        border-radius: 1.6rem;
    }

    .studio-card {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.02));
    }

    .studio-card[data-studio-hover],
    .studio-panel[data-studio-hover] {
        transition: transform 0.18s ease, border-color 0.18s ease;
    }

    .studio-card[data-studio-hover]:hover,
    .studio-panel[data-studio-hover]:hover {
        transform: translateY(-4px);
        border-color: var(--studio-line-strong);
    }

    .studio-panel-header,
    .studio-row-between {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .studio-panel-header {
        margin-bottom: 1rem;
    }

    .studio-stat {
        border-radius: 1.25rem;
        border: 1px solid var(--studio-line);
        background: rgba(255, 255, 255, 0.035);
        padding: 1rem;
    }

    .studio-stat strong {
        display: block;
        margin-top: 0.6rem;
        font-family: var(--studio-display);
        font-size: clamp(1.5rem, 3vw, 2rem);
        line-height: 1;
    }

    .studio-note {
        display: inline-flex;
        margin-top: 0.72rem;
        font-size: 0.62rem;
        color: #a8bfdc;
        font-weight: 700;
    }

    .studio-flow,
    .studio-actions,
    .studio-stack {
        display: grid;
        gap: 0.85rem;
    }

    .studio-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem;
        margin-top: 1rem;
    }

    .studio-stack {
        gap: 1rem;
    }

    .studio-field {
        display: grid;
        gap: 0.5rem;
    }

    .studio-field label {
        font-size: 0.92rem;
        font-weight: 700;
        color: #eef4ff;
    }

    .studio-field small,
    .studio-help {
        color: var(--studio-muted);
        font-size: 0.82rem;
        line-height: 1.65;
    }

    .studio-input,
    .studio-textarea,
    .studio-code,
    .studio-surface {
        width: 100%;
        border-radius: 1rem;
        border: 1px solid color-mix(in srgb, var(--studio-accent) 14%, transparent);
        background: rgba(255, 255, 255, 0.045);
        color: var(--studio-text);
        padding: 0.92rem 1rem;
        font: inherit;
    }

    .studio-input::placeholder,
    .studio-textarea::placeholder {
        color: #6f85a7;
    }

    .studio-textarea {
        min-height: 7.2rem;
        resize: vertical;
    }

    .studio-checkbox {
        display: flex;
        gap: 0.8rem;
        align-items: flex-start;
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid color-mix(in srgb, var(--studio-accent) 12%, transparent);
        background: rgba(255, 255, 255, 0.035);
    }

    .studio-checkbox input {
        width: auto;
        margin-top: 0.18rem;
        accent-color: var(--studio-accent);
    }

    .studio-code {
        font-family: var(--studio-mono);
        background: rgba(4, 9, 20, 0.96);
        white-space: pre-wrap;
        font-size: 0.82rem;
        line-height: 1.8;
    }

    .studio-api {
        word-break: break-word;
        font-family: var(--studio-mono);
        background: rgba(255, 255, 255, 0.05);
    }

    .studio-table-wrap {
        overflow-x: auto;
        border-radius: 1.25rem;
        border: 1px solid rgba(255, 255, 255, 0.06);
        background: rgba(255, 255, 255, 0.02);
    }

    .studio-table {
        width: 100%;
        border-collapse: collapse;
    }

    .studio-table th,
    .studio-table td {
        padding: 0.95rem 0.85rem;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        vertical-align: top;
    }

    .studio-table th {
        color: var(--studio-muted);
        font: 0.7rem/1 var(--studio-mono);
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .studio-table td {
        color: #eef4ff;
    }

    .studio-notice {
        margin-top: 1rem;
        padding: 0.95rem 1rem;
        border-radius: 1.2rem;
        background: color-mix(in srgb, var(--studio-accent-2) 10%, transparent);
    }

    .studio-empty {
        border-radius: 1.25rem;
        border: 1px dashed color-mix(in srgb, var(--studio-accent) 24%, transparent);
        background: rgba(255, 255, 255, 0.025);
        padding: 1.15rem;
    }

    .studio-inline-code {
        padding: 0.18rem 0.4rem;
        border-radius: 0.5rem;
        background: rgba(255, 255, 255, 0.08);
        font-family: var(--studio-mono);
        font-size: 0.8em;
    }

    @media (max-width: 1180px) {
        .studio-hero-grid,
        .studio-panel-grid,
        .studio-stats-grid {
            grid-template-columns: 1fr 1fr;
        }

        .studio-hero-grid,
        .studio-panel-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .studio-shell {
            padding: 0.9rem 0.85rem 1.15rem;
        }

        .studio-topbar,
        .studio-panel-header,
        .studio-row-between {
            flex-direction: column;
        }

        .studio-nav {
            width: 100%;
            justify-content: stretch;
        }

        .studio-nav a {
            flex: 1 1 calc(50% - 0.4rem);
        }

        .studio-brand {
            align-items: flex-start;
        }

        .studio-stats-grid,
        .studio-form-grid,
        .studio-chip-grid {
            grid-template-columns: 1fr;
        }

        .studio-hero h2 {
            font-size: clamp(2.3rem, 13vw, 3.35rem);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .studio-card[data-studio-hover],
        .studio-panel[data-studio-hover],
        .studio-nav a,
        .studio-button,
        .studio-button-ghost,
        .studio-button-danger {
            transition: none !important;
        }
    }
</style>
