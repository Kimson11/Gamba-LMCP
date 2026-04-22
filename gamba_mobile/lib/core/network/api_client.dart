import 'dart:async';
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

  List<Uri> get _baseUriCandidates {
    final configured = _baseUri;
    final candidates = <Uri>[configured];

    // Local mobile runtimes often cannot resolve Herd DNS names like gamba.test.
    // Keep the configured URL first, then try emulator/simulator host aliases
    // and common local HTTP fallback ports.
    if (configured.host == 'gamba.test') {
      candidates.add(
        configured.replace(host: '10.0.2.2'),
      );
      candidates.add(
        Uri(
          scheme: 'http',
          host: '10.0.2.2',
          port: 8000,
          path: configured.path,
        ),
      );
      candidates.add(
        Uri(
          scheme: 'http',
          host: '127.0.0.1',
          port: 8000,
          path: configured.path,
        ),
      );
      candidates.add(
        Uri(
          scheme: 'http',
          host: 'localhost',
          port: 8000,
          path: configured.path,
        ),
      );
      candidates.add(
        configured.replace(host: 'localhost'),
      );
    }

    final deduped = <String>{};

    return candidates.where((candidate) {
      final key = candidate.toString();

      if (deduped.contains(key)) {
        return false;
      }

      deduped.add(key);

      return true;
    }).toList(growable: false);
  }

  Future<Map<String, dynamic>> get(
    String path, {
    String? token,
    Map<String, String> headers = const {},
  }) async {
    return _executeWithBaseFallback(path, (baseUri) {
      return _withRetry(() async {
        final request = await _httpClient.getUrl(_resolve(path, baseUri));

        _attachHeaders(
          request,
          token: token,
          headers: headers,
        );

        final response = await request.close().timeout(_kRequestTimeout);

        return _decodeResponse(response);
      }, baseUri);
    });
  }

  Future<Map<String, dynamic>> post(
    String path, {
    String? token,
    Map<String, dynamic> body = const {},
    Map<String, String> headers = const {},
  }) async {
    return _executeWithBaseFallback(path, (baseUri) {
      return _withRetry(() async {
        final request = await _httpClient.postUrl(_resolve(path, baseUri));

        _attachHeaders(
          request,
          token: token,
          headers: headers,
        );

        request.write(jsonEncode(body));

        final response = await request.close().timeout(_kRequestTimeout);

        return _decodeResponse(response);
      }, baseUri);
    });
  }

  Uri _resolve(String path, Uri baseUri) {
    if (path.startsWith('http')) {
      return Uri.parse(path);
    }

    final normalizedPath = path.startsWith('/') ? path.substring(1) : path;
    final basePath = baseUri.path.endsWith('/')
        ? baseUri.path.substring(0, baseUri.path.length - 1)
        : baseUri.path;
    final joinedPath = '$basePath/$normalizedPath'.replaceAll('//', '/');

    return baseUri.replace(path: joinedPath);
  }

  Future<Map<String, dynamic>> _executeWithBaseFallback(
    String path,
    Future<Map<String, dynamic>> Function(Uri baseUri) operation,
  ) async {
    if (path.startsWith('http')) {
      return operation(Uri.parse(path));
    }

    ApiException? lastError;

    for (final baseUri in _baseUriCandidates) {
      try {
        return await operation(baseUri);
      } on ApiException catch (exception) {
        // 4xx/5xx from server should not fallback to another host.
        if (exception.statusCode > 0) {
          rethrow;
        }

        lastError = exception;
      }
    }

    if (lastError != null) {
      throw lastError;
    }

    throw ApiException(
      message:
          'Unable to reach configured API hosts. Check API_BASE_URL and network connectivity.',
      code: 'network_unreachable',
      statusCode: 0,
    );
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

  Future<Map<String, dynamic>> _decodeResponse(
      HttpClientResponse response) async {
    final raw = await utf8.decodeStream(response);
    final decoded = raw.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(raw) as Map<String, dynamic>;

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
    Uri baseUri,
  ) async {
    var attempts = 0;

    while (true) {
      try {
        return await operation();
      } on ApiException {
        rethrow;
      } on SocketException {
        attempts++;

        if (attempts >= _kMaxRetries) {
          throw ApiException(
            message:
                'Unable to reach the server at ${baseUri.host}. Check API_BASE_URL and your network connection.',
            code: 'network_unreachable',
            statusCode: 0,
          );
        }
      } on HandshakeException {
        attempts++;

        if (attempts >= _kMaxRetries) {
          throw ApiException(
            message:
                'Secure connection failed for ${baseUri.host}. Ensure certificate trust settings match your environment.',
            code: 'tls_handshake_failed',
            statusCode: 0,
          );
        }
      } on TimeoutException {
        attempts++;

        if (attempts >= _kMaxRetries) {
          throw ApiException(
            message:
                'Connection to ${baseUri.host} timed out. Verify the backend is running and reachable from this device.',
            code: 'network_timeout',
            statusCode: 0,
          );
        }
      } catch (_) {
        attempts++;

        if (attempts >= _kMaxRetries) {
          throw ApiException(
            message:
                'Request failed before reaching ${baseUri.host}. Check API_BASE_URL and connectivity.',
            code: 'request_transport_failed',
            statusCode: 0,
          );
        }
      }
    }
  }
}
