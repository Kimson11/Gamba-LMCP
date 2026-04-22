import 'package:flutter/material.dart';

import '../core/network/api_client.dart';
import '../core/storage/shared_preferences_session_storage.dart';
import '../core/storage/sync_queue_storage.dart';
import '../features/auth/data/auth_repository_impl.dart';
import '../features/auth/presentation/controllers/auth_controller.dart';
import 'app.dart';

Future<Widget> bootstrap() async {
  WidgetsFlutterBinding.ensureInitialized();

  final sessionStorage = SharedPreferencesSessionStorage();
  final syncQueueStorage = SharedPreferencesSyncQueueStorage();
  final apiClient = ApiClient();

  final authRepository = AuthRepositoryImpl(
    apiClient: apiClient,
    sessionStorage: sessionStorage,
  );

  final authController = AuthController(authRepository: authRepository);

  await authController.restoreSession();

  return GambaApp(
    authController: authController,
    apiClient: apiClient,
    sessionStorage: sessionStorage,
    syncQueueStorage: syncQueueStorage,
  );
}
