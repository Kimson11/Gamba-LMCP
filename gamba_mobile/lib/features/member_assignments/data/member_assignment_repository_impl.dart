import '../../../core/network/api_client.dart';
import '../../../core/network/idempotency_key_provider.dart';

class AssignmentQueueItem {
  const AssignmentQueueItem({
    required this.id,
    required this.memberId,
    required this.reason,
    required this.pendingForSeconds,
  });

  final int id;
  final int memberId;
  final String? reason;
  final int pendingForSeconds;

  factory AssignmentQueueItem.fromJson(Map<String, dynamic> json) {
    return AssignmentQueueItem(
      id: json['id'] as int,
      memberId: json['member_id'] as int,
      reason: json['reason'] as String?,
      pendingForSeconds: json['pending_for_seconds'] as int? ?? 0,
    );
  }
}

class MemberAssignmentRepositoryImpl {
  MemberAssignmentRepositoryImpl({
    required this.apiClient,
    IdempotencyKeyProvider? idempotencyKeyProvider,
  }) : _idempotencyKeyProvider = idempotencyKeyProvider ?? IdempotencyKeyProvider();

  final ApiClient apiClient;
  final IdempotencyKeyProvider _idempotencyKeyProvider;

  Future<List<AssignmentQueueItem>> fetchPending({
    required String token,
    required Map<String, String> privilegedHeaders,
  }) async {
    final response = await apiClient.get(
      '/member-assignments/pending',
      token: token,
      headers: privilegedHeaders,
    );

    final data = response['data'] as Map<String, dynamic>;

    return ((data['items'] as List<dynamic>?) ?? const [])
        .map((item) => AssignmentQueueItem.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<void> approve({
    required String token,
    required Map<String, String> privilegedHeaders,
    required int assignmentId,
  }) async {
    await apiClient.post(
      '/member-assignments/$assignmentId/approve',
      token: token,
      headers: {
        ...privilegedHeaders,
        'Idempotency-Key': _idempotencyKeyProvider.create('assignment-approve'),
      },
    );
  }

  Future<void> reject({
    required String token,
    required Map<String, String> privilegedHeaders,
    required int assignmentId,
    required String reason,
  }) async {
    await apiClient.post(
      '/member-assignments/$assignmentId/reject',
      token: token,
      headers: {
        ...privilegedHeaders,
        'Idempotency-Key': _idempotencyKeyProvider.create('assignment-reject'),
      },
      body: {
        'reason': reason,
      },
    );
  }
}
