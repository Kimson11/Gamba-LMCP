import 'package:flutter/material.dart';

import '../core/constants/env.dart';
import '../core/network/api_client.dart';
import '../core/storage/session_storage.dart';
import '../core/storage/sync_queue_storage.dart';
import '../features/admin_dashboard/data/admin_dashboard_repository_impl.dart';
import '../features/admin_dashboard/presentation/controllers/admin_dashboard_controller.dart';
import '../features/admin_dashboard/presentation/screens/admin_dashboard_screen.dart';
import '../features/auth/domain/entities/auth_session.dart';
import '../features/auth/presentation/controllers/auth_controller.dart';
import '../features/auth/presentation/screens/login_screen.dart';
import '../features/member_assignments/data/member_assignment_repository_impl.dart';
import '../features/member_assignments/presentation/controllers/member_assignment_controller.dart';
import '../features/member_assignments/presentation/screens/member_assignment_queue_screen.dart';
import '../features/member_home/data/member_home_repository_impl.dart';
import '../features/member_home/presentation/controllers/member_home_controller.dart';
import '../features/member_home/presentation/screens/member_home_screen.dart';
import '../features/milk_logs/data/milk_log_repository_impl.dart';
import '../features/milk_logs/presentation/controllers/log_milk_controller.dart';
import '../features/milk_logs/presentation/screens/log_milk_screen.dart';
import '../features/notifications/data/notifications_repository_impl.dart';
import '../features/notifications/presentation/controllers/notifications_controller.dart';
import '../features/notifications/presentation/screens/notifications_screen.dart';
import '../features/sync_center/data/sync_repository_impl.dart';
import '../features/sync_center/presentation/controllers/sync_center_controller.dart';
import '../features/sync_center/presentation/screens/sync_center_screen.dart';
import 'theme/app_theme.dart';

class GambaApp extends StatelessWidget {
  GambaApp({
    required this.authController,
    required this.apiClient,
    required this.sessionStorage,
    required this.syncQueueStorage,
    this.eagerLoadShellData = true,
    super.key,
  });

  final AuthController authController;
  final ApiClient apiClient;
  final SessionStorage sessionStorage;
  final SyncQueueStorage syncQueueStorage;
  final bool eagerLoadShellData;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: authController,
      builder: (context, _) {
        return MaterialApp(
          debugShowCheckedModeBanner: false,
          title: 'Gamba',
          theme: AppTheme.build(),
          home: _buildHome(),
        );
      },
    );
  }

  Widget _buildHome() {
    switch (authController.status) {
      case AuthStatus.restoring:
        return const _AppLoadingScreen();
      case AuthStatus.unauthenticated:
      case AuthStatus.failure:
        return LoginScreen(controller: authController);
      case AuthStatus.authenticated:
        final session = authController.session;

        if (session == null) {
          return LoginScreen(controller: authController);
        }

        return RoleShell(
          session: session,
          authController: authController,
          apiClient: apiClient,
          syncQueueStorage: syncQueueStorage,
          eagerLoadData: eagerLoadShellData,
        );
    }
  }
}

class RoleShell extends StatefulWidget {
  const RoleShell({
    required this.session,
    required this.authController,
    required this.apiClient,
    required this.syncQueueStorage,
    this.eagerLoadData = true,
    super.key,
  });

  final AuthSession session;
  final AuthController authController;
  final ApiClient apiClient;
  final SyncQueueStorage syncQueueStorage;
  final bool eagerLoadData;

  @override
  State<RoleShell> createState() => _RoleShellState();
}

class _RoleShellState extends State<RoleShell> {
  int _selectedIndex = 0;

  late final MemberHomeController _memberHomeController;
  late final LogMilkController _logMilkController;
  late final SyncCenterController _syncCenterController;
  late final NotificationsController _notificationsController;
  late final AdminDashboardController _adminDashboardController;
  late final MemberAssignmentController _memberAssignmentController;

  @override
  void initState() {
    super.initState();

    final isAdmin = widget.session.user.role.isAdminLike;

    _memberHomeController = MemberHomeController(
      repository: MemberHomeRepositoryImpl(apiClient: widget.apiClient),
    )..bindToken(widget.session.token);

    _logMilkController = LogMilkController(
      repository: MilkLogRepositoryImpl(apiClient: widget.apiClient),
      session: widget.session,
    );

    _syncCenterController = SyncCenterController(
      repository: SyncRepositoryImpl(apiClient: widget.apiClient),
      queueStorage: widget.syncQueueStorage,
    )..bindToken(widget.session.token);

    _notificationsController = NotificationsController(
      repository: NotificationsRepositoryImpl(apiClient: widget.apiClient),
    )..bindToken(widget.session.token);

    _adminDashboardController = AdminDashboardController(
      repository: AdminDashboardRepositoryImpl(apiClient: widget.apiClient),
    )..bindSession(
        token: widget.session.token,
        privilegedHeaders: _privilegedHeaders,
      );

    _memberAssignmentController = MemberAssignmentController(
      repository: MemberAssignmentRepositoryImpl(apiClient: widget.apiClient),
    )..bindSession(
        token: widget.session.token,
        privilegedHeaders: _privilegedHeaders,
      );

    if (!widget.eagerLoadData) {
      return;
    }

    _notificationsController.load();

    if (isAdmin) {
      _adminDashboardController.load();
      _memberAssignmentController.load();
      return;
    }

    _memberHomeController.load();
    _syncCenterController.load();
  }

  @override
  void dispose() {
    _memberHomeController.dispose();
    _logMilkController.dispose();
    _syncCenterController.dispose();
    _notificationsController.dispose();
    _adminDashboardController.dispose();
    _memberAssignmentController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isAdmin = widget.session.user.role.isAdminLike;
    final destinations = isAdmin ? _adminDestinations : _memberDestinations;
    final body = isAdmin ? _buildAdminBody() : _buildMemberBody();

    return Scaffold(
      appBar: AppBar(
        title: Text(isAdmin ? 'Gamba Admin' : 'Gamba'),
        actions: [
          IconButton(
            onPressed: widget.authController.logout,
            icon: const Icon(Icons.logout),
            tooltip: 'Logout',
          ),
        ],
      ),
      body: body,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (index) {
          setState(() {
            _selectedIndex = index;
          });
        },
        destinations: destinations,
      ),
    );
  }

  Widget _buildMemberBody() {
    switch (_selectedIndex) {
      case 0:
        return MemberHomeScreen(controller: _memberHomeController);
      case 1:
        return LogMilkScreen(
          controller: _logMilkController,
          onLogged: () {
            _memberHomeController.load();
            _syncCenterController.load();
          },
        );
      case 2:
        return SyncCenterScreen(controller: _syncCenterController);
      case 3:
        return NotificationsScreen(controller: _notificationsController);
      default:
        return MemberHomeScreen(controller: _memberHomeController);
    }
  }

  Widget _buildAdminBody() {
    switch (_selectedIndex) {
      case 0:
        return AdminDashboardScreen(controller: _adminDashboardController);
      case 1:
        return MemberAssignmentQueueScreen(controller: _memberAssignmentController);
      case 2:
        return NotificationsScreen(controller: _notificationsController);
      default:
        return AdminDashboardScreen(controller: _adminDashboardController);
    }
  }

  List<NavigationDestination> get _memberDestinations => const [
        NavigationDestination(
          icon: Icon(Icons.home_outlined),
          selectedIcon: Icon(Icons.home),
          label: 'Home',
        ),
        NavigationDestination(
          icon: Icon(Icons.water_drop_outlined),
          selectedIcon: Icon(Icons.water_drop),
          label: 'Log Milk',
        ),
        NavigationDestination(
          icon: Icon(Icons.sync_outlined),
          selectedIcon: Icon(Icons.sync),
          label: 'Sync',
        ),
        NavigationDestination(
          icon: Icon(Icons.notifications_outlined),
          selectedIcon: Icon(Icons.notifications),
          label: 'Alerts',
        ),
      ];

  List<NavigationDestination> get _adminDestinations => const [
        NavigationDestination(
          icon: Icon(Icons.dashboard_outlined),
          selectedIcon: Icon(Icons.dashboard),
          label: 'Dashboard',
        ),
        NavigationDestination(
          icon: Icon(Icons.approval_outlined),
          selectedIcon: Icon(Icons.approval),
          label: 'Approvals',
        ),
        NavigationDestination(
          icon: Icon(Icons.notifications_outlined),
          selectedIcon: Icon(Icons.notifications),
          label: 'Alerts',
        ),
      ];

  Map<String, String> get _privilegedHeaders {
    if (Env.privilegedTrustedDeviceId.isEmpty || !Env.privilegedMfaVerified) {
      return const {};
    }

    return {
      'X-MFA-Verified': 'true',
      'X-Trusted-Device-Id': Env.privilegedTrustedDeviceId,
    };
  }
}

class _AppLoadingScreen extends StatelessWidget {
  const _AppLoadingScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: CircularProgressIndicator(),
      ),
    );
  }
}
