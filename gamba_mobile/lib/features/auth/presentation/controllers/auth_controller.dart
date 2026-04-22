import 'package:flutter/foundation.dart';

import '../../../../core/network/api_exception.dart';
import '../../domain/entities/auth_session.dart';
import '../../domain/repositories/auth_repository.dart';

enum AuthStatus {
  restoring,
  authenticated,
  unauthenticated,
  failure,
}

class AuthController extends ChangeNotifier {
  AuthController({
    required this.authRepository,
  });

  final AuthRepository authRepository;

  AuthStatus _status = AuthStatus.restoring;
  AuthSession? _session;
  String? _errorMessage;
  bool _isSubmitting = false;

  AuthStatus get status => _status;
  AuthSession? get session => _session;
  String? get errorMessage => _errorMessage;
  bool get isSubmitting => _isSubmitting;

  Future<void> restoreSession() async {
    _status = AuthStatus.restoring;
    notifyListeners();

    try {
      _session = await authRepository.restoreSession();
      _status = _session == null
          ? AuthStatus.unauthenticated
          : AuthStatus.authenticated;
      _errorMessage = null;
    } on ApiException catch (exception) {
      _session = null;
      _status = AuthStatus.unauthenticated;
      _errorMessage = exception.message;
    } catch (_) {
      _session = null;
      _status = AuthStatus.unauthenticated;
      _errorMessage = null;
    }

    notifyListeners();
  }

  Future<bool> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    _isSubmitting = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _session = await authRepository.login(
        email: email,
        password: password,
        deviceName: deviceName,
      );
      _status = AuthStatus.authenticated;
      return true;
    } on ApiException catch (exception) {
      _session = null;
      _status = AuthStatus.failure;
      _errorMessage = exception.message;
      return false;
    } catch (_) {
      _session = null;
      _status = AuthStatus.failure;
      _errorMessage =
          'Unable to sign in. Check API connection settings and try again.';
      return false;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    final token = _session?.token;

    if (token != null) {
      try {
        await authRepository.logout(token);
      } catch (_) {}
    }

    _session = null;
    _status = AuthStatus.unauthenticated;
    _errorMessage = null;
    notifyListeners();
  }
}
