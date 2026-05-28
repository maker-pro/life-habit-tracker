import 'package:flutter/material.dart';

import '../services/api_client.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('生活习惯打卡')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: ApiClient.overview(),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('加载失败：${snapshot.error}'));
          }
          final data = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _TodayHero(
                todayRate: _asDouble(data['today_rate']),
                streakDays: data['streak_days'] ?? 0,
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(child: _MetricCard(icon: Icons.calendar_view_week_outlined, title: '本周', value: '${data['weekly_rate']}%')),
                  const SizedBox(width: 8),
                  Expanded(child: _MetricCard(icon: Icons.calendar_month_outlined, title: '本月', value: '${data['monthly_rate']}%')),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(child: _MetricCard(icon: Icons.done_all, title: '总打卡', value: '${data['total_checkins'] ?? 0}次')),
                  const SizedBox(width: 8),
                  Expanded(child: _MetricCard(icon: Icons.local_fire_department_outlined, title: '连续', value: '${data['streak_days']}天')),
                ],
              ),
              const SizedBox(height: 16),
              _SummaryBlock(
                text: data['recent_summary'] as String? ?? '今天还没有形成总结，完成几项打卡后再回来看看。',
              ),
            ],
          );
        },
      ),
    );
  }

  double _asDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }
}

class _TodayHero extends StatelessWidget {
  const _TodayHero({required this.todayRate, required this.streakDays});

  final double todayRate;
  final dynamic streakDays;

  @override
  Widget build(BuildContext context) {
    final progress = (todayRate / 100).clamp(0.0, 1.0);
    return Card(
      color: const Color(0xff123b3a),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Row(
          children: [
            SizedBox(
              width: 92,
              height: 92,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  CircularProgressIndicator(
                    value: progress,
                    strokeWidth: 9,
                    backgroundColor: Colors.white.withValues(alpha: 0.16),
                    valueColor: const AlwaysStoppedAnimation(Color(0xff66e3d4)),
                  ),
                  Center(
                    child: Text(
                      '${todayRate.toStringAsFixed(todayRate.truncateToDouble() == todayRate ? 0 : 1)}%',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(color: Colors.white, fontWeight: FontWeight.w800),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 18),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('今日完成率', style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white)),
                  const SizedBox(height: 6),
                  Text(
                    progress >= 1 ? '今天的事项已经全部完成' : '继续完成今日事项，保持节奏',
                    style: const TextStyle(color: Color(0xffc9f4ee)),
                  ),
                  const SizedBox(height: 12),
                  Chip(
                    avatar: const Icon(Icons.local_fire_department_outlined, size: 18),
                    label: Text('连续 $streakDays 天'),
                    side: BorderSide.none,
                    backgroundColor: Colors.white,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.icon, required this.title, required this.value});

  final IconData icon;
  final String title;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 10),
            Text(title, style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 4),
            Text(value, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
          ],
        ),
      ),
    );
  }
}

class _SummaryBlock extends StatelessWidget {
  const _SummaryBlock({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.auto_awesome_outlined, color: Theme.of(context).colorScheme.primary),
                const SizedBox(width: 8),
                Text('生活习惯总结', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
              ],
            ),
            const SizedBox(height: 10),
            Text(text, style: Theme.of(context).textTheme.bodyLarge),
          ],
        ),
      ),
    );
  }
}
