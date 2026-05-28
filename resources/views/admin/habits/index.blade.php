@extends('layouts.admin')

@section('title', '事项管理')
@section('page_title', '事项管理')
@section('page_desc', '查看、新增、编辑、删除每天需要完成的事项')

@section('content')
<div class="toolbar">
    <a class="layui-btn" href="{{ route('admin.habits.create') }}">新增事项</a>
</div>

<div class="layui-card">
    <div class="layui-card-body">
        <table class="layui-table" lay-size="lg">
            <thead>
            <tr>
                <th>排序</th>
                <th>事项</th>
                <th>分类</th>
                <th>图标</th>
                <th>颜色</th>
                <th>建议时间</th>
                <th>每日</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($habits as $habit)
                <tr>
                    <td>{{ $habit->sort_order }}</td>
                    <td>{{ $habit->name }}</td>
                    <td>{{ $habit->category }}</td>
                    <td><i class="layui-icon {{ $habit->icon }}"></i> {{ $habit->icon }}</td>
                    <td><span class="color-dot" style="background: {{ $habit->color }}"></span>{{ $habit->color }}</td>
                    <td>{{ $habit->suggested_time ? substr($habit->suggested_time, 0, 5) : '-' }}</td>
                    <td>{{ $habit->is_daily ? '是' : '否' }}</td>
                    <td><span class="layui-badge {{ $habit->status ? 'layui-bg-green' : '' }}">{{ $habit->status ? '启用' : '停用' }}</span></td>
                    <td>
                        <a class="layui-btn layui-btn-normal layui-btn-xs" href="{{ route('admin.habits.show', $habit) }}">记录</a>
                        <a class="layui-btn layui-btn-xs" href="{{ route('admin.habits.edit', $habit) }}">编辑</a>
                        <form class="inline-form js-delete-form" method="POST" action="{{ route('admin.habits.destroy', $habit) }}">
                            @csrf
                            @method('DELETE')
                            <button class="layui-btn layui-btn-danger layui-btn-xs" type="submit">删除</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
