import '../entities/auth_session.dart';

abstract class AuthRepository {
  Future<AuthSession> login({
    required String email,
    required String password,
    required String deviceName,
  });

  Future<AuthSession?> restoreSession();

  Future<void> logout(String token);
}
