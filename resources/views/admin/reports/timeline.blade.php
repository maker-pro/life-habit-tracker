@extends('layouts.admin')

@section('title', '时间线视图')
@section('page_title', '时间线视图')
@section('page_desc', '按时间顺序展示一天的打卡节奏')

@section('content')
<div class="layui-card">
    <div class="layui-card-body">
        <form class="layui-form filter-form" method="GET" action="{{ route('admin.reports.timeline') }}">
            <div class="layui-inline">
                <input class="layui-input date-picker" type="text" name="date" value="{{ $date }}" placeholder="选择日期">
            </div>
            <button class="layui-btn" type="submit">查看</button>
        </form>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">{{ $date }} 时间线</div>
    <div class="layui-card-body">
        <ul class="timeline-list large">
            @forelse ($timeline['items'] as $item)
                <li><span>{{ $item['time'] }}</span><i style="background: {{ $item['habit_color'] }}"></i>{{ $item['habit_name'] }} @if($item['note'])<em>{{ $item['note'] }}</em>@endif</li>
            @empty
                <li class="muted">该日期暂无时间线</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
