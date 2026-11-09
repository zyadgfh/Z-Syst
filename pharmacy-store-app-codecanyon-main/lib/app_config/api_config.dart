class APIConfig {
  // ── Z-Syst backend ─────────────────────────────────────────────────
  // Production: your deployed Z-Syst tenant (change before release).
  static const String domain = 'https://YOUR-Z-SYST-DOMAIN/';

  // Local development: the Z-Syst php-wasm runner on the host machine
  // (Android emulator reaches the host at 10.0.2.2; iOS sim uses localhost).
  static const String devDomain = 'http://10.0.2.2:8080/';

  // `flutter run --dart-define=ZSYST_DEV=true` targets the local runner.
  static bool get useDev => const bool.fromEnvironment('ZSYST_DEV');

  static String get effectiveDomain => useDev ? devDomain : domain;

  // route url
  static String get url => '${effectiveDomain}api/v1';
}
