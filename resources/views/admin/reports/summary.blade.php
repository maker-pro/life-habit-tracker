@extends('layouts.admin')

@section('title', '统计报告')
@section('page_title', '统计报告')
@section('page_desc', '今日、最近7天、最近30天和每个事项分析')

@section('content')
<div class="content-grid">
    <div class="layui-card">
        <div class="layui-card-header">今日总结</div>
        <div class="layui-card-body summary-text">{{ $summary['today'] }}</div>
    </div>
    <div class="layui-card">
        <div class="layui-card-header">最近7天总结</div>
        <div class="layui-card-body summary-text">{{ $summary['last_7_days'] }}</div>
    </div>
    <div class="layui-card">
        <div class="layui-card-header">最近30天总结</div>
        <div class="layui-card-body summary-text">{{ $summary['last_30_days'] }}</div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">完成率趋势</div>
    <div class="layui-card-body chart-box">
        <canvas id="summaryChart"></canvas>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">每个事项分析</div>
    <div class="layui-card-body">
        <table class="layui-table">
            <thead>
            <tr>
                <th>事项</th>
                <th>完成率</th>
                <th>平均时间</th>
                <th>趋势</th>
                <th>中文总结</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($summary['habit_analysis'] as $item)
                <tr>
                    <td>{{ $item['habit_name'] }}</td>
                    <td>{{ $item['completion_rate'] }}%</td>
                    <td>{{ $item['average_time'] ?: '-' }}</td>
                    <td>{{ $item['trend']['direction'] }} {{ $item['trend']['minutes'] }}分钟</td>
                    <td>{{ $item['summary'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
layui.use(['layer'], function () {
    new Chart(document.getElementById('summaryChart'), {
        type: 'line',
        data: {
            labels: ['今日', '本周', '本月'],
            datasets: [{ label: '完成率 %', data: [{{ $overview['today_rate'] }}, {{ $overview['weekly_rate'] }}, {{ $overview['monthly_rate'] }}], borderColor: '#16baaa', backgroundColor: 'rgba(22,186,170,.18)', tension: .35, fill: true }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } } }
    });
});
</script>
@endsection
