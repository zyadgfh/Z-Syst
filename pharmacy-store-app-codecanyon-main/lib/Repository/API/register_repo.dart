import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../../app_config/api_config.dart';

class RegisterRepo {
  Future<bool> registerRepo({required String email, required String password, required String confirmPassword, required BuildContext context}) async {
    final url = Uri.parse('${APIConfig.url}/sign-up');
    final body = {
      'email': email,
      'password': password,
      'password_confirmation': confirmPassword,
    };
    final headers = {
      'Accept': 'application/json',
    };

    try {
      final response = await http.post(url, headers: headers, body: body);

      final responseData = jsonDecode(response.body);
      if (!context.mounted) return false;

      if (response.statusCode == 200) {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));
        return true;
      } else {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));
      }
    } catch (error) {
      debugPrint('Register error: $error');
      if (!context.mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Network error: Please try again')));
    } finally {}

    return false;
  }
}
