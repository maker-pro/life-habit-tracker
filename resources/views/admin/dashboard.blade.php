@extends('layouts.admin')

@section('title', 'Dashboard 首页')
@section('page_title', 'Dashboard 首页')
@section('page_desc', '今日、本周、本月完成率和最近生活习惯总结')

@section('content')
<div class="stats-grid">
    <div class="layui-card metric-card">
        <div class="layui-card-header">今日完成率</div>
        <div class="layui-card-body"><strong>{{ $overview['today_rate'] }}%</strong></div>
    </div>
    <div class="layui-card metric-card">
        <div class="layui-card-header">本周完成率</div>
        <div class="layui-card-body"><strong>{{ $overview['weekly_rate'] }}%</strong></div>
    </div>
    <div class="layui-card metric-card">
        <div class="layui-card-header">本月完成率</div>
        <div class="layui-card-body"><strong>{{ $overview['monthly_rate'] }}%</strong></div>
    </div>
    <div class="layui-card metric-card">
        <div class="layui-card-header">总打卡次数</div>
        <div class="layui-card-body"><strong>{{ $overview['total_checkins'] }}</strong></div>
    </div>
    <div class="layui-card metric-card">
        <div class="layui-card-header">连续打卡天数</div>
        <div class="layui-card-body"><strong>{{ $overview['streak_days'] }}</strong></div>
    </div>
</div>

<div class="content-grid">
    <div class="layui-card">
        <div class="layui-card-header">最近生活习惯总结</div>
        <div class="layui-card-body summary-text">{{ $overview['recent_summary'] }}</div>
    </div>
    <div class="layui-card">
        <div class="layui-card-header">今日时间线</div>
        <div class="layui-card-body">
            <ul class="timeline-list">
                @forelse ($timeline['items'] as $item)
                    <li><span>{{ $item['time'] }}</span><i style="background: {{ $item['habit_color'] }}"></i>{{ $item['habit_name'] }}</li>
                @empty
                    <li class="muted">今天还没有打卡记录</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">最近30天事项完成率</div>
    <div class="layui-card-body chart-box">
        <canvas id="habitRateChart"></canvas>
    </div>
</div>
@endsection

@section('scripts')
<script>
layui.use(['layer'], function () {
    const labels = @json($overview['habit_rates']->pluck('habit_name'));
    const values = @json($overview['habit_rates']->pluck('completion_rate'));
    const colors = @json($overview['habit_rates']->pluck('color'));
    new Chart(document.getElementById('habitRateChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: '完成率 %', data: values, backgroundColor: colors }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } } }
    });
});
</script>
@endsection
