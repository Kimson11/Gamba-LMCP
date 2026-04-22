import 'dart:convert';
import 'dart:io';

import '../constants/env.dart';
import 'api_exception.dart';

const _kRequestTimeout = Duration(seconds: 30);
const _kMaxRetries = 2;

class ApiClient {
  ApiClient({HttpClient? httpClient})
      : _httpClient = httpClient ?? HttpClient() {
    _httpClient.connectionTimeout = _kRequestTimeout;

    if (Env.allowSelfSignedCertificates) {
      _httpClient.badCertificateCallback = (_, __, ___) => true;
    }
  }

  final HttpClient _httpClient;

  Uri get _baseUri => Uri.parse(Env.apiBaseUrl);

  Future<Map<String, dynamic>> get(
    String path, {
    String? token,
    Map<String, String> headers = const {},
  }) async {
    return _withRetry(() async {
      final request = await _httpClient.getUrl(_resolve(path));

      _attachHeaders(
        request,
        token: token,
        headers: headers,
      );

      final response = await request.close().timeout(_kRequestTimeout);

      return _decodeResponse(response);
    });
  }

  Future<Map<String, dynamic>> post(
    String path, {
    String? token,
    Map<String, dynamic> body = const {},
    Map<String, String> headers = const {},
  }) async {
    return _withRetry(() async {
      final request = await _httpClient.postUrl(_resolve(path));

      _attachHeaders(
        request,
        token: token,
        headers: headers,
      );

      request.write(jsonEncode(body));

      final response = await request.close().timeout(_kRequestTimeout);

      return _decodeResponse(response);
    });
  }

  Uri _resolve(String path) {
    if (path.startsWith('http')) {
      return Uri.parse(path);
    }

    final normalizedPath = path.startsWith('/') ? path.substring(1) : path;

    return _baseUri.resolve(normalizedPath);
  }

  void _attachHeaders(
    HttpClientRequest request, {
    String? token,
    Map<String, String> headers = const {},
  }) {
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');

    if (token != null && token.isNotEmpty) {
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
    }

    headers.forEach(request.headers.set);
  }

  Future<Map<String, dynamic>> _decodeResponse(HttpClientResponse response) async {
    final raw = await utf8.decodeStream(response);
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw) as Map<String, dynamic>;

    if (response.statusCode >= 400) {
      throw ApiException(
        message: (decoded['message'] as String?) ?? 'Request failed.',
        code: (decoded['code'] as String?) ?? 'request_failed',
        statusCode: response.statusCode,
        errors: (decoded['errors'] as Map<String, dynamic>?) ?? const {},
      );
    }

    return decoded;
  }

  Future<Map<String, dynamic>> _withRetry(
    Future<Map<String, dynamic>> Function() operation,
  ) async {
    var attempts = 0;

    while (true) {
      try {
        return await operation();
      } on ApiException {
        rethrow;
      } catch (_) {
        attempts++;

        if (attempts >= _kMaxRetries) {
          rethrow;
        }
      }
    }
  }
}
