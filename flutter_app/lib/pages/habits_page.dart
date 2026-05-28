import 'package:flutter/material.dart';

import '../models/habit.dart';
import '../services/api_client.dart';
import '../utils/habit_visuals.dart';

class HabitsPage extends StatefulWidget {
  const HabitsPage({super.key});

  @override
  State<HabitsPage> createState() => _HabitsPageState();
}

class _HabitsPageState extends State<HabitsPage> {
  late Future<List<Habit>> _future = ApiClient.habits();

  void _reload() {
    setState(() {
      _future = ApiClient.habits();
    });
  }

  Future<void> _openForm([Habit? habit]) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => _HabitForm(habit: habit),
    );
    if (saved == true) {
      _reload();
    }
  }

  Future<void> _delete(Habit habit) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('删除事项'),
        content: Text('确定删除「${habit.name}」吗？历史打卡记录不会一起删除。'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('取消')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('删除')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ApiClient.deleteHabit(habit.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('已删除 ${habit.name}')));
      _reload();
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('删除失败：$error')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('事项管理')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(),
        icon: const Icon(Icons.add),
        label: const Text('新增事项'),
      ),
      body: FutureBuilder<List<Habit>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('加载失败：${snapshot.error}'));
          }
          final habits = snapshot.data!;
          if (habits.isEmpty) {
            return const Center(child: Text('暂无事项，点击右下角新增'));
          }
          final enabledCount = habits.where((habit) => habit.status).length;
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 96),
              itemCount: habits.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                if (index == 0) {
                  return _HabitOverview(total: habits.length, enabled: enabledCount);
                }

                final habit = habits[index - 1];
                final color = habitColor(habit.color);
                return Card(
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: color.withValues(alpha: 0.14),
                      child: Icon(habitIcon(habit.icon, habit.name), color: color),
                    ),
                    title: Text(habit.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text('${habit.category} · ${habit.suggestedTime ?? '未设置时间'}'),
                    trailing: Wrap(
                      spacing: 2,
                      children: [
                        _StatusChip(enabled: habit.status),
                        IconButton(
                          tooltip: '编辑',
                          onPressed: () => _openForm(habit),
                          icon: const Icon(Icons.edit_outlined),
                        ),
                        IconButton(
                          tooltip: '删除',
                          onPressed: () => _delete(habit),
                          icon: const Icon(Icons.delete_outline),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class _HabitOverview extends StatelessWidget {
  const _HabitOverview({required this.total, required this.enabled});

  final int total;
  final int enabled;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Expanded(child: _MiniMetric(label: '全部事项', value: '$total')),
          const SizedBox(width: 8),
          Expanded(child: _MiniMetric(label: '启用中', value: '$enabled')),
        ],
      ),
    );
  }
}

class _MiniMetric extends StatelessWidget {
  const _MiniMetric({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 4),
            Text(value, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.enabled});

  final bool enabled;

  @override
  Widget build(BuildContext context) {
    return Chip(
      label: Text(enabled ? '启用' : '停用'),
      visualDensity: VisualDensity.compact,
      side: BorderSide.none,
      backgroundColor: enabled ? const Color(0xffe7f8f5) : const Color(0xffeef1f5),
      labelStyle: TextStyle(
        color: enabled ? const Color(0xff0c8f82) : const Color(0xff667085),
        fontSize: 12,
      ),
    );
  }
}

class _HabitForm extends StatefulWidget {
  const _HabitForm({this.habit});

  final Habit? habit;

  @override
  State<_HabitForm> createState() => _HabitFormState();
}

class _HabitFormState extends State<_HabitForm> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _categoryController;
  late final TextEditingController _timeController;
  late String _selectedColor;
  late String _selectedIcon;
  late String _habitType;
  late bool _isDaily;
  late bool _status;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final habit = widget.habit;
    _nameController = TextEditingController(text: habit?.name ?? '');
    _categoryController = TextEditingController(text: habit?.category ?? '日常');
    _timeController = TextEditingController(text: habit?.suggestedTime ?? '');
    _selectedColor = _normalizeColor(habit?.color ?? '#16baaa');
    _selectedIcon = normalizeHabitIcon(habit?.icon, habit?.name ?? '');
    _habitType = habit?.habitType ?? 'normal';
    _isDaily = habit?.isDaily ?? true;
    _status = habit?.status ?? true;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _categoryController.dispose();
    _timeController.dispose();
    super.dispose();
  }

  Future<void> _pickTime() async {
    final current = _parseTime(_timeController.text);
    final picked = await showTimePicker(
      context: context,
      initialTime: current ?? TimeOfDay.now(),
    );
    if (picked != null) {
      _timeController.text = '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    final payload = {
      'name': _nameController.text.trim(),
      'category': _categoryController.text.trim().isEmpty ? '日常' : _categoryController.text.trim(),
      'habit_type': _habitType,
      'icon': _selectedIcon,
      'color': _selectedColor,
      'suggested_time': _timeController.text.trim().isEmpty ? null : _timeController.text.trim(),
      'is_daily': _isDaily,
      'status': _status,
      if (widget.habit != null) 'sort_order': widget.habit!.sortOrder,
    };

    try {
      if (widget.habit == null) {
        await ApiClient.createHabit(payload);
      } else {
        await ApiClient.updateHabit(widget.habit!.id, payload);
      }
      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('保存失败：$error')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    final previewColor = habitColor(_selectedColor);

    return Padding(
      padding: EdgeInsets.fromLTRB(16, 12, 16, bottom + 16),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(color: const Color(0xffd0d5dd), borderRadius: BorderRadius.circular(99)),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: previewColor.withValues(alpha: 0.14),
                    child: Icon(habitIcon(_selectedIcon, _nameController.text), color: previewColor, size: 30),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      widget.habit == null ? '新增事项' : '编辑事项',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(labelText: '事项名称'),
                validator: (value) => value == null || value.trim().isEmpty ? '请输入事项名称' : null,
                onChanged: (_) => setState(() {}),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _categoryController,
                      decoration: const InputDecoration(labelText: '分类'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _timeController,
                      readOnly: true,
                      onTap: _pickTime,
                      decoration: InputDecoration(
                        labelText: '建议时间',
                        suffixIcon: IconButton(
                          tooltip: '选择时间',
                          onPressed: _pickTime,
                          icon: const Icon(Icons.schedule),
                        ),
                      ),
                      validator: (value) {
                        final text = value?.trim() ?? '';
                        if (text.isEmpty) return null;
                        return RegExp(r'^([01]\d|2[0-3]):[0-5]\d$').hasMatch(text) ? null : '格式 HH:mm';
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              Text('分析类型', style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 10),
              DropdownButtonFormField<String>(
                initialValue: _habitType,
                decoration: const InputDecoration(labelText: '打卡时需要记录的数据'),
                items: const [
                  DropdownMenuItem(value: 'normal', child: Text('普通打卡')),
                  DropdownMenuItem(value: 'wake', child: Text('起床时间')),
                  DropdownMenuItem(value: 'sleep', child: Text('睡觉时间')),
                  DropdownMenuItem(value: 'commute', child: Text('通勤：出发 / 到达')),
                  DropdownMenuItem(value: 'duration', child: Text('时长：学习 / 运动 / 游戏')),
                  DropdownMenuItem(value: 'weight', child: Text('体重')),
                  DropdownMenuItem(value: 'mood', child: Text('个人状态')),
                ],
                onChanged: (value) => setState(() => _habitType = value ?? 'normal'),
              ),
              const SizedBox(height: 18),
              Text('选择颜色', style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 10),
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: habitColorOptions.map((hex) {
                  final color = habitColor(hex);
                  final selected = _selectedColor.toLowerCase() == hex.toLowerCase();
                  return InkWell(
                    borderRadius: BorderRadius.circular(18),
                    onTap: () => setState(() => _selectedColor = hex),
                    child: Container(
                      width: 38,
                      height: 38,
                      decoration: BoxDecoration(
                        color: color,
                        shape: BoxShape.circle,
                        border: Border.all(color: selected ? Colors.black : Colors.white, width: selected ? 3 : 2),
                        boxShadow: const [BoxShadow(color: Color(0x1a000000), blurRadius: 4, offset: Offset(0, 2))],
                      ),
                      child: selected ? const Icon(Icons.check, color: Colors.white, size: 20) : null,
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 18),
              Text('选择图标', style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 10),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: habitIconOptions.length,
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 4,
                  mainAxisSpacing: 8,
                  crossAxisSpacing: 8,
                  childAspectRatio: 1.08,
                ),
                itemBuilder: (context, index) {
                  final option = habitIconOptions[index];
                  final selected = _selectedIcon == option.value;
                  return InkWell(
                    borderRadius: BorderRadius.circular(8),
                    onTap: () => setState(() => _selectedIcon = option.value),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 160),
                      decoration: BoxDecoration(
                        color: selected ? previewColor.withValues(alpha: 0.12) : Colors.white,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: selected ? previewColor : const Color(0xffdde3ea)),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(option.icon, color: selected ? previewColor : const Color(0xff667085)),
                          const SizedBox(height: 4),
                          Text(option.label, style: const TextStyle(fontSize: 12)),
                        ],
                      ),
                    ),
                  );
                },
              ),
              const SizedBox(height: 12),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('每天都需要打卡'),
                value: _isDaily,
                onChanged: (value) => setState(() => _isDaily = value),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('启用事项'),
                value: _status,
                onChanged: (value) => setState(() => _status = value),
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: _saving ? null : _save,
                icon: Icon(_saving ? Icons.hourglass_empty : Icons.save_outlined),
                label: Text(_saving ? '保存中' : '保存事项'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _normalizeColor(String color) {
    return habitColorOptions.firstWhere(
      (item) => item.toLowerCase() == color.toLowerCase(),
      orElse: () => habitColorOptions.first,
    );
  }

  TimeOfDay? _parseTime(String value) {
    if (!RegExp(r'^([01]\d|2[0-3]):[0-5]\d$').hasMatch(value)) {
      return null;
    }
    final parts = value.split(':');
    return TimeOfDay(hour: int.parse(parts[0]), minute: int.parse(parts[1]));
  }
}
