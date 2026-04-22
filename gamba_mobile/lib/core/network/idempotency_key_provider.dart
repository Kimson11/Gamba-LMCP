class IdempotencyKeyProvider {
  String create(String prefix) {
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final nonce = DateTime.now().microsecondsSinceEpoch.toRadixString(36);

    return '$prefix-$timestamp-$nonce';
  }
}
