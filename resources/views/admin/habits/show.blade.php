@extends('layouts.admin')

@section('title', $habit->name.'打卡记录')
@section('page_title', $habit->name.'打卡记录')
@section('page_desc', '查看该事项的每一次打卡、时间、时长、状态和备注')

@section('head')
<style>
    .record-chart-box {
        position: relative;
        height: 360px;
        min-height: 360px;
        width: 100%;
    }

    .record-chart-box canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    .record-detail {
        line-height: 1.8;
    }

    .record-detail strong {
        color: var(--accent);
        margin-right: 4px;
    }
</style>
@endsection

@section('content')
@php
    $typeLabels = [
        'normal' => '普通打卡',
        'commute' => '通勤',
        'duration' => '时长事项',
        'weight' => '体重',
        'mood' => '个人状态',
        'sleep' => '睡觉',
        'wake' => '起床',
    ];
@endphp

<div class="toolbar">
    <a class="layui-btn layui-btn-primary" href="{{ route('admin.habits.index') }}">返回事项管理</a>
    <a class="layui-btn" href="{{ route('admin.checkins.index') }}">查看全部打卡</a>
</div>

<div class="layui-card">
    <div class="layui-card-body">
        <form class="layui-form filter-form" method="GET" action="{{ route('admin.habits.show', $habit) }}">
            <div class="layui-inline">
                <input class="layui-input date-picker" type="text" name="start_date" value="{{ $start }}" placeholder="开始日期">
            </div>
            <div class="layui-inline">
                <input class="layui-input date-picker" type="text" name="end_date" value="{{ $end }}" placeholder="结束日期">
            </div>
            <button class="layui-btn" type="submit">筛选</button>
        </form>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">事项类型</div>
            <div class="layui-card-body"><strong>{{ $typeLabels[$habit->habit_type] ?? '普通打卡' }}</strong></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">记录次数</div>
            <div class="layui-card-body"><strong>{{ $summary['total'] }}</strong><span>次</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">覆盖天数</div>
            <div class="layui-card-body"><strong>{{ $summary['days'] }}</strong><span>天</span></div>
        </div>
    </div>
    <div class="layui-col-md3 layui-col-sm6">
        <div class="layui-card metric-card">
            <div class="layui-card-header">平均时长</div>
            <div class="layui-card-body"><strong>{{ $summary['avg_duration'] ?: '-' }}</strong><span>{{ $summary['avg_duration'] ? '分钟' : '' }}</span></div>
        </div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">{{ $chart['label'] }}趋势</div>
    <div class="layui-card-body">
        <div class="record-chart-box"><canvas id="habitRecordChart"></canvas></div>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">打卡明细</div>
    <div class="layui-card-body">
        <table class="layui-table" lay-size="lg">
            <thead>
            <tr>
                <th>日期</th>
                <th>打卡时间</th>
                <th>记录详情</th>
                <th>备注</th>
                <th>更新时间</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>{{ $record->checkin_date->toDateString() }}</td>
                    <td>{{ substr((string) $record->checkin_time, 0, 5) }}</td>
                    <td class="record-detail">
                        @if ($habit->habit_type === 'commute')
                            <div><strong>出发</strong>{{ $record->start_time ? substr((string) $record->start_time, 0, 5) : '-' }}</div>
                            <div><strong>到达</strong>{{ $record->end_time ? substr((string) $record->end_time, 0, 5) : '-' }}</div>
                            <div><strong>通勤</strong>{{ $record->duration_minutes ? $record->duration_minutes.'分钟' : '未完成' }}</div>
                        @elseif ($habit->habit_type === 'duration')
                            <div><strong>开始</strong>{{ substr((string) $record->checkin_time, 0, 5) }}</div>
                            <div><strong>持续</strong>{{ $record->duration_minutes ? $record->duration_minutes.'分钟' : '-' }}</div>
                        @elseif ($habit->habit_type === 'weight')
                            <div><strong>体重</strong>{{ $record->value_number ? $record->value_number.'kg' : '-' }}</div>
                        @elseif ($habit->habit_type === 'mood')
                            <div><strong>状态</strong>{{ $record->value_text ?: '-' }}</div>
                            <div><strong>评分</strong>{{ $record->mood_score ? $record->mood_score.'分' : '-' }}</div>
                        @else
                            <div><strong>时间</strong>{{ substr((string) $record->checkin_time, 0, 5) }}</div>
                        @endif
                    </td>
                    <td>{{ $record->note ?: '-' }}</td>
                    <td>{{ optional($record->updated_at)->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">当前筛选范围内暂无记录</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="table-pagination">
            @if ($records->previousPageUrl())
                <a class="layui-btn layui-btn-primary layui-btn-sm" href="{{ $records->previousPageUrl() }}">上一页</a>
            @endif
            <span class="muted">第 {{ $records->currentPage() }} / {{ $records->lastPage() }} 页，共 {{ $records->total() }} 条</span>
            @if ($records->nextPageUrl())
                <a class="layui-btn layui-btn-primary layui-btn-sm" href="{{ $records->nextPageUrl() }}">下一页</a>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const recordChart = @json($chart);

new Chart(document.getElementById('habitRecordChart'), {
    type: 'line',
    data: {
        labels: recordChart.labels,
        datasets: [{
            label: recordChart.label,
            data: recordChart.values,
            borderColor: '{{ $habit->color }}',
            backgroundColor: '{{ $habit->color }}22',
            tension: .35,
            pointRadius: 5,
            pointHoverRadius: 8,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 20, boxHeight: 20, font: { size: 14 } }
            }
        },
        scales: {
            x: { ticks: { font: { size: 13 } } },
            y: { ticks: { font: { size: 13 } } }
        }
    }
});
</script>
@endsection
