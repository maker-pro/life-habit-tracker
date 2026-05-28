import 'package:flutter/material.dart';

import '../models/habit.dart';
import '../services/api_client.dart';
import '../utils/habit_visuals.dart';

class TodayPage extends StatefulWidget {
  const TodayPage({super.key});

  @override
  State<TodayPage> createState() => _TodayPageState();
}

class _TodayPageState extends State<TodayPage> {
  late Future<_TodayData> future = _loadData();
  final Set<int> _checkedHabitIds = {};
  final Set<int> _checkingHabitIds = {};

  Future<_TodayData> _loadData() async {
    final today = _date(DateTime.now());
    final results = await Future.wait([
      ApiClient.habits(),
      ApiClient.checkins(date: today),
    ]);
    final habits = results[0] as List<Habit>;
    final checkins = results[1];
    _checkedHabitIds
      ..clear()
      ..addAll(checkins.map((item) => item['habit_id'] as int));
    return _TodayData(habits: habits);
  }

  Future<void> _openCheckinSheet(Habit habit) async {
    if (_checkedHabitIds.contains(habit.id) || _checkingHabitIds.contains(habit.id)) {
      return;
    }

    final payload = await showModalBottomSheet<_CheckinPayload>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (context) => _CheckinSheet(habit: habit),
    );
    if (payload == null) return;

    setState(() => _checkingHabitIds.add(habit.id));
    try {
      await ApiClient.checkin(
        habit.id,
        time: payload.checkinTime,
        note: payload.note,
        startTime: payload.startTime,
        endTime: payload.endTime,
        durationMinutes: payload.durationMinutes,
        valueNumber: payload.valueNumber,
        valueText: payload.valueText,
        moodScore: payload.moodScore,
      );
      if (!mounted) return;
      setState(() => _checkedHabitIds.add(habit.id));
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${habit.name} 打卡成功')));
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('打卡失败：$error')));
    } finally {
      if (mounted) setState(() => _checkingHabitIds.remove(habit.id));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('今日打卡')),
      body: FutureBuilder<_TodayData>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('加载失败：${snapshot.error}'));
          }
          final habits = snapshot.data!.habits.where((habit) => habit.status).toList();
          if (habits.isEmpty) {
            return const Center(child: Text('还没有启用的打卡事项'));
          }
          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemBuilder: (context, index) {
              final habit = habits[index];
              final color = habitColor(habit.color);
              final isChecked = _checkedHabitIds.contains(habit.id);
              final isChecking = _checkingHabitIds.contains(habit.id);
              return Card(
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  leading: CircleAvatar(
                    backgroundColor: color.withValues(alpha: 0.14),
                    child: Icon(habitIcon(habit.icon, habit.name), color: color),
                  ),
                  title: Text(habit.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Text(_subtitle(habit)),
                  trailing: FilledButton.icon(
                    onPressed: isChecked || isChecking ? null : () => _openCheckinSheet(habit),
                    icon: Icon(isChecked ? Icons.check_circle : Icons.add_task),
                    label: Text(isChecked ? '已打卡' : (isChecking ? '提交中' : '打卡')),
                  ),
                ),
              );
            },
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemCount: habits.length,
          );
        },
      ),
    );
  }

  String _subtitle(Habit habit) {
    final type = switch (habit.habitType) {
      'commute' => '通勤时间',
      'duration' => '记录时长',
      'weight' => '记录体重',
      'mood' => '评估状态',
      'wake' => '起床时间',
      'sleep' => '睡觉时间',
      _ => '普通打卡',
    };
    return '$type  建议 ${habit.suggestedTime ?? '-'}';
  }

  String _date(DateTime date) {
    return '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }
}

class _CheckinSheet extends StatefulWidget {
  const _CheckinSheet({required this.habit});

  final Habit habit;

  @override
  State<_CheckinSheet> createState() => _CheckinSheetState();
}

class _CheckinSheetState extends State<_CheckinSheet> {
  late final TextEditingController _noteController = TextEditingController();
  late TimeOfDay _checkinTime = TimeOfDay.now();
  TimeOfDay? _startTime;
  TimeOfDay? _endTime;
  int? _durationMinutes;
  double? _weight;
  String? _moodLevel;
  int? _moodScore;

  final _durations = const [15, 30, 45, 60, 90, 120, 180];
  final _moods = const [
    ('极好', 5),
    ('很好', 4),
    ('还行', 3),
    ('一般', 2),
    ('差劲', 1),
  ];

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _pickTime(String field) async {
    final picked = await showTimePicker(context: context, initialTime: TimeOfDay.now());
    if (picked == null) return;
    setState(() {
      if (field == 'checkin') _checkinTime = picked;
      if (field == 'start') _startTime = picked;
      if (field == 'end') _endTime = picked;
    });
  }

  void _submit() {
    if (widget.habit.habitType == 'commute' && (_startTime == null || _endTime == null)) {
      _message('请选择出发时间和到达时间');
      return;
    }
    if (widget.habit.habitType == 'duration' && _durationMinutes == null) {
      _message('请选择持续时间');
      return;
    }
    if (widget.habit.habitType == 'weight' && _weight == null) {
      _message('请输入今日体重');
      return;
    }
    if (widget.habit.habitType == 'mood' && _moodLevel == null) {
      _message('请选择今日状态');
      return;
    }

    Navigator.pop(
      context,
      _CheckinPayload(
        checkinTime: _format(_checkinTime),
        startTime: _startTime == null ? null : _format(_startTime!),
        endTime: _endTime == null ? null : _format(_endTime!),
        durationMinutes: _durationMinutes,
        valueNumber: _weight,
        valueText: _moodLevel,
        moodScore: _moodScore,
        note: _noteController.text.trim().isEmpty ? null : _noteController.text.trim(),
      ),
    );
  }

  void _message(String text) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    final color = habitColor(widget.habit.color);

    return Padding(
      padding: EdgeInsets.fromLTRB(16, 12, 16, bottom + 16),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(width: 42, height: 4, decoration: BoxDecoration(color: const Color(0xffd0d5dd), borderRadius: BorderRadius.circular(99))),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                CircleAvatar(
                  radius: 26,
                  backgroundColor: color.withValues(alpha: 0.14),
                  child: Icon(habitIcon(widget.habit.icon, widget.habit.name), color: color),
                ),
                const SizedBox(width: 12),
                Expanded(child: Text(widget.habit.name, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700))),
              ],
            ),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: () => _pickTime('checkin'),
              icon: const Icon(Icons.schedule),
              label: Text('打卡时间 ${_format(_checkinTime)}'),
            ),
            const SizedBox(height: 12),
            if (widget.habit.habitType == 'commute') _commuteFields(),
            if (widget.habit.habitType == 'duration') _durationFields(),
            if (widget.habit.habitType == 'weight') _weightFields(),
            if (widget.habit.habitType == 'mood') _moodFields(),
            TextField(
              controller: _noteController,
              maxLines: 3,
              decoration: const InputDecoration(labelText: '备注', hintText: '例如：下雨、感冒、路上堵车'),
            ),
            const SizedBox(height: 16),
            FilledButton.icon(onPressed: _submit, icon: const Icon(Icons.check), label: const Text('保存打卡')),
          ],
        ),
      ),
    );
  }

  Widget _commuteFields() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: () => _pickTime('start'),
              icon: const Icon(Icons.directions_walk),
              label: Text('出发 ${_startTime == null ? '选择' : _format(_startTime!)}'),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: OutlinedButton.icon(
              onPressed: () => _pickTime('end'),
              icon: const Icon(Icons.flag_outlined),
              label: Text('到达 ${_endTime == null ? '选择' : _format(_endTime!)}'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _durationFields() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        children: _durations.map((minutes) {
          return ChoiceChip(
            label: Text('$minutes 分钟'),
            selected: _durationMinutes == minutes,
            onSelected: (_) => setState(() => _durationMinutes = minutes),
          );
        }).toList(),
      ),
    );
  }

  Widget _weightFields() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextField(
        keyboardType: const TextInputType.numberWithOptions(decimal: true),
        decoration: const InputDecoration(labelText: '今日体重 kg', suffixText: 'kg'),
        onChanged: (value) => _weight = double.tryParse(value),
      ),
    );
  }

  Widget _moodFields() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        children: _moods.map((item) {
          return ChoiceChip(
            label: Text(item.$1),
            selected: _moodLevel == item.$1,
            onSelected: (_) => setState(() {
              _moodLevel = item.$1;
              _moodScore = item.$2;
            }),
          );
        }).toList(),
      ),
    );
  }

  String _format(TimeOfDay time) {
    return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
  }
}

class _CheckinPayload {
  const _CheckinPayload({
    required this.checkinTime,
    this.startTime,
    this.endTime,
    this.durationMinutes,
    this.valueNumber,
    this.valueText,
    this.moodScore,
    this.note,
  });

  final String checkinTime;
  final String? startTime;
  final String? endTime;
  final int? durationMinutes;
  final double? valueNumber;
  final String? valueText;
  final int? moodScore;
  final String? note;
}

class _TodayData {
  const _TodayData({required this.habits});

  final List<Habit> habits;
}
