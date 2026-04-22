import '../../../core/network/api_client.dart';
import '../../../core/storage/session_storage.dart';
import '../domain/entities/auth_session.dart';
import '../domain/repositories/auth_repository.dart';

class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl({
    required this.apiClient,
    required this.sessionStorage,
  });

  final ApiClient apiClient;
  final SessionStorage sessionStorage;

  @override
  Future<AuthSession> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final response = await apiClient.post(
      '/auth/login',
      body: {
        'email': email,
        'password': password,
        'device_name': deviceName,
      },
    );

    final session = AuthSession.fromLoginResponse(response);

    await sessionStorage.write(session.toJson());

    return session;
  }

  @override
  Future<void> logout(String token) async {
    await apiClient.post('/auth/logout', token: token);
    await sessionStorage.clear();
  }

  @override
  Future<AuthSession?> restoreSession() async {
    final stored = await sessionStorage.read();

    if (stored == null) {
      return null;
    }

    final partialSession = AuthSession.fromStorage(stored);
    final response = await apiClient.get('/auth/me', token: partialSession.token);
    final refreshedSession = AuthSession.fromCurrentUserResponse(response, partialSession.token);

    await sessionStorage.write(refreshedSession.toJson());

    return refreshedSession;
  }
}
