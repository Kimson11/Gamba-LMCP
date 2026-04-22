import 'package:flutter/foundation.dart';

import '../../../../core/network/api_exception.dart';
import '../../../auth/domain/entities/auth_session.dart';
import '../../data/milk_log_repository_impl.dart';

class LogMilkController extends ChangeNotifier {
  LogMilkController({
    required this.repository,
    required this.session,
  });

  final MilkLogRepositoryImpl repository;
  final AuthSession session;

  bool _isSubmitting = false;
  String? _errorMessage;
  String? _successMessage;

  bool get isSubmitting => _isSubmitting;
  String? get errorMessage => _errorMessage;
  String? get successMessage => _successMessage;

  Future<bool> submit({
    required double quantityLiters,
    required DateTime productionDate,
  }) async {
    final linkedMember = session.linkedMember;

    if (linkedMember == null) {
      _errorMessage = 'This account does not have a linked member profile.';
      _successMessage = null;
      notifyListeners();
      return false;
    }

    _isSubmitting = true;
    _errorMessage = null;
    _successMessage = null;
    notifyListeners();

    try {
      await repository.create(
        token: session.token,
        memberId: linkedMember.id,
        clusterId: linkedMember.clusterId ?? 0,
        quantityLiters: quantityLiters,
        productionDate: productionDate,
      );

      _successMessage = 'Milk log submitted successfully.';
      return true;
    } on ApiException catch (exception) {
      _errorMessage = exception.message;
      return false;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }
}
