<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Nusantara Sentiment</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('layouts.partials.styles')
    @hasSection('css-vars')
        @yield('css-vars')
    @endif
    @stack('styles')
</head>
<body class="@yield('body-class')">
    {{-- Navbar --}}
    <div class="navbar">
        <div class="nav-container">
            <a href="{{ route('about') }}" class="logo">
                <i class="fas fa-chart-network"></i> Nusantara Sentiment
            </a>
            <button class="nav-toggle" id="navToggle" onclick="toggleNavMenu()" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">
                    <i class="fas fa-info-circle"></i> Tentang
                </a>
                @auth
                    <a href="{{ route('dashboard.mbg') }}" class="{{ request()->routeIs('dashboard.mbg') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i> MBG
                    </a>
                    <a href="{{ route('dashboard.kdmp') }}" class="{{ request()->routeIs('dashboard.kdmp') ? 'active' : '' }}">
                        <i class="fas fa-handshake"></i> KDMP
                    </a>
                    <a href="{{ route('dashboard.history') }}" class="{{ request()->routeIs('dashboard.history') ? 'active' : '' }}">
                        <i class="fas fa-history"></i> History
                    </a>
                    <a href="javascript:void(0)" onclick="openTwitterCookiesModal()">
                        <i class="fas fa-cookie-bite"></i> Cookie Twitter
                    </a>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <i class="fas fa-user-circle"></i> {{ Auth::user()->name }}
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="{{ route('logout') }}">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-login">
                        <i class="fas fa-key"></i> Login
                    </a>
                    <a href="{{ route('register') }}" class="btn-register">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Konten Halaman --}}
    <div class="dashboard-container">
        @yield('content')
    </div>

    {{-- Footer --}}
    <footer>
        <i class="fas fa-copyright"></i> 2026 Nusantara Sentiment Analysis
    </footer>

    <script>
        function toggleNavMenu() {
            const navLinks = document.getElementById('navLinks');
            const navToggle = document.getElementById('navToggle');
            navLinks.classList.toggle('show');
            
            const icon = navToggle.querySelector('i');
            if (navLinks.classList.contains('show')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
