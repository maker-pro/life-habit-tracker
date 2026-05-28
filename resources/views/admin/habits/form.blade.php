<div class="layui-card form-card">
    <div class="layui-card-body">
        <form class="layui-form" method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <div class="layui-form-item">
                <label class="layui-form-label">事项名称</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="text" name="name" value="{{ old('name', $habit->name ?? '') }}" required lay-verify="required" placeholder="例如：起床">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">分类</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="text" name="category" value="{{ old('category', $habit->category ?? '日常') }}" placeholder="作息、工作、健康">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">分析类型</label>
                <div class="layui-input-block">
                    <select name="habit_type">
                        @php($type = old('habit_type', $habit->habit_type ?? 'normal'))
                        <option value="normal" @selected($type === 'normal')>普通打卡</option>
                        <option value="wake" @selected($type === 'wake')>起床</option>
                        <option value="sleep" @selected($type === 'sleep')>睡觉</option>
                        <option value="commute" @selected($type === 'commute')>通勤</option>
                        <option value="duration" @selected($type === 'duration')>时长事项</option>
                        <option value="weight" @selected($type === 'weight')>体重</option>
                        <option value="mood" @selected($type === 'mood')>个人状态</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">图标</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="text" name="icon" value="{{ old('icon', $habit->icon ?? 'layui-icon-star') }}" placeholder="layui-icon-star">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">颜色</label>
                <div class="layui-input-inline">
                    <input class="layui-input" type="color" name="color" value="{{ old('color', $habit->color ?? '#16baaa') }}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">建议时间</label>
                <div class="layui-input-inline">
                    <input class="layui-input time-picker" type="text" name="suggested_time" value="{{ old('suggested_time', isset($habit) && $habit->suggested_time ? substr($habit->suggested_time, 0, 5) : '') }}" placeholder="HH:mm">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">每日事项</label>
                <div class="layui-input-block">
                    <input type="hidden" name="is_daily" value="0">
                    <input type="checkbox" name="is_daily" value="1" lay-skin="switch" lay-text="是|否" {{ old('is_daily', $habit->is_daily ?? true) ? 'checked' : '' }}>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" lay-skin="switch" lay-text="启用|停用" {{ old('status', $habit->status ?? true) ? 'checked' : '' }}>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">排序</label>
                <div class="layui-input-inline">
                    <input class="layui-input" type="number" min="0" name="sort_order" value="{{ old('sort_order', $habit->sort_order ?? 0) }}">
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit type="submit">保存</button>
                    <a class="layui-btn layui-btn-primary" href="{{ route('admin.habits.index') }}">返回</a>
                </div>
            </div>
        </form>
    </div>
</div>
