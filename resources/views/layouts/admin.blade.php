<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '生活习惯打卡统计系统')</title>
    <link rel="stylesheet" href="/layui/css/layui.css?v=2.9.21">
    <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0">
    @yield('head')
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="brand">生活习惯打卡</div>
        <ul class="layui-nav layui-nav-tree" lay-filter="admin-nav">
            <li class="layui-nav-item {{ request()->routeIs('admin.dashboard') ? 'layui-this' : '' }}"><a href="{{ route('admin.dashboard') }}">Dashboard 首页</a></li>
            <li class="layui-nav-item {{ request()->routeIs('admin.habits.*') ? 'layui-this' : '' }}"><a href="{{ route('admin.habits.index') }}">事项管理</a></li>
            <li class="layui-nav-item {{ request()->routeIs('admin.checkins.*') ? 'layui-this' : '' }}"><a href="{{ route('admin.checkins.index') }}">打卡记录</a></li>
            <li class="layui-nav-item {{ request()->routeIs('admin.analysis.*') ? 'layui-this' : '' }}"><a href="{{ route('admin.analysis.index') }}">健康分析</a></li>
            <li class="layui-nav-item {{ request()->routeIs('admin.reports.calendar') ? 'layui-this' : '' }}"><a href="{{ route('admin.reports.calendar') }}">日历视图</a></li>
            <li class="layui-nav-item {{ request()->routeIs('admin.reports.timeline') ? 'layui-this' : '' }}"><a href="{{ route('admin.reports.timeline') }}">时间线视图</a></li>
            <li class="layui-nav-item {{ request()->routeIs('admin.reports.summary') ? 'layui-this' : '' }}"><a href="{{ route('admin.reports.summary') }}">统计报告</a></li>
        </ul>
    </aside>
    <main class="app-main">
        <header class="app-header">
            <div>
                <h1>@yield('page_title', 'Dashboard 首页')</h1>
                <p>@yield('page_desc', '记录打卡时间，分析生活规律')</p>
            </div>
            <div class="header-actions">
                @auth
                    <span class="admin-user">{{ auth()->user()->name }}</span>
                    <form class="inline-form" method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="layui-btn layui-btn-primary layui-border-red" type="submit">退出登录</button>
                    </form>
                @endauth
                <button class="layui-btn layui-btn-primary layui-border-green" id="themeToggle" type="button">切换主题</button>
            </div>
        </header>

        @if (session('success'))
            <div class="layui-card app-message" data-message="{{ session('success') }}"></div>
        @endif

        @if ($errors->any())
            <div class="layui-card app-errors" data-message="{{ $errors->first() }}"></div>
        @endif

        @yield('content')
    </main>
</div>

<script src="/layui/layui.js?v=2.9.21"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/chart.umd.js?v=4.4.3"></script>
<script src="/assets/js/admin.js?v=1.0.0"></script>
@yield('scripts')
</body>
</html>
