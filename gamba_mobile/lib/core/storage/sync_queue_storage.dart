import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart'; // ignore: depend_on_referenced_packages

class QueuedConflictResolution {
  const QueuedConflictResolution({
    required this.conflictId,
    required this.action,
    required this.reason,
    required this.queuedAt,
    required this.status,
  });

  final int conflictId;
  final String action;
  final String? reason;
  final DateTime queuedAt;
  final String status;

  QueuedConflictResolution copyWith({
    String? status,
  }) {
    return QueuedConflictResolution(
      conflictId: conflictId,
      action: action,
      reason: reason,
      queuedAt: queuedAt,
      status: status ?? this.status,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'conflict_id': conflictId,
      'action': action,
      'reason': reason,
      'queued_at': queuedAt.toIso8601String(),
      'status': status,
    };
  }

  factory QueuedConflictResolution.fromJson(Map<String, dynamic> json) {
    return QueuedConflictResolution(
      conflictId: (json['conflict_id'] as num).toInt(),
      action: json['action'] as String? ?? 'view_server_record',
      reason: json['reason'] as String?,
      queuedAt: DateTime.tryParse(json['queued_at'] as String? ?? '') ?? DateTime.now(),
      status: json['status'] as String? ?? 'queued',
    );
  }
}

abstract class SyncQueueStorage {
  Future<List<QueuedConflictResolution>> read();

  Future<void> write(List<QueuedConflictResolution> queue);
}

class InMemorySyncQueueStorage implements SyncQueueStorage {
  List<QueuedConflictResolution> _queue = const [];

  @override
  Future<List<QueuedConflictResolution>> read() async {
    return List<QueuedConflictResolution>.from(_queue);
  }

  @override
  Future<void> write(List<QueuedConflictResolution> queue) async {
    _queue = List<QueuedConflictResolution>.from(queue);
  }
}

class SharedPreferencesSyncQueueStorage implements SyncQueueStorage {
  static const _storageKey = 'sync_conflict_queue_v1';

  @override
  Future<List<QueuedConflictResolution>> read() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);

    if (raw == null || raw.isEmpty) {
      return const [];
    }

    try {
      final decoded = jsonDecode(raw);

      if (decoded is! List<dynamic>) {
        return const [];
      }

      return decoded
          .whereType<Map<String, dynamic>>()
          .map(QueuedConflictResolution.fromJson)
          .toList();
    } catch (_) {
      await prefs.remove(_storageKey);
      return const [];
    }
  }

  @override
  Future<void> write(List<QueuedConflictResolution> queue) async {
    final prefs = await SharedPreferences.getInstance();
    final payload = queue.map((item) => item.toJson()).toList(growable: false);
    await prefs.setString(_storageKey, jsonEncode(payload));
  }
}
