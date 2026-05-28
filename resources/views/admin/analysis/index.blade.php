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
            <div class="layui-card-header chart-card-header">
                <span>睡眠与健康评分趋势</span>
                <button class="layui-btn layui-btn-primary layui-btn-sm js-chart-fullscreen" type="button" data-chart="sleep"><i class="layui-icon layui-icon-screen-full"></i> 放大</button>
            </div>
            <div class="layui-card-body">
                <div class="analysis-chart-box"><canvas id="sleepChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header chart-card-header">
                <span>通勤时间趋势</span>
                <button class="layui-btn layui-btn-primary layui-btn-sm js-chart-fullscreen" type="button" data-chart="commute"><i class="layui-icon layui-icon-screen-full"></i> 放大</button>
            </div>
            <div class="layui-card-body">
                <div class="analysis-chart-box"><canvas id="commuteChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header chart-card-header">
                <span>学习 / 运动 / 游戏时间</span>
                <button class="layui-btn layui-btn-primary layui-btn-sm js-chart-fullscreen" type="button" data-chart="time"><i class="layui-icon layui-icon-screen-full"></i> 放大</button>
            </div>
            <div class="layui-card-body">
                <div class="analysis-chart-box"><canvas id="timeChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="layui-col-md6">
        <div class="layui-card">
            <div class="layui-card-header chart-card-header">
                <span>体重与状态评分</span>
                <button class="layui-btn layui-btn-primary layui-btn-sm js-chart-fullscreen" type="button" data-chart="body"><i class="layui-icon layui-icon-screen-full"></i> 放大</button>
            </div>
            <div class="layui-card-body">
                <div class="analysis-chart-box"><canvas id="bodyChart"></canvas></div>
            </div>
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
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    resizeDelay: 120,
    interaction: { mode: 'index', intersect: false },
    elements: {
        point: { radius: 6, hoverRadius: 9 },
        line: { borderWidth: 4 },
        bar: { borderRadius: 8 }
    },
    plugins: {
        legend: {
            position: 'bottom',
            labels: { boxWidth: 24, boxHeight: 24, padding: 22, font: { size: 16 } }
        },
        tooltip: { titleFont: { size: 17 }, bodyFont: { size: 16 }, padding: 14 }
    },
    scales: {
        x: { ticks: { font: { size: 15 } } },
        y: { ticks: { font: { size: 15 } } }
    }
};

const chartConfigs = {
    sleep: {
        title: '睡眠与健康评分趋势',
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: '睡眠小时', data: chartData.sleep, borderColor: '#1e9fff', backgroundColor: 'rgba(30,159,255,.12)', tension: .35 },
                { label: '健康评分', data: chartData.health, borderColor: '#16baaa', backgroundColor: 'rgba(22,186,170,.12)', tension: .35, yAxisID: 'score' }
            ]
        },
        options: { ...chartOptions, scales: { ...chartOptions.scales, score: { position: 'right', min: 0, max: 100, ticks: { font: { size: 13 } } } } }
    },
    commute: {
        title: '通勤时间趋势',
        type: 'bar',
        data: { labels: chartData.labels, datasets: [{ label: '通勤分钟', data: chartData.commute, backgroundColor: '#ffb800' }] },
        options: chartOptions
    },
    time: {
        title: '学习 / 运动 / 游戏时间',
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: '学习', data: chartData.study, backgroundColor: '#5fb878' },
                { label: '运动', data: chartData.exercise, backgroundColor: '#ff5722' },
                { label: '游戏', data: chartData.game, backgroundColor: '#a233c6' }
            ]
        },
        options: { ...chartOptions, scales: { x: { ...chartOptions.scales.x, stacked: true }, y: { ...chartOptions.scales.y, stacked: true } } }
    },
    body: {
        title: '体重与状态评分',
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: '体重', data: chartData.weight, borderColor: '#009688', backgroundColor: 'rgba(0,150,136,.12)', tension: .35 },
                { label: '状态评分', data: chartData.mood, borderColor: '#e91e63', backgroundColor: 'rgba(233,30,99,.12)', tension: .35, yAxisID: 'score' }
            ]
        },
        options: { ...chartOptions, scales: { ...chartOptions.scales, score: { position: 'right', min: 1, max: 5, ticks: { font: { size: 13 } } } } }
    }
};

new Chart(document.getElementById('sleepChart'), chartConfigs.sleep);
new Chart(document.getElementById('commuteChart'), chartConfigs.commute);
new Chart(document.getElementById('timeChart'), chartConfigs.time);
new Chart(document.getElementById('bodyChart'), chartConfigs.body);

layui.use(['layer'], function () {
    const layer = layui.layer;

    document.querySelectorAll('.js-chart-fullscreen').forEach(function (button) {
        button.addEventListener('click', function () {
            const key = button.dataset.chart;
            const config = chartConfigs[key];
            const canvasId = 'fullscreenChart' + Date.now();

            layer.open({
                type: 1,
                title: config.title,
                skin: 'chart-fullscreen-layer',
                area: ['80vw', '80vh'],
                maxmin: true,
                shadeClose: true,
                content: '<div class="fullscreen-chart-box"><canvas id="' + canvasId + '"></canvas></div>',
                success: function (layero) {
                    const content = layero.find('.layui-layer-content')[0];
                    const chartBox = layero.find('.fullscreen-chart-box')[0];
                    content.style.width = '80vw';
                    content.style.height = 'calc(80vh - 43px)';
                    content.style.overflow = 'hidden';
                    chartBox.style.width = '100%';
                    chartBox.style.height = '100%';

                    requestAnimationFrame(function () {
                        const chart = new Chart(document.getElementById(canvasId), {
                            type: config.type,
                            data: config.data,
                            options: {
                                ...config.options,
                                maintainAspectRatio: false,
                                elements: {
                                    point: { radius: 6, hoverRadius: 10 },
                                    line: { borderWidth: 4 },
                                    bar: { borderRadius: 8 }
                                },
                                plugins: {
                                    ...config.options.plugins,
                                    legend: {
                                        position: 'top',
                                        labels: { boxWidth: 24, boxHeight: 24, padding: 24, font: { size: 16 } }
                                    },
                                    tooltip: { titleFont: { size: 17 }, bodyFont: { size: 16 }, padding: 14 }
                                },
                                scales: enlargeScales(config.options.scales || {})
                            }
                        });
                        setTimeout(function () {
                            chart.resize(chartBox.clientWidth, chartBox.clientHeight);
                        }, 160);
                    });
                }
            });
        });
    });
});

function enlargeScales(scales) {
    const result = {};
    Object.keys(scales).forEach(function (key) {
        result[key] = {
            ...scales[key],
            ticks: { ...(scales[key].ticks || {}), font: { size: 15 } }
        };
    });
    return result;
}
</script>
@endsection
