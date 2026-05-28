import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/habit.dart';

class ApiClient {
  ApiClient._();

  static String baseUrl = 'http://192.168.11.23:18083/api';

  static Future<List<Habit>> habits() async {
    final data = await _get('/habits');
    return (data as List).map((item) => Habit.fromJson(item)).toList();
  }

  static Future<Habit> createHabit(Map<String, dynamic> payload) async {
    final data = await _post('/habits', payload);
    return Habit.fromJson(data as Map<String, dynamic>);
  }

  static Future<Habit> updateHabit(int id, Map<String, dynamic> payload) async {
    final data = await _put('/habits/$id', payload);
    return Habit.fromJson(data as Map<String, dynamic>);
  }

  static Future<void> deleteHabit(int id) async {
    await _delete('/habits/$id');
  }

  static Future<List<dynamic>> checkins({String? date}) async {
    final path = date == null ? '/checkins' : '/checkins?date=$date';
    return await _get(path) as List<dynamic>;
  }

  static Future<Map<String, dynamic>> overview() async {
    return await _get('/stats/overview') as Map<String, dynamic>;
  }

  static Future<Map<String, dynamic>> summary() async {
    return await _get('/stats/summary') as Map<String, dynamic>;
  }

  static Future<Map<String, dynamic>> timeline({String? date}) async {
    final path = date == null ? '/stats/timeline' : '/stats/timeline?date=$date';
    return await _get(path) as Map<String, dynamic>;
  }

  static Future<void> checkin(
    int habitId, {
    String? date,
    String? time,
    String? note,
    String? startTime,
    String? endTime,
    int? durationMinutes,
    double? valueNumber,
    String? valueText,
    int? moodScore,
  }) async {
    final now = DateTime.now();
    final checkinDate = date ?? _date(now);
    final checkinTime = time ?? _time(now);
    await _post('/checkins', {
      'habit_id': habitId,
      'checkin_date': checkinDate,
      'checkin_time': checkinTime,
      if (startTime != null) 'start_time': startTime,
      if (endTime != null) 'end_time': endTime,
      if (durationMinutes != null) 'duration_minutes': durationMinutes,
      if (valueNumber != null) 'value_number': valueNumber,
      if (valueText != null) 'value_text': valueText,
      if (moodScore != null) 'mood_score': moodScore,
      'note': note,
    });
  }

  static Future<dynamic> _get(String path) async {
    final response = await http.get(Uri.parse('$baseUrl$path'));
    return _decode(response);
  }

  static Future<dynamic> _post(String path, Map<String, dynamic> body) async {
    final response = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(body),
    );
    return _decode(response);
  }

  static Future<dynamic> _put(String path, Map<String, dynamic> body) async {
    final response = await http.put(
      Uri.parse('$baseUrl$path'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(body),
    );
    return _decode(response);
  }

  static Future<dynamic> _delete(String path) async {
    final response = await http.delete(Uri.parse('$baseUrl$path'));
    return _decode(response);
  }

  static dynamic _decode(http.Response response) {
    final payload = jsonDecode(utf8.decode(response.bodyBytes)) as Map<String, dynamic>;
    if (payload['code'] != 200) {
      throw Exception(payload['message'] ?? '请求失败');
    }
    return payload['data'];
  }

  static String _date(DateTime date) {
    return '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  static String _time(DateTime date) {
    return '${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
  }
}
