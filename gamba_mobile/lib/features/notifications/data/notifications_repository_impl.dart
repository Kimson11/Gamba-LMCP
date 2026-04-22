import '../../../core/network/api_client.dart';
import '../../../core/network/idempotency_key_provider.dart';

class NotificationInboxItem {
  const NotificationInboxItem({
    required this.id,
    required this.type,
    required this.title,
    required this.message,
    required this.readAt,
  });

  final String id;
  final String type;
  final String title;
  final String message;
  final String? readAt;

  bool get isRead => readAt != null;

  factory NotificationInboxItem.fromJson(Map<String, dynamic> json) {
    return NotificationInboxItem(
      id: json['id'] as String,
      type: json['type'] as String? ?? 'system',
      title: json['title'] as String? ?? '',
      message: json['message'] as String? ?? '',
      readAt: json['read_at'] as String?,
    );
  }
}

class NotificationsPayload {
  const NotificationsPayload({
    required this.items,
    required this.unreadCount,
  });

  final List<NotificationInboxItem> items;
  final int unreadCount;

  factory NotificationsPayload.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;

    return NotificationsPayload(
      items: ((data['items'] as List<dynamic>?) ?? const [])
          .map((item) => NotificationInboxItem.fromJson(item as Map<String, dynamic>))
          .toList(),
      unreadCount: data['unread_count'] as int? ?? 0,
    );
  }
}

class NotificationsRepositoryImpl {
  NotificationsRepositoryImpl({
    required this.apiClient,
    IdempotencyKeyProvider? idempotencyKeyProvider,
  }) : _idempotencyKeyProvider = idempotencyKeyProvider ?? IdempotencyKeyProvider();

  final ApiClient apiClient;
  final IdempotencyKeyProvider _idempotencyKeyProvider;

  Future<NotificationsPayload> fetch(String token) async {
    final response = await apiClient.get('/notifications', token: token);

    return NotificationsPayload.fromJson(response);
  }

  Future<void> markRead({
    required String token,
    required String notificationId,
  }) async {
    await apiClient.post(
      '/notifications/$notificationId/read',
      token: token,
      headers: {
        'Idempotency-Key': _idempotencyKeyProvider.create('notification-read'),
      },
    );
  }
}
