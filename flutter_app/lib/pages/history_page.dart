import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../utils/habit_visuals.dart';

class HistoryPage extends StatefulWidget {
  const HistoryPage({super.key});

  @override
  State<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends State<HistoryPage> {
  DateTime date = DateTime.now();

  @override
  Widget build(BuildContext context) {
    final dateText = _date(date);
    return Scaffold(
      appBar: AppBar(
        title: const Text('历史记录'),
        actions: [
          IconButton(
            onPressed: () async {
              final picked = await showDatePicker(
                context: context,
                firstDate: DateTime(2020),
                lastDate: DateTime.now().add(const Duration(days: 365)),
                initialDate: date,
              );
              if (picked != null) setState(() => date = picked);
            },
            icon: const Icon(Icons.calendar_month),
          ),
        ],
      ),
      body: FutureBuilder<List<dynamic>>(
        future: ApiClient.checkins(date: dateText),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('加载失败：${snapshot.error}'));
          }
          final items = snapshot.data!;
          if (items.isEmpty) {
            return const Center(child: Text('这一天还没有打卡记录'));
          }
          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index] as Map<String, dynamic>;
              final habit = item['habit'] as Map<String, dynamic>?;
              final color = habitColor((habit?['color'] as String?) ?? '#16baaa');
              final name = habit?['name'] as String? ?? '未知事项';
              return Card(
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: color.withValues(alpha: 0.14),
                    child: Icon(habitIcon(habit?['icon'] as String?, name), color: color),
                  ),
                  title: Text(name),
                  subtitle: Text('${item['checkin_date']} ${item['checkin_time']}'),
                  trailing: Text(item['note'] ?? ''),
                ),
              );
            },
          );
        },
      ),
    );
  }

  String _date(DateTime value) {
    return '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
  }
}
