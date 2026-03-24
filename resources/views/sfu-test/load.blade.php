<!DOCTYPE html>
<html class="dark" lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="robots" content="noindex"/>
    <title>SFU_LOAD_TESTER | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container-low": "#131b2e",
                        "surface-container": "#171f33",
                        "on-error": "#690005",
                        "tertiary": "#ffb3ad",
                        "secondary": "#4edea3",
                        "on-tertiary": "#68000a",
                        "primary-container": "#00102c",
                        "surface-container-highest": "#2d3449",
                        "secondary-fixed": "#6ffbbe",
                        "outline-variant": "#45474b",
                        "error-container": "#93000a",
                        "error": "#ffb4ab",
                        "on-surface-variant": "#c6c6cb",
                        "surface-tint": "#adc7ff",
                        "inverse-primary": "#005bc0",
                        "outline": "#8f9095",
                        "on-primary": "#002e68",
                        "background": "#0b1326",
                        "on-primary-fixed": "#001a41",
                        "surface-variant": "#2d3449",
                        "on-secondary-fixed": "#002113",
                        "inverse-on-surface": "#283044",
                        "on-background": "#dae2fd",
                        "on-secondary": "#003824",
                        "surface-bright": "#31394d",
                        "on-surface": "#dae2fd",
                        "on-error-container": "#ffdad6",
                        "tertiary-container": "#2c0002",
                        "surface-dim": "#0b1326",
                        "primary": "#adc7ff",
                        "on-secondary-container": "#00311f",
                        "on-primary-container": "#0078f9",
                        "secondary-container": "#00a572",
                        "surface-container-lowest": "#060e20",
                        "surface-container-high": "#222a3d",
                        "on-primary-fixed-variant": "#004493",
                        "surface": "#0b1326",
                        "primary-fixed-dim": "#adc7ff",
                        "inverse-surface": "#dae2fd",
                        "on-tertiary-fixed": "#410004"
                    },
                    fontFamily: {
                        "headline": ["Space Grotesk"],
                        "body": ["Inter"],
                        "label": ["Space Grotesk"],
                        "mono": ["JetBrains Mono", "ui-monospace", "monospace"]
                    },
                    borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #0b1326; }
        ::-webkit-scrollbar-thumb { background: #222a3d; border-radius: 2px; }
        button { cursor: pointer; transition: opacity 0.12s; }
        button:not(:disabled):hover { opacity: 0.85; }
    </style>
</head>
<body class="bg-background text-on-surface font-body selection:bg-primary/30 min-h-screen flex flex-col">

    <!-- ── TopAppBar ──────────────────────────────────────────────────── -->
    <header class="bg-background sticky top-0 z-50 flex justify-between items-center w-full px-4 md:px-6 h-14 border-b border-outline-variant/10">
        <div class="flex items-center gap-4 min-w-0">
            <span class="text-lg md:text-xl font-bold text-primary tracking-tighter font-headline whitespace-nowrap">SFU_LOAD_TESTER</span>
            <div class="h-4 w-[1px] bg-outline-variant/30 hidden sm:block"></div>
            <span class="hidden sm:inline bg-surface-container-highest px-2 py-0.5 rounded text-[10px] font-mono text-primary border border-outline-variant/20 tracking-widest">{{ $roomId }}</span>
        </div>
        <div class="flex items-center gap-3 md:gap-4 shrink-0">
            <div id="statusBadge" class="flex items-center gap-2 bg-tertiary/10 px-3 py-1 rounded-sm border border-tertiary/20">
                <span id="statusDot" class="w-2 h-2 rounded-full bg-tertiary animate-pulse"></span>
                <span id="statusText" class="text-tertiary text-[10px] font-bold tracking-widest uppercase">Iniciando…</span>
            </div>
            <div class="hidden md:flex gap-2">
                <button class="text-primary hover:bg-surface-container-high transition-colors p-1.5 rounded-sm" title="Stats">
                    <span class="material-symbols-outlined text-lg">monitor_heart</span>
                </button>
                <button class="text-primary hover:bg-surface-container-high transition-colors p-1.5 rounded-sm" title="Logs" onclick="document.getElementById('logSection').scrollIntoView({behavior:'smooth'})">
                    <span class="material-symbols-outlined text-lg">terminal</span>
                </button>
            </div>
        </div>
    </header>

    <!-- ── Main content ───────────────────────────────────────────────── -->
    <main class="flex-1 flex flex-col p-3 md:p-4 gap-3 md:gap-4 overflow-hidden">

        <!-- Status Cards Row -->
        <section class="grid grid-cols-2 md:grid-cols-5 gap-2 md:gap-3">
            <div class="bg-surface-container-low p-3 flex flex-col gap-1 border-l-2 border-primary">
                <span class="text-on-surface-variant text-[10px] uppercase tracking-tighter font-label">Meu ID</span>
                <span id="infoUserId" class="text-primary font-mono text-[11px] sm:text-sm font-bold break-all leading-tight">—</span>
            </div>
            <div class="bg-surface-container-low p-3 flex flex-col gap-1 border-l-2 border-outline-variant">
                <span class="text-on-surface-variant text-[10px] uppercase tracking-tighter font-label">Participantes</span>
                <span id="infoParticipants" class="text-on-surface font-mono text-sm font-bold">0</span>
            </div>
            <div class="bg-surface-container-low p-3 flex flex-col gap-1 border-l-2 border-secondary">
                <span class="text-on-surface-variant text-[10px] uppercase tracking-tighter font-label">Câmera</span>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant/40 text-xs" style="font-variation-settings: 'FILL' 1;">videocam_off</span>
                    <span id="infoCam" class="text-on-surface-variant/50 font-mono text-sm font-bold">—</span>
                </div>
            </div>
            <div class="bg-surface-container-low p-3 flex flex-col gap-1 border-l-2 border-secondary">
                <span class="text-on-surface-variant text-[10px] uppercase tracking-tighter font-label">Microfone</span>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant/40 text-xs" style="font-variation-settings: 'FILL' 1;">mic_off</span>
                    <span id="infoMic" class="text-on-surface-variant/50 font-mono text-sm font-bold">—</span>
                </div>
            </div>
            <div class="bg-surface-container-low p-3 flex flex-col gap-1 border-l-2 border-secondary col-span-2 md:col-span-1">
                <span class="text-on-surface-variant text-[10px] uppercase tracking-tighter font-label">Conexão</span>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant/40 text-xs" style="font-variation-settings: 'FILL' 1;">bolt</span>
                    <span id="infoConn" class="text-on-surface-variant/50 font-mono text-sm font-bold">—</span>
                </div>
            </div>
        </section>

        <!-- Main Video Area: Asymmetric Layout -->
        <section class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-3 md:gap-4 min-h-0">

            <!-- Local Preview -->
            <div class="lg:col-span-7 flex flex-col gap-2 min-h-0">
                <div class="flex justify-between items-end">
                    <h2 class="font-headline uppercase text-xs tracking-widest text-primary">Local_Source_Preview</h2>
                    <span id="localRecTimer" class="font-mono text-[10px] text-on-surface-variant">REC: --:--:--</span>
                </div>
                <div class="relative flex-1 bg-surface-container-highest overflow-hidden min-h-[200px] sm:min-h-[280px]">
                    <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                    <div class="absolute inset-0 bg-gradient-to-t from-surface/80 to-transparent pointer-events-none"></div>
                    <!-- Overlay Telemetry -->
                    <div class="absolute top-3 left-3 flex flex-col gap-1">
                        <div class="bg-black/40 backdrop-blur-md px-2 py-1 flex items-center gap-2 border border-white/10">
                            <span id="localLiveDot" class="w-1.5 h-1.5 rounded-full bg-on-surface-variant/30"></span>
                            <span id="localLiveLabel" class="font-mono text-[10px] font-bold text-on-surface-variant/50 uppercase tracking-tighter">Aguardando…</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remote Peers Grid -->
            <div class="lg:col-span-5 flex flex-col gap-2 min-h-0">
                <div class="flex justify-between items-end">
                    <h2 class="font-headline uppercase text-xs tracking-widest text-on-surface-variant">Remote_Peers_Cluster</h2>
                    <span id="remotePeerCount" class="font-mono text-[10px] text-secondary">PEERS: 0</span>
                </div>
                <div id="remoteVideos" class="flex-1 overflow-y-auto pr-1 grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-3 gap-2 auto-rows-min content-start min-h-[120px]">
                    <!-- JS will populate this -->
                </div>
            </div>
        </section>

        <!-- Stats Block -->
        <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 md:gap-3">
            <div id="statsContent" class="contents">
                <!-- JS will populate stat cards here, or show default -->
            </div>
            <div id="statsDefault" class="col-span-full bg-surface-container-low p-4 flex items-center justify-center">
                <span class="text-[11px] text-on-surface-variant/40 font-mono">Aguardando mídia…</span>
            </div>
        </section>

        <!-- Share Box -->
        <section class="bg-surface-container-low p-3 md:p-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 min-w-0">
                <span class="text-[10px] text-on-surface-variant uppercase font-label tracking-widest block mb-1">Abrir em outra aba</span>
                <div id="pageUrl" class="font-mono text-[11px] sm:text-xs text-primary break-all bg-surface-container-lowest px-3 py-2 rounded select-all"></div>
            </div>
            <button id="btnCopy" class="shrink-0 bg-primary text-on-primary text-[10px] font-black px-4 py-2 rounded hover:bg-primary-fixed-dim transition-colors tracking-widest uppercase">
                Copiar URL
            </button>
        </section>

        <!-- Terminal Log -->
        <section id="logSection" class="bg-surface-container-lowest border-t border-outline-variant/10 flex flex-col min-h-0" style="height: clamp(120px, 20vh, 200px);">
            <div class="flex items-center justify-between px-3 py-1.5 bg-surface-container shrink-0">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-xs text-on-surface-variant">terminal</span>
                    <span class="text-[10px] font-headline uppercase tracking-widest text-on-surface-variant">System_Output_Stream</span>
                </div>
                <span class="font-mono text-[8px] text-on-surface-variant/40 hidden sm:inline">VER: 4.2.0-STABLE</span>
            </div>
            <div id="logContainer" class="flex-1 p-3 overflow-y-auto font-mono text-[10px] space-y-1 text-on-surface-variant/60"></div>
        </section>
    </main>

    <!-- ── Footer ─────────────────────────────────────────────────────── -->
    <footer class="h-8 bg-surface-container-highest flex items-center justify-between px-4 md:px-6 border-t border-outline-variant/10 shrink-0">
        <div class="flex items-center gap-4 md:gap-6">
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase font-label text-on-surface-variant tracking-wider">Sala:</span>
                <span class="font-mono text-[10px] text-primary">{{ $roomId }}</span>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="text-[10px] uppercase font-label text-on-surface-variant tracking-wider">Health:</span>
                <span id="footerHealth" class="font-mono text-[10px] text-on-surface-variant/50">—</span>
            </div>
        </div>
        <div class="flex items-center gap-3 md:gap-4">
            <div class="hidden sm:flex items-center gap-1">
                <span class="material-symbols-outlined text-[10px] text-on-surface-variant">timer</span>
                <span id="footerUptime" class="font-mono text-[10px] text-on-surface-variant">UPTIME: --:--:--</span>
            </div>
            <div class="bg-error/10 px-2 py-0.5 border border-error/30 rounded-sm">
                <span class="text-[9px] font-bold text-error uppercase tracking-tighter">Emergency Kill</span>
            </div>
        </div>
    </footer>

    <script>
        window.__SFU_LOAD_CONFIG__ = @json($sfuTestConfig);

        // Fill share URL
        var pageUrlEl = document.getElementById('pageUrl');
        if (pageUrlEl) pageUrlEl.textContent = window.location.href;

        // Copy button
        document.getElementById('btnCopy')?.addEventListener('click', function() {
            navigator.clipboard?.writeText(window.location.href).then(function() {
                var btn = document.getElementById('btnCopy');
                if (btn) { btn.textContent = 'COPIADO!'; setTimeout(function() { btn.textContent = 'COPIAR URL'; }, 2000); }
            }).catch(function() {});
        });
    </script>
    @vite(['resources/js/sfu-load-test-app.js'])
</body>
</html>
