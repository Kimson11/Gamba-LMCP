import '../../../core/network/api_client.dart';
import '../../../core/network/idempotency_key_provider.dart';

class SyncStatusSummary {
  const SyncStatusSummary({
    required this.counts,
    required this.recentResults,
    required this.conflicts,
  });

  final Map<String, int> counts;
  final List<SyncRecentResult> recentResults;
  final List<SyncConflictItem> conflicts;

  factory SyncStatusSummary.fromJson(
    Map<String, dynamic> statusJson,
    Map<String, dynamic> conflictsJson,
  ) {
    final statusData = statusJson['data'] as Map<String, dynamic>;
    final conflictsData = conflictsJson['data'] as Map<String, dynamic>;

    return SyncStatusSummary(
      counts: ((statusData['counts'] as Map<String, dynamic>?) ?? const {})
          .map((key, value) => MapEntry(key, (value as num).toInt())),
      recentResults: ((statusData['recent_results'] as List<dynamic>?) ?? const [])
          .map((item) => SyncRecentResult.fromJson(item as Map<String, dynamic>))
          .toList(),
      conflicts: ((conflictsData['items'] as List<dynamic>?) ?? const [])
          .map((item) => SyncConflictItem.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }
}

class SyncRecentResult {
  const SyncRecentResult({
    required this.requestType,
    required this.status,
    required this.processedAt,
  });

  final String requestType;
  final String status;
  final String? processedAt;

  factory SyncRecentResult.fromJson(Map<String, dynamic> json) {
    return SyncRecentResult(
      requestType: json['request_type'] as String? ?? '',
      status: json['status'] as String? ?? '',
      processedAt: json['processed_at'] as String?,
    );
  }
}

class SyncConflictItem {
  const SyncConflictItem({
    required this.id,
    required this.conflictCode,
    required this.requestType,
    required this.resolutionRequired,
  });

  final int id;
  final String conflictCode;
  final String requestType;
  final bool resolutionRequired;

  factory SyncConflictItem.fromJson(Map<String, dynamic> json) {
    return SyncConflictItem(
      id: json['id'] as int,
      conflictCode: json['conflict_code'] as String? ?? '',
      requestType: json['request_type'] as String? ?? '',
      resolutionRequired: json['resolution_required'] as bool? ?? false,
    );
  }
}

class SyncRepositoryImpl {
  SyncRepositoryImpl({
    required this.apiClient,
    IdempotencyKeyProvider? idempotencyKeyProvider,
  }) : _idempotencyKeyProvider = idempotencyKeyProvider ?? IdempotencyKeyProvider();

  final ApiClient apiClient;
  final IdempotencyKeyProvider _idempotencyKeyProvider;

  Future<SyncStatusSummary> fetch(String token) async {
    final status = await apiClient.get('/sync/status', token: token);
    final conflicts = await apiClient.get('/sync/conflicts', token: token);

    return SyncStatusSummary.fromJson(status, conflicts);
  }

  Future<void> resolve({
    required String token,
    required int conflictId,
    required String action,
    String? reason,
  }) async {
    await apiClient.post(
      '/sync/conflicts/$conflictId/resolve',
      token: token,
      headers: {
        'Idempotency-Key': _idempotencyKeyProvider.create('sync-conflict-resolve'),
      },
      body: {
        'data': {
          'action': action,
          if (reason != null && reason.isNotEmpty) 'reason': reason,
        },
      },
    );
  }
}
