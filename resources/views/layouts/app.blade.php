<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B81">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Larry & Janden — 恋爱纪念日</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <canvas id="hearts"></canvas>

    @auth
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="logout-btn" title="退出">🚪</button>
        </form>
        <button type="button" class="theme-toggle" id="themeToggle" title="夜间模式">🌙</button>
    @endauth

    <div id="app">
        @yield('content')

        @auth
            <nav class="tabbar">
                <a href="{{ route('home') }}" class="tab @if(request()->routeIs('home')) active @endif">
                    <span class="ic">🏠</span>
                    <span class="tx">首页</span>
                </a>
                <a href="{{ route('anniversaries.index') }}" class="tab @if(request()->routeIs('anniversaries.*')) active @endif">
                    <span class="ic">📅</span>
                    <span class="tx">纪念日</span>
                </a>
                <a href="{{ route('diary.index') }}" class="tab @if(request()->routeIs('diary.*')) active @endif">
                    <span class="ic">📖</span>
                    <span class="tx">日记</span>
                </a>
                <a href="{{ route('about') }}" class="tab @if(request()->routeIs('about')) active @endif">
                    <span class="ic">👤</span>
                    <span class="tx">关于</span>
                </a>
            </nav>
        @endauth
    </div>

    @yield('lightbox')

    @stack('scripts')
</body>
</html>
