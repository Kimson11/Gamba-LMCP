import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'session_storage.dart';

class SharedPreferencesSessionStorage implements SessionStorage {
  static const _storageKey = 'auth_session_v1';

  @override
  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_storageKey);
  }

  @override
  Future<Map<String, dynamic>?> read() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);

    if (raw == null || raw.isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(raw);

      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    } catch (_) {
      await clear();
    }

    return null;
  }

  @override
  Future<void> write(Map<String, dynamic> session) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_storageKey, jsonEncode(session));
  }
}
