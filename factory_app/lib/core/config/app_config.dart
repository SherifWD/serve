class AppConfig {
  /// Set from --dart-define=API_BASE_URL=https://your-api
  static const apiBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://localhost/api');
}
