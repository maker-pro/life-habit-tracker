import 'package:flutter_test/flutter_test.dart';

import 'package:life_habit_tracker_app/main.dart';

void main() {
  testWidgets('Life habit app renders navigation', (WidgetTester tester) async {
    await tester.pumpWidget(const LifeHabitApp());

    expect(find.text('生活习惯打卡'), findsOneWidget);
    expect(find.text('首页'), findsOneWidget);
    expect(find.text('今日'), findsOneWidget);
    expect(find.text('统计'), findsOneWidget);
  });
}
