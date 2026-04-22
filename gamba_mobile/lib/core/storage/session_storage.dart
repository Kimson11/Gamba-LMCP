abstract class SessionStorage {
  Future<Map<String, dynamic>?> read();

  Future<void> write(Map<String, dynamic> session);

  Future<void> clear();
}
