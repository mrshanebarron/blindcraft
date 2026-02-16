<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BlindCraft CPQ') — Custom Window Covering Pricing Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['DM Serif Display', 'Georgia', 'serif'],
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f4f8',
                            100: '#d9e2ec',
                            200: '#bcccdc',
                            300: '#9fb3c8',
                            400: '#829ab1',
                            500: '#627d98',
                            600: '#486581',
                            700: '#334e68',
                            800: '#243b53',
                            900: '#102a43',
                            950: '#0a1929',
                        },
                        accent: {
                            400: '#f0b429',
                            500: '#de911d',
                            600: '#cb6e17',
                        },
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-serif { font-family: 'DM Serif Display', Georgia, serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /* Baroque touches */
        .card-elevated {
            background: white;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.04);
            border-radius: 0.5rem;
        }
        .card-elevated:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08), 0 12px 32px rgba(0,0,0,0.06);
        }
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #334e68, #627d98, #f0b429);
        }
        .pricing-grid-cell {
            transition: all 0.15s ease;
        }
        .pricing-grid-cell:hover {
            background: #f0f4f8;
            transform: scale(1.02);
        }
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #f0b429;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }
        .breakdown-row {
            border-left: 3px solid transparent;
            transition: border-color 0.2s;
        }
        .breakdown-row:hover {
            border-left-color: #f0b429;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-brand-50 text-brand-900 min-h-screen">
    {{-- Navigation --}}
    <nav class="bg-brand-950 text-white border-b border-brand-800">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <div class="w-8 h-8 bg-accent-500 rounded flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <span class="font-serif text-xl tracking-tight group-hover:text-accent-400 transition-colors">BlindCraft</span>
                <span class="text-xs font-mono text-brand-400 bg-brand-800 px-2 py-0.5 rounded">CPQ</span>
            </a>
            <div class="flex items-center gap-8">
                <a href="{{ route('configurator') }}" class="nav-link text-sm font-medium text-brand-200 hover:text-white transition-colors {{ request()->routeIs('configurator') ? 'active text-white' : '' }}">Configure</a>
                <a href="{{ route('quotes.index') }}" class="nav-link text-sm font-medium text-brand-200 hover:text-white transition-colors {{ request()->routeIs('quotes.*') ? 'active text-white' : '' }}">Quotes</a>
                <a href="{{ route('admin.index') }}" class="nav-link text-sm font-medium text-brand-200 hover:text-white transition-colors {{ request()->routeIs('admin.*') ? 'active text-white' : '' }}">Admin</a>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-6 mt-4">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-7xl mx-auto px-6 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Content --}}
    <main class="max-w-7xl mx-auto px-6 py-8">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-brand-200 bg-white mt-12">
        <div class="max-w-7xl mx-auto px-6 py-6 flex items-center justify-between text-sm text-brand-400">
            <span class="font-mono text-xs">BlindCraft CPQ v1.0 &mdash; Demo</span>
            <span>Built by <a href="https://sbarron.com" class="text-brand-600 hover:text-accent-500 transition-colors font-medium">sbarron.com</a></span>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
