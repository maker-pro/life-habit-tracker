@extends('layouts.admin')

@section('title', '日历视图')
@section('page_title', '日历视图')
@section('page_desc', '查看每天完成率，点击日期查看当天详情')

@section('content')
<div class="layui-card">
    <div class="layui-card-body">
        <form class="layui-form filter-form" method="GET" action="{{ route('admin.reports.calendar') }}">
            <div class="layui-inline">
                <input class="layui-input month-picker" type="text" name="month" value="{{ $month }}" placeholder="选择月份">
            </div>
            <button class="layui-btn" type="submit">查看</button>
        </form>
    </div>
</div>

<div class="calendar-grid">
    @foreach ($days as $day)
        <a class="layui-card calendar-day" href="{{ route('admin.checkins.index', ['date' => $day['date']]) }}">
            <div class="day-number">{{ \Carbon\Carbon::parse($day['date'])->day }}</div>
            <div class="day-rate">{{ $day['completion_rate'] }}%</div>
            <div class="progress"><span style="width: {{ $day['completion_rate'] }}%"></span></div>
        </a>
    @endforeach
</div>
@endsection
