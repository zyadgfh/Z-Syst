import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

const _secureStorage = FlutterSecureStorage();

Future<String> getAuthToken() async {
  // Prefer secure storage, fallback to SharedPreferences for migration
  String? token = await _secureStorage.read(key: 'auth_token');
  token ??= (await SharedPreferences.getInstance()).getString('token');
  if (token == null || token.isEmpty) return '';
  return token.startsWith('Bearer ') ? token : 'Bearer $token';
}

Future<void> saveUserData({required String token}) async {
  final raw = token.startsWith('Bearer ') ? token.substring(7) : token;
  await _secureStorage.write(key: 'auth_token', value: raw);
  // Keep SharedPreferences for backwards compat until fully migrated
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('token', raw);
}

Future<void> clearAuthToken() async {
  await _secureStorage.delete(key: 'auth_token');
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove('token');
}
