import 'package:flutter/foundation.dart';

import '../../../../core/network/api_exception.dart';
import '../../data/member_home_repository_impl.dart';
import '../../domain/entities/member_home_summary.dart';

class MemberHomeController extends ChangeNotifier {
  MemberHomeController({
    required this.repository,
  });

  final MemberHomeRepositoryImpl repository;

  MemberHomeSummary? _summary;
  String? _errorMessage;
  bool _isLoading = false;
  String? _token;

  MemberHomeSummary? get summary => _summary;
  String? get errorMessage => _errorMessage;
  bool get isLoading => _isLoading;

  void bindToken(String token) {
    _token = token;
  }

  Future<void> load() async {
    if (_token == null || _token!.isEmpty) {
      return;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _summary = await repository.fetch(_token!);
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
