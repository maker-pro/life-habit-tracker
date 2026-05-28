<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - 生活习惯打卡统计系统</title>
    <link rel="stylesheet" href="/layui/css/layui.css?v=2.9.21">
    <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="layui-card login-card">
        <div class="layui-card-header">生活习惯打卡后台</div>
        <div class="layui-card-body">
            <form class="layui-form" method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="layui-form-item">
                    <label class="layui-form-label">邮箱</label>
                    <div class="layui-input-block">
                        <input class="layui-input" type="email" name="email" value="{{ old('email', 'admin@example.com') }}" lay-verify="required|email" autocomplete="email">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">密码</label>
                    <div class="layui-input-block">
                        <input class="layui-input" type="password" name="password" lay-verify="required" autocomplete="current-password">
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <input type="checkbox" name="remember" value="1" title="记住我" lay-skin="primary">
                    </div>
                </div>
                @if ($errors->any())
                    <div class="login-error">{{ $errors->first() }}</div>
                @endif
                <button class="layui-btn layui-btn-fluid" lay-submit type="submit">登录后台</button>
            </form>
            <p class="login-tip">默认账号：admin@example.com，默认密码：admin123456</p>
        </div>
    </section>
</main>
<script src="/layui/layui.js?v=2.9.21"></script>
</body>
</html>
