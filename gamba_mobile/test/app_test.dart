import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gamba_mob/app/app.dart';
import 'package:gamba_mob/core/network/api_client.dart';
import 'package:gamba_mob/core/storage/in_memory_session_storage.dart';
import 'package:gamba_mob/core/storage/sync_queue_storage.dart';
import 'package:gamba_mob/features/auth/domain/entities/auth_session.dart';
import 'package:gamba_mob/features/auth/domain/repositories/auth_repository.dart';
import 'package:gamba_mob/features/auth/presentation/controllers/auth_controller.dart';

void main() {
  testWidgets('shows login screen when no stored session exists', (tester) async {
    final authController = AuthController(
      authRepository: FakeAuthRepository(restoredSession: null),
    );

    await authController.restoreSession();

    await tester.pumpWidget(
      GambaApp(
        authController: authController,
        apiClient: ApiClient(),
        sessionStorage: InMemorySessionStorage(),
        syncQueueStorage: InMemorySyncQueueStorage(),
        eagerLoadShellData: false,
      ),
    );

    await tester.pump();

    expect(find.text('Sign in'), findsOneWidget);
    expect(find.text('Log Milk'), findsNothing);
    expect(find.text('Dashboard'), findsNothing);
  });

  testWidgets('routes authenticated member users into the member shell', (tester) async {
    final authController = AuthController(
      authRepository: FakeAuthRepository(
        restoredSession: buildSession(
          role: UserRole.member,
          linkedMember: const LinkedMember(
            id: 12,
            cooperativeId: 5,
            clusterId: 9,
            memberNumber: 'MEM-00012',
            status: 'active',
          ),
        ),
      ),
    );

    await authController.restoreSession();

    await tester.pumpWidget(
      GambaApp(
        authController: authController,
        apiClient: ApiClient(),
        sessionStorage: InMemorySessionStorage(),
        syncQueueStorage: InMemorySyncQueueStorage(),
        eagerLoadShellData: false,
      ),
    );

    await tester.pump();

    expect(find.text('Log Milk'), findsOneWidget);
    expect(find.text('Sync'), findsOneWidget);
    expect(find.text('Alerts'), findsOneWidget);
    expect(find.text('Dashboard'), findsNothing);
  });

  testWidgets('routes authenticated admin users into the admin shell', (tester) async {
    final authController = AuthController(
      authRepository: FakeAuthRepository(
        restoredSession: buildSession(role: UserRole.coopAdmin),
      ),
    );

    await authController.restoreSession();

    await tester.pumpWidget(
      GambaApp(
        authController: authController,
        apiClient: ApiClient(),
        sessionStorage: InMemorySessionStorage(),
        syncQueueStorage: InMemorySyncQueueStorage(),
        eagerLoadShellData: false,
      ),
    );

    await tester.pump();

    expect(find.text('Gamba Admin'), findsOneWidget);
    expect(find.text('Dashboard'), findsOneWidget);
    expect(find.text('Approvals'), findsOneWidget);
    expect(find.text('Log Milk'), findsNothing);
  });
}

AuthSession buildSession({
  required UserRole role,
  LinkedMember? linkedMember,
}) {
  return AuthSession(
    token: 'test-token',
    user: AuthUser(
      id: 1,
      name: 'Amina Yusuf',
      email: 'amina@example.com',
      role: role,
    ),
    scopes: const ScopeSummary(
      cooperativeIds: [5],
      clusterIds: [9],
      memberId: 12,
    ),
    linkedMember: linkedMember,
    security: const SessionSecurity(
      mfaEnabled: true,
      privilegedSessionRequired: false,
      trustedDeviceCount: 1,
    ),
  );
}

class FakeAuthRepository implements AuthRepository {
  FakeAuthRepository({
    required this.restoredSession,
  });

  final AuthSession? restoredSession;

  @override
  Future<AuthSession> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    return restoredSession ??
        buildSession(
          role: UserRole.member,
          linkedMember: const LinkedMember(
            id: 12,
            cooperativeId: 5,
            clusterId: 9,
            memberNumber: 'MEM-00012',
            status: 'active',
          ),
        );
  }

  @override
  Future<void> logout(String token) async {}

  @override
  Future<AuthSession?> restoreSession() async {
    return restoredSession;
  }
}
