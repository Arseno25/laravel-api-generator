<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    @php
        $localDocsStylesheet = public_path('vendor/api-magic/docs.css');
    @endphp
    @if (file_exists($localDocsStylesheet))
        <link rel="stylesheet" href="{{ asset('vendor/api-magic/docs.css') }}">
    @elseif ($tailwindCdn = config('api-magic.docs.assets.tailwind_cdn'))
        <script src="{{ $tailwindCdn }}"></script>
    @endif
    @if ($iconStylesheet = config('api-magic.docs.assets.icon_stylesheet'))
        <link rel="stylesheet" href="{{ $iconStylesheet }}">
    @endif
    @foreach (config('api-magic.docs.assets.stylesheets', []) as $stylesheet)
        <link rel="stylesheet" href="{{ $stylesheet }}">
    @endforeach
    @foreach (config('api-magic.docs.assets.scripts', []) as $script)
        <script src="{{ $script }}"></script>
    @endforeach
    @include('api-magic::partials.styles')
</head>
<body class="bg-slate-950 text-slate-300 antialiased selection:bg-indigo-500/30">
    @include('api-magic::partials.icon-sprite')

    {{-- Ambient Background --}}
    <div class="fixed inset-0 z-[-1] bg-slate-950">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-500/10 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-500/10 blur-[120px]"></div>
    </div>

    <div id="app" class="relative">
        {{-- Loading Spinner --}}
        <div id="loading" class="fixed inset-0 flex items-center justify-center bg-slate-950 z-50">
            <div class="text-center">
                <div class="w-12 h-12 border-[3px] border-slate-700 border-t-indigo-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-slate-400">Loading documentation...</p>
            </div>
        </div>

        {{-- Main Layout --}}
        <div id="main-content" class="hidden h-screen flex">
            {{-- Mobile Header --}}
            <div class="lg:hidden fixed top-0 left-0 right-0 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 px-4 py-3 flex items-center justify-between z-30">
                <button onclick="toggleMobileSidebar()" class="p-2 rounded-lg hover:bg-slate-800 transition-colors">
                    <i class="fas fa-bars text-slate-300"></i>
                </button>
                <h2 class="font-semibold text-sm truncate mx-2" id="mobile-title">API Documentation</h2>
                <button onclick="openAuthModal()" class="p-2 rounded-lg hover:bg-slate-800 transition-colors">
                    <i class="fas fa-key text-slate-300"></i>
                </button>
            </div>

            {{-- Mobile Sidebar Overlay --}}
            <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden sidebar-overlay" onclick="closeMobileSidebar()"></div>

            {{-- Sidebar --}}
            @include('api-magic::partials.sidebar')

            {{-- Main Content --}}
            @include('api-magic::partials.content')
        </div>

        {{-- Modals --}}
        @include('api-magic::partials.modals')
    </div>

    {{-- JavaScript Modules --}}
    @php
        $docsConfig = [
            'urls' => [
                'ui' => route('api.docs.ui'),
                'json' => route('api.docs.json'),
                'health' => route('api.docs.health'),
                'changelog' => route('api.docs.changelog'),
                'codeSnippet' => route('api.docs.code-snippet'),
                'oauthCallback' => route('api.docs.oauth2-callback'),
            ],
        ];
    @endphp
    <script>
        window.apiMagicDocsConfig = {{ \Illuminate\Support\Js::from($docsConfig) }};
    </script>
    <script>
    // ── App Core (state, init, utilities) ──
    @include('api-magic::scripts.app')

    // ── Endpoint Rendering (sidebar, detail, snippets) ──
    @include('api-magic::scripts.endpoint')

    // ── Request Handling (send, response display) ──
    @include('api-magic::scripts.request')

    // ── History & Chaining ──
    @include('api-magic::scripts.history')

    // ── Boot ──
    init();
    </script>
</body>
</html>
