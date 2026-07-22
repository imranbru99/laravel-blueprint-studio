<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $brand['name'] ?? 'Laravel Blueprint Studio' }}</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('bps-theme') || 'dark';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        ink: {
                            950: '#0a0f0d',
                            900: '#0f1612',
                            800: '#162019',
                            700: '#1e2b23',
                            600: '#2a3a30',
                        },
                        accent: {
                            DEFAULT: '#3d9b6e',
                            soft: '#2f8a5f',
                            muted: '#2d7352',
                        },
                    },
                    animation: {
                        'slide-up': 'slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                    },
                    keyframes: {
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        [data-theme="dark"] {
            --bps-bg: #0a0f0d;
            --bps-fg: #e8efe9;
            --bps-muted: #7a9a84;
            --bps-panel: #162019;
            --bps-border: rgba(255, 255, 255, 0.1);
            --bps-header: rgba(15, 22, 18, 0.85);
            --bps-input: #0f1612;
            --bps-hover: rgba(255, 255, 255, 0.04);
            --bps-glow-a: rgba(61, 155, 110, 0.16);
            --bps-glow-b: rgba(90, 125, 101, 0.1);
            --bps-grid: rgba(255, 255, 255, 0.035);
            --bps-toast: #162019;
            --bps-title: #ffffff;
            --bps-soft: #d0ddd3;
            --bps-thumb: rgba(168, 191, 176, 0.3);
            --bps-accent-text: #5cb88a;
            --bps-shadow: 0 18px 40px -24px rgba(0,0,0,0.55);
        }

        [data-theme="light"] {
            --bps-bg: #eef3ef;
            --bps-fg: #1a2a20;
            --bps-muted: #4d6b57;
            --bps-panel: #ffffff;
            --bps-border: rgba(26, 42, 32, 0.12);
            --bps-header: rgba(255, 255, 255, 0.92);
            --bps-input: #ffffff;
            --bps-hover: rgba(26, 42, 32, 0.04);
            --bps-glow-a: rgba(61, 155, 110, 0.12);
            --bps-glow-b: rgba(45, 115, 82, 0.08);
            --bps-grid: rgba(26, 42, 32, 0.05);
            --bps-toast: #ffffff;
            --bps-title: #0f1612;
            --bps-soft: #2a3a30;
            --bps-thumb: rgba(90, 125, 101, 0.35);
            --bps-accent-text: #2d7352;
            --bps-shadow: 0 16px 36px -24px rgba(15, 22, 18, 0.22);
        }

        body {
            color: var(--bps-fg);
            background:
                radial-gradient(ellipse 80% 50% at 20% -10%, var(--bps-glow-a), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 10%, var(--bps-glow-b), transparent 50%),
                var(--bps-bg);
        }
        .grid-overlay {
            background-image:
                linear-gradient(var(--bps-grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--bps-grid) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, black, transparent);
        }
        .bps-header { background: var(--bps-header); border-color: var(--bps-border); }
        .bps-panel {
            background: var(--bps-panel) !important;
            border-color: var(--bps-border) !important;
            box-shadow: var(--bps-shadow);
            color: var(--bps-fg);
        }
        .bps-input {
            background: var(--bps-input) !important;
            border-color: var(--bps-border) !important;
            color: var(--bps-title) !important;
        }
        .bps-input::placeholder { color: var(--bps-muted); opacity: 0.7; }
        .bps-title { color: var(--bps-title) !important; }
        .bps-muted { color: var(--bps-muted) !important; }
        .bps-soft { color: var(--bps-soft) !important; }
        .bps-border { border-color: var(--bps-border) !important; }
        .bps-hover:hover { background: var(--bps-hover) !important; }
        .text-accent-soft { color: var(--bps-accent-text) !important; }
        .bps-toast-ok {
            background: var(--bps-toast);
            border-color: rgba(61, 155, 110, 0.4);
            color: var(--bps-fg);
            box-shadow: var(--bps-shadow);
        }
        .scroll-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scroll-thin::-webkit-scrollbar-thumb { background: var(--bps-thumb); border-radius: 999px; }
        .field-locked { opacity: 0.55; }
        input:focus, select:focus, textarea:focus, button:focus-visible { outline: none; }

        [data-theme="light"] .bg-accent\/15 { background-color: rgba(61, 155, 110, 0.12) !important; }
        [data-theme="light"] .bg-accent\/10 { background-color: rgba(61, 155, 110, 0.1) !important; }
    </style>
</head>
<body class="h-full font-sans antialiased">
    <div class="pointer-events-none fixed inset-0 grid-overlay" aria-hidden="true"></div>
    @yield('body')
    <script>
        window.BlueprintStudio = {
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            routes: {
                bootstrap: @json(route('blueprint-studio.api.bootstrap')),
                crud: @json(route('blueprint-studio.api.crud.generate')),
                crudBatch: @json(route('blueprint-studio.api.crud.batch')),
                draftParse: @json(route('blueprint-studio.api.draft.parse')),
                draftImport: @json(route('blueprint-studio.api.draft.import')),
                history: @json(route('blueprint-studio.api.history')),
                historyClear: @json(route('blueprint-studio.api.history.clear')),
            },
            async request(url, options = {}) {
                const res = await fetch(url, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    ...options,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const msg = data.message
                        || (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                        || 'Request failed';
                    throw new Error(msg);
                }
                return data;
            },
        };
    </script>
    @stack('scripts')
</body>
</html>
