@extends('layouts.admin')

@section('title', '修改密码')
@section('page_title', '修改密码')
@section('page_desc', '更新当前后台账号的登录密码')

@section('content')
<div class="layui-card form-card">
    <div class="layui-card-header">账号安全</div>
    <div class="layui-card-body">
        <form class="layui-form" method="POST" action="{{ route('admin.password.update') }}">
            @csrf
            @method('PUT')

            <div class="layui-form-item">
                <label class="layui-form-label">当前密码</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="password" name="current_password" lay-verify="required" autocomplete="current-password">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">新密码</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="password" name="password" lay-verify="required" autocomplete="new-password">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">确认密码</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="password" name="password_confirmation" lay-verify="required" autocomplete="new-password">
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit type="submit">保存新密码</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
