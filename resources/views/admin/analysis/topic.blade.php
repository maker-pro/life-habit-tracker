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
        <div class="layui-col-md6">
            <div class="layui-card">
                <div class="layui-card-header">{{ $chart['title'] }}</div>
                <div class="layui-card-body">
                    <div class="topic-chart-box"><canvas id="{{ $chart['id'] }}"></canvas></div>
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
            tension: .35,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 7,
            borderRadius: 6,
        };
    });

    const instance = new Chart(document.getElementById(chart.id), {
        type: chart.type,
        data: {
            labels: chart.labels,
            datasets: datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 20, boxHeight: 20, padding: 18, font: { size: 14 } }
                }
            },
            scales: {
                x: { stacked: chart.stacked === true, ticks: { font: { size: 13 } } },
                y: { stacked: chart.stacked === true, ticks: { font: { size: 13 } } }
            }
        }
    });

    setTimeout(function () {
        const box = document.getElementById(chart.id).closest('.topic-chart-box');
        instance.resize(box.clientWidth, box.clientHeight);
    }, 120);
});
</script>
@endsection
