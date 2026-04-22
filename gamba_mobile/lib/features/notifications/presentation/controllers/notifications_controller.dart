import 'package:flutter/foundation.dart';

import '../../../../core/network/api_exception.dart';
import '../../data/notifications_repository_impl.dart';

class NotificationsController extends ChangeNotifier {
  NotificationsController({
    required this.repository,
  });

  final NotificationsRepositoryImpl repository;

  String? _token;
  bool _isLoading = false;
  String? _errorMessage;
  List<NotificationInboxItem> _items = const [];
  int _unreadCount = 0;

  void bindToken(String token) {
    _token = token;
  }

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  List<NotificationInboxItem> get items => _items;
  int get unreadCount => _unreadCount;

  Future<void> load() async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final payload = await repository.fetch(_token!);
      _items = payload.items;
      _unreadCount = payload.unreadCount;
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> markRead(String notificationId) async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    try {
      await repository.markRead(
        token: _token!,
        notificationId: notificationId,
      );
      await load();
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
      notifyListeners();
    }
  }
}
