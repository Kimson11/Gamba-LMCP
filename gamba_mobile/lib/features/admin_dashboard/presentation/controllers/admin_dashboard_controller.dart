import 'package:flutter/foundation.dart';

import '../../../../core/network/api_exception.dart';
import '../../data/admin_dashboard_repository_impl.dart';

class AdminDashboardController extends ChangeNotifier {
  AdminDashboardController({
    required this.repository,
  });

  final AdminDashboardRepositoryImpl repository;

  String? _token;
  Map<String, String> _privilegedHeaders = const {};
  bool _isLoading = false;
  String? _errorMessage;
  AdminDashboardSummary? _summary;

  void bindSession({
    required String token,
    required Map<String, String> privilegedHeaders,
  }) {
    _token = token;
    _privilegedHeaders = privilegedHeaders;
  }

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  AdminDashboardSummary? get summary => _summary;

  Future<void> load() async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _summary = await repository.fetch(
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
}
