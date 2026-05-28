@extends('layouts.admin')

@section('title', '综合分析报告')
@section('page_title', '综合分析报告')
@section('page_desc', '生成每日、每周、每月中文生活习惯总结')

@section('head')
<style>
    .report-chart-box {
        position: relative;
        height: 420px;
        min-height: 420px;
        width: 100%;
    }

    .report-chart-box canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .report-list {
        margin: 0;
        padding-left: 20px;
        line-height: 2;
    }
</style>
@endsection

@section('content')
<div class="layui-card">
    <div class="layui-card-body">
        <form class="layui-form filter-form" method="GET" action="{{ route('admin.reports.comprehensive') }}">
            <div class="layui-inline">
                <select name="period">
                    <option value="day" @selected($period === 'day')>今日</option>
                    <option value="week" @selected($period === 'week')>最近7天</option>
                    <option value="month" @selected($period === 'month')>最近30天</option>
                </select>
            </div>
            <button class="layui-btn" type="submit">生成报告</button>
            <span class="muted">{{ $report['start_date'] }} 至 {{ $report['end_date'] }}</span>
        </form>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">综合评分</div>
            <div class="layui-card-body"><strong>{{ $report['metrics']['avg_health_score'] }}</strong><span>分</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">平均睡眠</div>
            <div class="layui-card-body"><strong>{{ $report['metrics']['avg_sleep_hours'] }}</strong><span>小时</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">平均通勤</div>
            <div class="layui-card-body"><strong>{{ $report['metrics']['avg_commute_minutes'] }}</strong><span>分钟</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">平均状态</div>
            <div class="layui-card-body"><strong>{{ $report['metrics']['avg_mood_score'] ?: '-' }}</strong><span>{{ $report['metrics']['avg_mood_score'] ? '分' : '' }}</span></div>
        </div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">{{ $report['label'] }}中文总结</div>
    <div class="layui-card-body summary-text">
        {{ $report['summary'] }}
    </div>
</div>

<div class="content-grid">
    <div class="layui-card">
        <div class="layui-card-header">关键发现</div>
        <div class="layui-card-body">
            <ol class="report-list">
                @foreach ($report['insights'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
        </div>
    </div>
    <div class="layui-card">
        <div class="layui-card-header">调整建议</div>
        <div class="layui-card-body">
            <ol class="report-list">
                @foreach ($report['suggestions'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
        </div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">综合趋势</div>
    <div class="layui-card-body">
        <div class="report-chart-box"><canvas id="comprehensiveChart"></canvas></div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">每日报告明细</div>
    <div class="layui-card-body">
        <table class="layui-table" lay-size="lg">
            <thead>
            <tr>
                <th>日期</th>
                <th>睡眠</th>
                <th>通勤</th>
                <th>运动/游戏</th>
                <th>体重/状态</th>
                <th>健康评分</th>
                <th>每日总结</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($report['rows'] as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['sleep_hours'] }}小时</td>
                    <td>{{ $row['commute_minutes'] }}分钟</td>
                    <td>运动{{ $row['exercise_minutes'] }}分钟 / 游戏{{ $row['game_minutes'] }}分钟</td>
                    <td>{{ $row['weight'] ? $row['weight'].'kg' : '-' }} / {{ $row['mood_level'] ?: '-' }}</td>
                    <td>{{ $row['health_score'] }}</td>
                    <td>{{ $row['analysis_text'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
const chart = @json($report['chart']);
const instance = new Chart(document.getElementById('comprehensiveChart'), {
    type: 'line',
    data: {
        labels: chart.labels,
        datasets: [
            { label: '健康评分', data: chart.health, borderColor: '#16baaa', backgroundColor: 'rgba(22,186,170,.12)', tension: .35, borderWidth: 3 },
            { label: '睡眠小时', data: chart.sleep, borderColor: '#1e9fff', backgroundColor: 'rgba(30,159,255,.12)', tension: .35, borderWidth: 3 },
            { label: '运动分钟', data: chart.exercise, borderColor: '#ff5722', backgroundColor: 'rgba(255,87,34,.12)', tension: .35, borderWidth: 3 },
            { label: '游戏分钟', data: chart.game, borderColor: '#a233c6', backgroundColor: 'rgba(162,51,198,.12)', tension: .35, borderWidth: 3 },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 20, boxHeight: 20, font: { size: 14 } } } },
        scales: { x: { ticks: { font: { size: 13 } } }, y: { ticks: { font: { size: 13 } } } }
    }
});

setTimeout(function () {
    const box = document.getElementById('comprehensiveChart').closest('.report-chart-box');
    instance.resize(box.clientWidth, box.clientHeight);
}, 120);
</script>
@endsection
