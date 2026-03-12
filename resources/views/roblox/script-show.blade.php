<x-layouts::app :title="$script['label']">
    <style>
        .script-preview-shell {
            --preview-accent: #8df27a;
            --preview-accent-2: #63f4ff;
            --preview-line: rgba(141, 242, 122, 0.14);
            --preview-panel: rgba(8, 20, 36, 0.82);
            --preview-muted: #8ea7c8;
            --preview-text: #eef6ff;
            padding: 1rem;
            color: var(--preview-text);
        }

        .script-preview-shell * {
            box-sizing: border-box;
        }

        .script-preview-hero,
        .script-preview-code {
            position: relative;
            overflow: hidden;
            border-radius: 1.8rem;
            border: 1px solid var(--preview-line);
            background: linear-gradient(180deg, var(--preview-panel), rgba(5, 12, 24, 0.95));
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        }

        .script-preview-hero::after,
        .script-preview-code::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at top right, rgba(99, 244, 255, 0.14), transparent 30%),
                radial-gradient(circle at 0% 0%, rgba(141, 242, 122, 0.12), transparent 24%);
        }

        .script-preview-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem;
        }

        .script-preview-label {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            border-radius: 999px;
            border: 1px solid rgba(141, 242, 122, 0.18);
            padding: .46rem .82rem;
            font: 700 .69rem/1 "JetBrains Mono", ui-monospace, monospace;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--preview-accent);
            background: rgba(255,255,255,.04);
        }

        .script-preview-label::before {
            content: "";
            width: .48rem;
            height: .48rem;
            border-radius: 999px;
            background: var(--preview-accent-2);
            box-shadow: 0 0 14px rgba(99, 244, 255, .7);
        }

        .script-preview-title {
            margin: .95rem 0 0;
            font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif;
            font-size: clamp(2.1rem, 4vw, 3.45rem);
            line-height: .95;
            letter-spacing: -.03em;
        }

        .script-preview-copy {
            margin: .9rem 0 0;
            max-width: 52rem;
            color: var(--preview-muted);
            line-height: 1.85;
        }

        .script-preview-download {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .82rem 1.08rem;
            background: linear-gradient(135deg, var(--preview-accent), var(--preview-accent-2));
            color: #04111c;
            font-weight: 800;
            text-decoration: none;
        }

        .script-preview-code {
            margin-top: 1.1rem;
            padding: 0;
        }

        .script-preview-code pre {
            margin: 0;
            overflow-x: auto;
            padding: 1.2rem;
            color: #f4fbff;
            font: 400 .92rem/1.85 "JetBrains Mono", ui-monospace, monospace;
        }

        @media (max-width: 760px) {
            .script-preview-shell {
                padding: .2rem;
            }
        }
    </style>

    <div class="script-preview-shell">
        <section class="script-preview-hero">
            <div>
                <span class="script-preview-label">{{ $script['filename'] }}</span>
                <h1 class="script-preview-title">{{ $script['label'] }}</h1>
                <p class="script-preview-copy">{{ $script['description'] }}</p>
            </div>

            <a href="{{ route('roblox.scripts.download', $script['slug']) }}" class="script-preview-download">
                Download File
            </a>
        </section>

        <section class="script-preview-code">
            <pre><code>{{ $content }}</code></pre>
        </section>
    </div>
</x-layouts::app>
