@extends('layouts.admin')

@section('title', '打卡记录管理')
@section('page_title', '打卡记录管理')
@section('page_desc', '日期筛选、补打卡、编辑记录并查看某一天打卡详情')

@section('content')
<div class="layui-card">
    <div class="layui-card-body">
        <form class="layui-form filter-form" method="GET" action="{{ route('admin.checkins.index') }}">
            <div class="layui-inline">
                <input class="layui-input date-picker" type="text" name="date" value="{{ $date }}" placeholder="选择日期">
            </div>
            <button class="layui-btn" type="submit">筛选</button>
        </form>
    </div>
</div>

<div class="content-grid">
    <div class="layui-card">
        <div class="layui-card-header">新增 / 补打卡</div>
        <div class="layui-card-body">
            <form class="layui-form" method="POST" action="{{ route('admin.checkins.store') }}">
                @csrf
                <div class="layui-form-item">
                    <label class="layui-form-label">事项</label>
                    <div class="layui-input-block">
                        <select name="habit_id" lay-verify="required">
                            <option value="">请选择</option>
                            @foreach ($habits as $habit)
                                <option value="{{ $habit->id }}">{{ $habit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">日期</label>
                    <div class="layui-input-block">
                        <input class="layui-input date-picker" type="text" name="checkin_date" value="{{ $date }}" lay-verify="required">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">时间</label>
                    <div class="layui-input-block">
                        <input class="layui-input time-picker" type="text" name="checkin_time" value="{{ now()->format('H:i') }}" lay-verify="required">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">开始/结束</label>
                    <div class="layui-input-inline">
                        <input class="layui-input time-picker" type="text" name="start_time" placeholder="开始/出发">
                    </div>
                    <div class="layui-input-inline">
                        <input class="layui-input time-picker" type="text" name="end_time" placeholder="结束/到达">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">时长/数值</label>
                    <div class="layui-input-inline">
                        <input class="layui-input" type="number" min="0" name="duration_minutes" placeholder="分钟">
                    </div>
                    <div class="layui-input-inline">
                        <input class="layui-input" type="number" step="0.1" min="0" name="value_number" placeholder="体重等数值">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">状态</label>
                    <div class="layui-input-inline">
                        <select name="value_text">
                            <option value="">请选择</option>
                            <option value="极好">极好</option>
                            <option value="很好">很好</option>
                            <option value="还行">还行</option>
                            <option value="一般">一般</option>
                            <option value="差劲">差劲</option>
                        </select>
                    </div>
                    <div class="layui-input-inline">
                        <select name="mood_score">
                            <option value="">评分</option>
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">备注</label>
                    <div class="layui-input-block">
                        <textarea class="layui-textarea" name="note" placeholder="可选"></textarea>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn" lay-submit type="submit">保存打卡</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="layui-card">
        <div class="layui-card-header">{{ $date }} 打卡详情</div>
        <div class="layui-card-body">
            <table class="layui-table">
                <thead>
                <tr>
                    <th>时间</th>
                    <th>事项</th>
                    <th>分析数据</th>
                    <th>备注</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($checkins as $checkin)
                    <tr>
                        <td>{{ substr((string) $checkin->checkin_time, 0, 5) }}</td>
                        <td><span class="color-dot" style="background: {{ $checkin->habit?->color }}"></span>{{ $checkin->habit?->name }}</td>
                        <td>
                            @if ($checkin->duration_minutes)
                                {{ $checkin->duration_minutes }}分钟
                            @elseif ($checkin->start_time || $checkin->end_time)
                                {{ $checkin->start_time ? substr((string) $checkin->start_time, 0, 5) : '-' }} → {{ $checkin->end_time ? substr((string) $checkin->end_time, 0, 5) : '-' }}
                            @elseif ($checkin->value_number)
                                {{ $checkin->value_number }}
                            @elseif ($checkin->value_text)
                                {{ $checkin->value_text }} {{ $checkin->mood_score ? '('.$checkin->mood_score.'分)' : '' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $checkin->note ?: '-' }}</td>
                        <td>
                            <button class="layui-btn layui-btn-normal layui-btn-xs js-edit-checkin" type="button" data-target="#edit-checkin-{{ $checkin->id }}">编辑</button>
                            <form class="inline-form js-delete-form" method="POST" action="{{ route('admin.checkins.destroy', $checkin) }}">
                                @csrf
                                @method('DELETE')
                                <button class="layui-btn layui-btn-danger layui-btn-xs" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">该日期暂无打卡记录</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach ($checkins as $checkin)
    <template id="edit-checkin-{{ $checkin->id }}">
        <form class="layui-form modal-form" method="POST" action="{{ route('admin.checkins.update', $checkin) }}">
            @csrf
            @method('PUT')
            <div class="layui-form-item">
                <label class="layui-form-label">事项</label>
                <div class="layui-input-block">
                    <select name="habit_id" lay-verify="required">
                        @foreach ($habits as $habit)
                            <option value="{{ $habit->id }}" @selected($habit->id === $checkin->habit_id)>{{ $habit->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">日期</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="text" name="checkin_date" value="{{ $checkin->checkin_date->toDateString() }}" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">时间</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="text" name="checkin_time" value="{{ substr((string) $checkin->checkin_time, 0, 5) }}" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">开始/结束</label>
                <div class="layui-input-inline">
                    <input class="layui-input" type="text" name="start_time" value="{{ $checkin->start_time ? substr((string) $checkin->start_time, 0, 5) : '' }}" placeholder="开始/出发">
                </div>
                <div class="layui-input-inline">
                    <input class="layui-input" type="text" name="end_time" value="{{ $checkin->end_time ? substr((string) $checkin->end_time, 0, 5) : '' }}" placeholder="结束/到达">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">时长/数值</label>
                <div class="layui-input-inline">
                    <input class="layui-input" type="number" min="0" name="duration_minutes" value="{{ $checkin->duration_minutes }}" placeholder="分钟">
                </div>
                <div class="layui-input-inline">
                    <input class="layui-input" type="number" step="0.1" min="0" name="value_number" value="{{ $checkin->value_number }}" placeholder="体重等数值">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-inline">
                    <select name="value_text">
                        <option value="">请选择</option>
                        @foreach (['极好', '很好', '还行', '一般', '差劲'] as $level)
                            <option value="{{ $level }}" @selected($checkin->value_text === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="layui-input-inline">
                    <select name="mood_score">
                        <option value="">评分</option>
                        @foreach ([5,4,3,2,1] as $score)
                            <option value="{{ $score }}" @selected((int) $checkin->mood_score === $score)>{{ $score }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-block">
                    <textarea class="layui-textarea" name="note" placeholder="可选">{{ $checkin->note }}</textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit type="submit">保存修改</button>
                </div>
            </div>
        </form>
    </template>
@endforeach
@endsection

@section('scripts')
<script>
layui.use(['layer', 'form'], function () {
    var layer = layui.layer;
    var form = layui.form;

    document.querySelectorAll('.js-edit-checkin').forEach(function (button) {
        button.addEventListener('click', function () {
            var template = document.querySelector(button.dataset.target);
            layer.open({
                type: 1,
                title: '编辑打卡记录',
                area: ['520px', 'auto'],
                content: template.innerHTML,
                success: function () {
                    form.render();
                }
            });
        });
    });
});
</script>
@endsection
