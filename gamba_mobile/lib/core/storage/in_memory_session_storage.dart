import 'session_storage.dart';

class InMemorySessionStorage implements SessionStorage {
  Map<String, dynamic>? _session;

  @override
  Future<void> clear() async {
    _session = null;
  }

  @override
  Future<Map<String, dynamic>?> read() async {
    return _session;
  }

  @override
  Future<void> write(Map<String, dynamic> session) async {
    _session = Map<String, dynamic>.from(session);
  }
}
