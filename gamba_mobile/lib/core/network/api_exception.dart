class ApiException implements Exception {
  ApiException({
    required this.message,
    required this.code,
    required this.statusCode,
    this.errors = const {},
  });

  final String message;
  final String code;
  final int statusCode;
  final Map<String, dynamic> errors;

  bool get isUnauthorized => statusCode == 401;

  bool get isForbidden => statusCode == 403;

  bool get isValidation => statusCode == 422;

  @override
  String toString() {
    return 'ApiException(statusCode: $statusCode, code: $code, message: $message)';
  }
}
