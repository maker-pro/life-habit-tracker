class Habit {
  const Habit({
    required this.id,
    required this.name,
    required this.category,
    required this.habitType,
    required this.color,
    this.icon,
    this.suggestedTime,
    this.isDaily = true,
    this.sortOrder = 0,
    this.status = true,
  });

  final int id;
  final String name;
  final String category;
  final String habitType;
  final String color;
  final String? icon;
  final String? suggestedTime;
  final bool isDaily;
  final int sortOrder;
  final bool status;

  factory Habit.fromJson(Map<String, dynamic> json) {
    return Habit(
      id: json['id'] as int,
      name: json['name'] as String,
      category: (json['category'] ?? '日常') as String,
      habitType: (json['habit_type'] ?? 'normal') as String,
      color: (json['color'] ?? '#16baaa') as String,
      icon: json['icon'] as String?,
      suggestedTime: json['suggested_time'] as String?,
      isDaily: json['is_daily'] as bool? ?? true,
      sortOrder: json['sort_order'] as int? ?? 0,
      status: json['status'] as bool? ?? true,
    );
  }

  Map<String, dynamic> toPayload() {
    return {
      'name': name,
      'category': category,
      'habit_type': habitType,
      'icon': icon,
      'color': color,
      'suggested_time': suggestedTime,
      'is_daily': isDaily,
      'sort_order': sortOrder,
      'status': status,
    };
  }
}
