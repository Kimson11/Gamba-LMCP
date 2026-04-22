class Env {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://gamba.test/api/v1',
  );

  static const bool allowSelfSignedCertificates = bool.fromEnvironment(
    'ALLOW_SELF_SIGNED_CERTS',
    defaultValue: true,
  );

  static const String privilegedTrustedDeviceId = String.fromEnvironment(
    'PRIVILEGED_TRUSTED_DEVICE_ID',
    defaultValue: '',
  );

  static const bool privilegedMfaVerified = bool.fromEnvironment(
    'PRIVILEGED_MFA_VERIFIED',
    defaultValue: false,
  );
}
