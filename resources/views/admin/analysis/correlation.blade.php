@extends('layouts.admin')

@section('title', '行为影响分析')
@section('page_title', '行为影响分析')
@section('page_desc', '分析睡眠、运动、游戏、通勤对个人状态和健康评分的影响')

@section('head')
<style>
    .correlation-chart-box {
        position: relative;
        height: 360px;
        min-height: 360px;
        width: 100%;
    }

    .correlation-chart-box canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    .impact-delta {
        font-size: 18px;
        font-weight: 700;
        color: var(--accent);
    }

    .impact-list {
        line-height: 2;
        margin: 0;
        padding-left: 20px;
    }
</style>
@endsection

@section('content')
<div class="layui-card">
    <div class="layui-card-body">
        <form class="layui-form filter-form" method="GET" action="{{ route('admin.analysis.correlation') }}">
            <div class="layui-inline">
                <select name="period">
                    <option value="week" @selected($period === 'week')>最近7天</option>
                    <option value="month" @selected($period === 'month')>最近30天</option>
                </select>
            </div>
            <button class="layui-btn" type="submit">重新分析</button>
            <span class="muted">{{ $analysis['start_date'] }} 至 {{ $analysis['end_date'] }}，共 {{ $analysis['days'] }} 天</span>
        </form>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">核心结论</div>
    <div class="layui-card-body summary-text">{{ $analysis['summary'] }}</div>
</div>

<div class="content-grid">
    <div class="layui-card">
        <div class="layui-card-header">影响因素排名</div>
        <div class="layui-card-body">
            <table class="layui-table">
                <thead>
                <tr>
                    <th>因素</th>
                    <th>较好组</th>
                    <th>对比组</th>
                    <th>差值</th>
                    <th>样本</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($analysis['ranking'] as $item)
                    <tr>
                        <td>{{ $item['title'] }}</td>
                        <td>{{ $item['good_label'] }}：{{ $item['good_avg'] }}{{ $item['unit'] }}</td>
                        <td>{{ $item['bad_label'] }}：{{ $item['bad_avg'] }}{{ $item['unit'] }}</td>
                        <td><span class="impact-delta">{{ $item['delta'] > 0 ? '+' : '' }}{{ $item['delta'] }}{{ $item['unit'] }}</span></td>
                        <td>{{ $item['good_count'] }} / {{ $item['bad_count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">有效样本不足，继续记录后再分析</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="layui-card">
        <div class="layui-card-header">调整建议</div>
        <div class="layui-card-body">
            <ol class="impact-list">
                @foreach ($analysis['suggestions'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
        </div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">次日影响分析</div>
    <div class="layui-card-body">
        <table class="layui-table">
            <thead>
            <tr>
                <th>分析项</th>
                <th>较好组</th>
                <th>对比组</th>
                <th>差值</th>
                <th>说明</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($analysis['next_day'] as $item)
                <tr>
                    <td>{{ $item['title'] }}</td>
                    <td>{{ $item['good_label'] }}：{{ $item['good_avg'] }}{{ $item['unit'] }}</td>
                    <td>{{ $item['bad_label'] }}：{{ $item['bad_avg'] }}{{ $item['unit'] }}</td>
                    <td>{{ $item['delta'] > 0 ? '+' : '' }}{{ $item['delta'] }}{{ $item['unit'] }}</td>
                    <td>{{ $item['text'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">次日样本不足，至少需要连续两天记录状态</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="layui-row layui-col-space15">
    @foreach ($analysis['charts'] as $chart)
        <div class="layui-col-md6">
            <div class="layui-card">
                <div class="layui-card-header">{{ $chart['title'] }}</div>
                <div class="layui-card-body">
                    <div class="correlation-chart-box"><canvas id="{{ $chart['id'] }}"></canvas></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
const charts = @json($analysis['charts']);

charts.forEach(function (chart) {
    const instance = new Chart(document.getElementById(chart.id), {
        type: 'scatter',
        data: {
            datasets: [{
                label: chart.title,
                data: chart.points,
                borderColor: chart.color,
                backgroundColor: chart.color + '99',
                pointRadius: 6,
                pointHoverRadius: 9
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 20, boxHeight: 20, font: { size: 14 } }
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return chart.x_label + '：' + ctx.raw.x + '，' + chart.y_label + '：' + ctx.raw.y;
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: chart.x_label }, ticks: { font: { size: 13 } } },
                y: { title: { display: true, text: chart.y_label }, ticks: { font: { size: 13 } } }
            }
        }
    });

    setTimeout(function () {
        const box = document.getElementById(chart.id).closest('.correlation-chart-box');
        instance.resize(box.clientWidth, box.clientHeight);
    }, 120);
});
</script>
@endsection
