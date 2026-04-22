import 'package:flutter/material.dart';

import 'app/bootstrap.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final app = await bootstrap();

  runApp(app);
}
