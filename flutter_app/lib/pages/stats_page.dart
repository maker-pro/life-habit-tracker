import 'package:flutter/material.dart';

import '../services/api_client.dart';

class StatsPage extends StatelessWidget {
  const StatsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('统计')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: ApiClient.summary(),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('加载失败：${snapshot.error}'));
          }
          final data = snapshot.data!;
          final analysis = data['habit_analysis'] as List<dynamic>;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _SummaryCard(title: '今日总结', text: data['today'] as String),
              _SummaryCard(title: '最近7天总结', text: data['last_7_days'] as String),
              _SummaryCard(title: '最近30天总结', text: data['last_30_days'] as String),
              const SizedBox(height: 12),
              Text('事项分析', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
              const SizedBox(height: 8),
              ...analysis.map((item) {
                final map = item as Map<String, dynamic>;
                final rate = _asDouble(map['completion_rate']);
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text('${map['habit_name']}', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                            ),
                            Text('${map['completion_rate']}%', style: Theme.of(context).textTheme.titleMedium),
                          ],
                        ),
                        const SizedBox(height: 8),
                        LinearProgressIndicator(value: (rate / 100).clamp(0.0, 1.0), minHeight: 8),
                        const SizedBox(height: 10),
                        Text(map['summary'] as String),
                      ],
                    ),
                  ),
                );
              }),
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

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.title, required this.text});

  final String title;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            Text(text),
          ],
        ),
      ),
    );
  }
}
