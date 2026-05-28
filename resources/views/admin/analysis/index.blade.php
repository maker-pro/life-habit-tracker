@extends('layouts.admin')

@section('title', '健康分析')
@section('page_title', '健康分析')
@section('page_desc', '结合睡眠、通勤、学习、运动、游戏、体重和个人状态分析生活习惯影响')

@section('content')
@php
    $today = $overview['today'];
    $weekly = $overview['weekly'];
    $monthly = $overview['monthly'];
@endphp

<div class="layui-row layui-col-space15">
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">今日健康评分</div>
            <div class="layui-card-body"><strong>{{ $today->health_score }}</strong><span>分</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">今日睡眠</div>
            <div class="layui-card-body"><strong>{{ round($today->sleep_minutes / 60, 1) }}</strong><span>小时</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">今日通勤</div>
            <div class="layui-card-body"><strong>{{ $today->commute_minutes }}</strong><span>分钟</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">今日状态</div>
            <div class="layui-card-body"><strong>{{ $today->mood_level ?: '-' }}</strong><span>{{ $today->weight ? $today->weight.'kg' : '' }}</span></div>
        </div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">今日分析</div>
    <div class="layui-card-body">
        <blockquote class="layui-elem-quote">{{ $today->analysis_text }}</blockquote>
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md4">起床：{{ substr((string) $today->wake_time, 0, 5) }}</div>
            <div class="layui-col-md4">睡觉：{{ substr((string) $today->sleep_time, 0, 5) }}</div>
            <div class="layui-col-md4">清醒：{{ round($today->awake_minutes / 60, 1) }} 小时</div>
        </div>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header">睡眠与健康评分趋势</div>
            <div class="layui-card-body"><canvas id="sleepChart" height="220"></canvas></div>
        </div>
    </div>
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header">通勤时间趋势</div>
            <div class="layui-card-body"><canvas id="commuteChart" height="220"></canvas></div>
        </div>
    </div>
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header">学习 / 运动 / 游戏时间</div>
            <div class="layui-card-body"><canvas id="timeChart" height="220"></canvas></div>
        </div>
    </div>
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header">体重与状态评分</div>
            <div class="layui-card-body"><canvas id="bodyChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header">最近7天总结</div>
            <div class="layui-card-body">
                <p>{{ $weekly['summary'] }}</p>
                <table class="layui-table">
                    <tr><td>平均睡眠</td><td>{{ round($weekly['avg_sleep_minutes'] / 60, 1) }} 小时</td></tr>
                    <tr><td>平均通勤</td><td>{{ $weekly['avg_commute_minutes'] }} 分钟</td></tr>
                    <tr><td>学习占清醒时间</td><td>{{ $weekly['study_ratio'] }}%</td></tr>
                    <tr><td>运动占清醒时间</td><td>{{ $weekly['exercise_ratio'] }}%</td></tr>
                    <tr><td>游戏占清醒时间</td><td>{{ $weekly['game_ratio'] }}%</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header">最近30天总结</div>
            <div class="layui-card-body">
                <p>{{ $monthly['summary'] }}</p>
                <table class="layui-table">
                    <tr><td>平均睡眠</td><td>{{ round($monthly['avg_sleep_minutes'] / 60, 1) }} 小时</td></tr>
                    <tr><td>平均通勤</td><td>{{ $monthly['avg_commute_minutes'] }} 分钟</td></tr>
                    <tr><td>学习占清醒时间</td><td>{{ $monthly['study_ratio'] }}%</td></tr>
                    <tr><td>运动占清醒时间</td><td>{{ $monthly['exercise_ratio'] }}%</td></tr>
                    <tr><td>游戏占清醒时间</td><td>{{ $monthly['game_ratio'] }}%</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const chartData = @json($chartData);

new Chart(document.getElementById('sleepChart'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            { label: '睡眠小时', data: chartData.sleep, borderColor: '#1e9fff', tension: .35 },
            { label: '健康评分', data: chartData.health, borderColor: '#16baaa', tension: .35, yAxisID: 'score' }
        ]
    },
    options: { responsive: true, scales: { score: { position: 'right', min: 0, max: 100 } } }
});

new Chart(document.getElementById('commuteChart'), {
    type: 'bar',
    data: { labels: chartData.labels, datasets: [{ label: '通勤分钟', data: chartData.commute, backgroundColor: '#ffb800' }] },
    options: { responsive: true }
});

new Chart(document.getElementById('timeChart'), {
    type: 'bar',
    data: {
        labels: chartData.labels,
        datasets: [
            { label: '学习', data: chartData.study, backgroundColor: '#5fb878' },
            { label: '运动', data: chartData.exercise, backgroundColor: '#ff5722' },
            { label: '游戏', data: chartData.game, backgroundColor: '#a233c6' }
        ]
    },
    options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true } } }
});

new Chart(document.getElementById('bodyChart'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            { label: '体重', data: chartData.weight, borderColor: '#009688', tension: .35 },
            { label: '状态评分', data: chartData.mood, borderColor: '#e91e63', tension: .35, yAxisID: 'score' }
        ]
    },
    options: { responsive: true, scales: { score: { position: 'right', min: 1, max: 5 } } }
});
</script>
@endsection
