import 'package:flutter/foundation.dart';

import '../../../../core/network/api_exception.dart';
import '../../data/member_assignment_repository_impl.dart';

class MemberAssignmentController extends ChangeNotifier {
  MemberAssignmentController({
    required this.repository,
  });

  final MemberAssignmentRepositoryImpl repository;

  String? _token;
  Map<String, String> _privilegedHeaders = const {};
  bool _isLoading = false;
  String? _errorMessage;
  List<AssignmentQueueItem> _items = const [];

  void bindSession({
    required String token,
    required Map<String, String> privilegedHeaders,
  }) {
    _token = token;
    _privilegedHeaders = privilegedHeaders;
  }

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  List<AssignmentQueueItem> get items => _items;

  Future<void> load() async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _items = await repository.fetchPending(
        token: _token!,
        privilegedHeaders: _privilegedHeaders,
      );
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> approve(int assignmentId) async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    try {
      await repository.approve(
        token: _token!,
        privilegedHeaders: _privilegedHeaders,
        assignmentId: assignmentId,
      );
      await load();
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
      notifyListeners();
    }
  }

  Future<void> reject(int assignmentId) async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    try {
      await repository.reject(
        token: _token!,
        privilegedHeaders: _privilegedHeaders,
        assignmentId: assignmentId,
        reason: 'Rejected from mobile review queue.',
      );
      await load();
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
      notifyListeners();
    }
  }
}
