import 'package:flutter/foundation.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/storage/sync_queue_storage.dart';
import '../../data/sync_repository_impl.dart';

class SyncCenterController extends ChangeNotifier {
  SyncCenterController({
    required this.repository,
    SyncQueueStorage? queueStorage,
  }) : _queueStorage = queueStorage ?? InMemorySyncQueueStorage();

  final SyncRepositoryImpl repository;
  final SyncQueueStorage _queueStorage;

  String? _token;
  bool _isLoading = false;
  String? _errorMessage;
  SyncStatusSummary? _summary;
  List<QueuedConflictResolution> _pendingQueue = [];

  void bindToken(String token) {
    _token = token;
  }

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  SyncStatusSummary? get summary => _summary;
  List<QueuedConflictResolution> get pendingQueue => List.unmodifiable(_pendingQueue);

  Future<void> load() async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    await _flushQueue();

    try {
      _summary = await repository.fetch(_token!);
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
    } finally {
      _pendingQueue = await _queueStorage.read();
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> resolveConflict(SyncConflictItem item, {String action = 'view_server_record'}) async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    try {
      await repository.resolve(
        token: _token!,
        conflictId: item.id,
        action: action,
        reason: 'Resolved from mobile sync center.',
      );
      await load();
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
      final current = await _queueStorage.read();
      final queued = QueuedConflictResolution(
        conflictId: item.id,
        action: action,
        reason: 'Resolved from mobile sync center.',
        queuedAt: DateTime.now(),
        status: 'queued',
      );
      await _queueStorage.write([...current, queued]);
      _pendingQueue = await _queueStorage.read();
      notifyListeners();
    }
  }

  Future<void> _flushQueue() async {
    if (_token == null) {
      return;
    }

    final queued = await _queueStorage.read();
    final remaining = <QueuedConflictResolution>[];

    for (final item in queued) {
      try {
        await repository.resolve(
          token: _token!,
          conflictId: item.conflictId,
          action: item.action,
          reason: item.reason,
        );
      } on ApiException {
        remaining.add(item);
        break;
      }
    }

    if (remaining.length != queued.length) {
      await _queueStorage.write(remaining);
    }
  }
}
