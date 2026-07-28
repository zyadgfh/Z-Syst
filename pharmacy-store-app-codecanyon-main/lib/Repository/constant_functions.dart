import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<String> getAuthToken() async {
  final prefs = await SharedPreferences.getInstance();

  debugPrint("AUTHToken: Bearer ${prefs.getString('token')}");
  return "Bearer ${prefs.getString('token') ?? ''}";
}

Future<void> saveUserData({required String token}) async {
  debugPrint('Saving user token: $token');
  final prefs = await SharedPreferences.getInstance();

  await prefs.setString('token', token);
}
