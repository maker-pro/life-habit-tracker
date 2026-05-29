@extends('layouts.admin')

@section('title', $topic['title'])
@section('page_title', $topic['title'])
@section('page_desc', $topic['desc'])

@section('head')
<style>
    .topic-chart-box {
        position: relative;
        height: 420px;
        min-height: 420px;
        width: 100%;
    }

    .topic-chart-box canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    .topic-chart-box.large {
        height: 560px;
        min-height: 560px;
    }

    .topic-nav {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
</style>
@endsection

@section('content')
<div class="topic-nav">
    <a class="layui-btn {{ $topic['key'] === 'sleep' ? '' : 'layui-btn-primary' }}" href="{{ route('admin.analysis.topic', 'sleep') }}">睡眠分析</a>
    <a class="layui-btn {{ $topic['key'] === 'commute' ? '' : 'layui-btn-primary' }}" href="{{ route('admin.analysis.topic', 'commute') }}">通勤分析</a>
    <a class="layui-btn {{ $topic['key'] === 'time' ? '' : 'layui-btn-primary' }}" href="{{ route('admin.analysis.topic', 'time') }}">时间分配</a>
    <a class="layui-btn {{ $topic['key'] === 'body' ? '' : 'layui-btn-primary' }}" href="{{ route('admin.analysis.topic', 'body') }}">体重状态</a>
</div>

<div class="layui-card">
    <div class="layui-card-body">
        <form class="layui-form filter-form" method="GET" action="{{ route('admin.analysis.topic', $topic['key']) }}">
            <div class="layui-inline">
                <select name="period">
                    <option value="week" @selected($period === 'week')>最近7天</option>
                    <option value="month" @selected($period === 'month')>最近30天</option>
                </select>
            </div>
            <button class="layui-btn" type="submit">切换周期</button>
            <span class="muted">{{ $topic['start_date'] }} 至 {{ $topic['end_date'] }}</span>
        </form>
    </div>
</div>

<div class="layui-row layui-col-space15">
    @foreach ($topic['cards'] as $card)
        <div class="layui-col-md3 layui-col-sm6">
            <div class="layui-card metric-card">
                <div class="layui-card-header">{{ $card['label'] }}</div>
                <div class="layui-card-body"><strong>{{ $card['value'] }}</strong><span>{{ $card['unit'] }}</span></div>
            </div>
        </div>
    @endforeach
</div>

<div class="layui-card">
    <div class="layui-card-header">分析结论</div>
    <div class="layui-card-body">
        <blockquote class="layui-elem-quote">{{ $topic['summary'] }}</blockquote>
    </div>
</div>

<div class="layui-row layui-col-space15">
    @foreach ($topic['charts'] as $chart)
        <div class="{{ !empty($chart['wide']) ? 'layui-col-md12' : 'layui-col-md6' }}">
            <div class="layui-card">
                <div class="layui-card-header">{{ $chart['title'] }}</div>
                <div class="layui-card-body">
                    <div class="topic-chart-box {{ !empty($chart['wide']) ? 'large' : '' }}"><canvas id="{{ $chart['id'] }}"></canvas></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="layui-card">
    <div class="layui-card-header">每日明细</div>
    <div class="layui-card-body">
        <table class="layui-table" lay-size="lg">
            <thead>
            <tr>
                <th>日期</th>
                <th>主要数据</th>
                <th>辅助信息</th>
                <th>影响分析</th>
                <th>备注</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($topic['rows'] as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['main'] }}</td>
                    <td>{{ $row['sub'] }}</td>
                    <td>{{ $row['impact'] }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
const charts = @json($topic['charts']);

charts.forEach(function (chart) {
    if (chart.type === 'pie' || chart.type === 'doughnut') {
        const instance = new Chart(document.getElementById(chart.id), {
            type: chart.type,
            data: {
                labels: chart.labels,
                datasets: [{
                    label: chart.title,
                    data: chart.values,
                    backgroundColor: chart.colors,
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: chart.type === 'doughnut' ? '58%' : 0,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 20, boxHeight: 20, padding: 18, font: { size: 14 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce(function (sum, item) {
                                    return sum + Number(item || 0);
                                }, 0);
                                const value = Number(ctx.raw || 0);
                                const percent = total > 0 ? (value / total * 100).toFixed(1) : '0.0';
                                return ctx.label + '：' + value + '分钟，占比' + percent + '%';
                            }
                        }
                    }
                }
            }
        });

        setTimeout(function () {
            const box = document.getElementById(chart.id).closest('.topic-chart-box');
            instance.resize(box.clientWidth, box.clientHeight);
        }, 120);
        return;
    }

    const datasets = chart.datasets.map(function (dataset) {
        return {
            label: dataset.label,
            data: dataset.data,
            borderColor: dataset.color,
            backgroundColor: chart.type === 'bar' ? dataset.color : dataset.color + '22',
            yAxisID: dataset.axis || 'y',
            unit: dataset.unit || '',
            tension: .35,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 7,
            borderRadius: 6,
        };
    });
    const hasDurationAxis = datasets.some(function (dataset) { return dataset.yAxisID === 'duration' || dataset.yAxisID === 'y'; });
    const hasClockAxis = datasets.some(function (dataset) { return dataset.yAxisID === 'clock'; });
    const hasScoreAxis = datasets.some(function (dataset) { return dataset.yAxisID === 'score'; });
    const hasHealthAxis = datasets.some(function (dataset) { return dataset.yAxisID === 'health'; });

    const instance = new Chart(document.getElementById(chart.id), {
        type: chart.type,
        data: {
            labels: chart.labels,
            datasets: datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 20, boxHeight: 20, padding: 18, font: { size: 14 } }
                },
                tooltip: {
                    callbacks: {
                        title: function (items) {
                            const index = items[0].dataIndex;
                            const detail = chart.details ? chart.details[index] : null;
                            return detail ? detail.date : items[0].label;
                        },
                        label: function (ctx) {
                            if (ctx.dataset.yAxisID === 'clock') {
                                return ctx.dataset.label + '：' + formatClock(ctx.raw);
                            }
                            if (ctx.dataset.yAxisID === 'score') {
                                return ctx.dataset.label + '：' + ctx.formattedValue + '分';
                            }
                            return ctx.dataset.label + '：' + ctx.formattedValue + (ctx.dataset.unit || '');
                        },
                        afterBody: function (items) {
                            const index = items[0].dataIndex;
                            const detail = chart.details ? chart.details[index] : null;
                            if (!detail) return [];

                            return [
                                '起床时间：' + detail.wake_time,
                                '睡觉时间：' + detail.sleep_time,
                                '睡眠时长：' + detail.sleep_hours + '小时',
                                '睡眠质量：' + detail.quality,
                                '健康评分：' + detail.health_score + '分',
                                '个人状态：' + detail.mood + '（' + detail.mood_score + '）',
                                '备注：' + detail.note
                            ];
                        }
                    }
                }
            },
            scales: {
                x: { stacked: chart.stacked === true, ticks: { font: { size: 13 } } },
                y: {
                    type: 'linear',
                    position: 'left',
                    display: hasDurationAxis,
                    stacked: chart.stacked === true,
                    ticks: { font: { size: 13 } },
                    title: { display: hasDurationAxis && chart.id.toLowerCase().includes('sleep'), text: '时长（小时）' }
                },
                clock: {
                    type: 'linear',
                    display: hasClockAxis,
                    position: hasDurationAxis ? 'right' : 'left',
                    min: 0,
                    max: 24,
                    grid: { drawOnChartArea: !hasDurationAxis },
                    ticks: {
                        font: { size: 13 },
                        callback: function (value) {
                            return formatClock(value);
                        }
                    },
                    title: { display: hasClockAxis, text: '时间点' }
                },
                score: {
                    type: 'linear',
                    display: hasScoreAxis,
                    position: 'right',
                    min: 1,
                    max: 5,
                    grid: { drawOnChartArea: false },
                    ticks: { font: { size: 13 } },
                    title: { display: hasScoreAxis, text: '状态评分' }
                },
                health: {
                    type: 'linear',
                    display: hasHealthAxis,
                    position: 'left',
                    min: 0,
                    max: 100,
                    ticks: { font: { size: 13 } },
                    title: { display: hasHealthAxis, text: '健康评分' }
                }
            }
        }
    });

    setTimeout(function () {
        const box = document.getElementById(chart.id).closest('.topic-chart-box');
        instance.resize(box.clientWidth, box.clientHeight);
    }, 120);
});

function formatClock(value) {
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return '-';
    let hour = Math.floor(numeric) % 24;
    let minute = Math.round((numeric - Math.floor(numeric)) * 60);
    if (minute >= 60) {
        minute = 0;
        hour = (hour + 1) % 24;
    }
    return String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
}
</script>
@endsection
