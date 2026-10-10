<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Smart Survey') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepteal: '#008080',
                        brandbg: '#94918b'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #94918b; }
        .glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .btn-teal {
            background: #008080;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-teal:hover {
            background: #006666;
            transform: translateY(-2px);
        }
        .nav-link {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1a202c;
            transition: all 0.2s ease;
        }
        .nav-link:hover { color: #008080; }
        .nav-link.active { color: #008080; }
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.875rem;
            border-radius: 0.875rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .admin-nav-item:hover {
            background: rgba(255,255,255,0.5);
            color: #008080;
        }
        .admin-nav-item.active {
            background: #008080;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(0,128,128,0.3);
        }
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.6rem;
            font-weight: 900;
            text-transform: uppercase;
            transition: all 0.2s;
        }
    </style>
</head>
<body class="antialiased font-sans text-slate-900 min-h-screen">

    <!-- Transparent Glassmorphic Navbar -->
    <nav class="sticky top-0 z-50 w-full px-8 py-3 glass border-b border-white/20">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <!-- Left: Logo & Tagline -->
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="p-2 bg-deepteal rounded-lg shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.07 4.93l-1.41 1.41 1.41 1.41-1.41 1.41-1.41-1.41-1.41 1.41 1.41 1.41-1.41 1.41-1.41-1.41-1.41 1.41 1.41 1.41-1.41 1.41-2.83-2.83z"></path></svg>
                </div>
                <div>
                    <span class="text-slate-900 font-extrabold text-xl tracking-tighter block leading-tight">Smart Survey</span>
                    <span class="text-white font-bold text-[9px] tracking-widest uppercase opacity-80">Precision First</span>
                </div>
            </a>

            <!-- Center: Navigation -->
            <div class="hidden md:flex items-center space-x-10">
                @guest
                    <a href="{{ route('home') }}" class="nav-link {{ request()->is('/') ? 'active underline' : '' }}">Home</a>
                    <a href="#services" class="nav-link">Services</a>
                    <a href="#about" class="nav-link">About Us</a>
                    <a href="#contact" class="nav-link">Contact</a>
                @else
                    <a href="{{ route('home') }}" class="nav-link {{ request()->is('/') ? 'active underline' : '' }}">Home</a>
                    <a href="{{ route('survey.create') }}" class="nav-link {{ request()->is('survey/create') ? 'active underline' : '' }}">New Survey</a>
                    <a href="{{ route('survey.index') }}" class="nav-link {{ request()->is('survey*') && !request()->is('survey/create') ? 'active underline' : '' }}">Tracking</a>
                    @if(auth()->user()->role === 'client' && auth()->user()->surveys()->where('status', 'completed')->exists())
                        <a href="{{ route('feedback.index') }}" class="nav-link {{ request()->is('feedback*') ? 'active underline' : '' }}">Feedback</a>
                    @endif
                @endguest
            </div>

            <!-- Right: User Actions -->
            <div class="flex items-center gap-4">
                @auth
                    <div class="text-right hidden sm:block">
                        <div class="text-[10px] font-black text-white/50 uppercase tracking-widest leading-none mb-1">Authenticated</div>
                        <div class="text-xs font-bold text-slate-900">{{ auth()->user()->name }}</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="p-2 hover:bg-white/20 rounded-full transition text-slate-800" title="Logout">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-teal px-6 py-2 rounded-xl text-xs font-bold uppercase tracking-widest shadow-md">Sign In</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto px-6 py-12 min-h-[75vh]">
        @if(session('success'))
            <div class="bg-white/90 border-l-4 border-deepteal text-slate-900 p-5 rounded-2xl mb-12 shadow-xl flex items-center gap-4 animate-bounce">
                <div class="bg-deepteal p-1 rounded-full"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg></div>
                <span class="font-bold tracking-tight">{{ session('success') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Global Footer -->
    <footer class="max-w-7xl mx-auto px-6 py-12 border-t border-white/10 text-center text-white/40 text-[9px] tracking-widest uppercase font-black">
        &copy; {{ date('Y') }} Smart Survey Systems &bull; Precision & Engineering Excellence
    </footer>

    <!-- Global Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
