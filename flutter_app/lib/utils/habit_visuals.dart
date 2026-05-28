import 'package:flutter/material.dart';

class HabitIconOption {
  const HabitIconOption(this.value, this.label, this.icon);

  final String value;
  final String label;
  final IconData icon;
}

const habitIconOptions = [
  HabitIconOption('wake', '起床', Icons.wb_sunny_outlined),
  HabitIconOption('work', '上班', Icons.work_outline),
  HabitIconOption('meal', '吃饭', Icons.restaurant_outlined),
  HabitIconOption('home', '下班', Icons.home_outlined),
  HabitIconOption('sport', '运动', Icons.directions_run),
  HabitIconOption('study', '学习', Icons.menu_book_outlined),
  HabitIconOption('game', '游戏', Icons.sports_esports_outlined),
  HabitIconOption('sleep', '睡觉', Icons.bedtime_outlined),
  HabitIconOption('shower', '洗澡', Icons.shower_outlined),
  HabitIconOption('coffee', '休息', Icons.local_cafe_outlined),
  HabitIconOption('health', '健康', Icons.favorite_border),
  HabitIconOption('todo', '其他', Icons.task_alt),
];

const habitColorOptions = [
  '#16baaa',
  '#1e9fff',
  '#5fb878',
  '#ffb800',
  '#ff5722',
  '#a233c6',
  '#2f4056',
  '#31bdec',
  '#e91e63',
  '#607d8b',
  '#795548',
  '#009688',
];

IconData habitIcon(String? value, String name) {
  final normalized = _normalizeIconValue(value);
  for (final option in habitIconOptions) {
    if (option.value == normalized) {
      return option.icon;
    }
  }

  if (name.contains('起床')) return Icons.wb_sunny_outlined;
  if (name.contains('上班')) return Icons.work_outline;
  if (name.contains('吃饭')) return Icons.restaurant_outlined;
  if (name.contains('下班')) return Icons.home_outlined;
  if (name.contains('运动')) return Icons.directions_run;
  if (name.contains('学习')) return Icons.menu_book_outlined;
  if (name.contains('游戏')) return Icons.sports_esports_outlined;
  if (name.contains('睡觉')) return Icons.bedtime_outlined;
  if (name.contains('洗澡')) return Icons.shower_outlined;
  return Icons.task_alt;
}

String normalizeHabitIcon(String? value, String name) {
  final normalized = _normalizeIconValue(value);
  if (normalized != null && habitIconOptions.any((item) => item.value == normalized)) {
    return normalized;
  }
  if (name.contains('起床')) return 'wake';
  if (name.contains('上班')) return 'work';
  if (name.contains('吃饭')) return 'meal';
  if (name.contains('下班')) return 'home';
  if (name.contains('运动')) return 'sport';
  if (name.contains('学习')) return 'study';
  if (name.contains('游戏')) return 'game';
  if (name.contains('睡觉')) return 'sleep';
  if (name.contains('洗澡')) return 'shower';
  return 'todo';
}

Color habitColor(String hex) {
  final value = hex.replaceFirst('#', '');
  return Color(int.tryParse('ff$value', radix: 16) ?? 0xff16baaa);
}

String? _normalizeIconValue(String? value) {
  if (value == null || value.trim().isEmpty) {
    return null;
  }
  final text = value.trim();
  final legacyMap = {
    'layui-icon-face-smile': 'wake',
    'layui-icon-template-1': 'work',
    'layui-icon-cart': 'meal',
    'layui-icon-release': 'home',
    'layui-icon-fire': 'sport',
    'layui-icon-read': 'study',
    'layui-icon-game': 'game',
    'layui-icon-moon': 'sleep',
  };
  return legacyMap[text] ?? text;
}
