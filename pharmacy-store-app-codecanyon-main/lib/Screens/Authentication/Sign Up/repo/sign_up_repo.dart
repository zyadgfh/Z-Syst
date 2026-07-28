import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';

import 'package:http/http.dart' as http;

import '../../../../app_config/api_config.dart';
import '../../../../Repository/constant_functions.dart';

class SignUpRepo {
  Future<bool> signUp({required String name, required String email, required String password, required BuildContext context}) async {
    final url = Uri.parse('${APIConfig.url}/sign-up');

    final body = {
      'name': name,
      'email': email,
      'password': password,
    };
    final headers = {
      'Accept': 'application/json',
    };

    try {
      final response = await http.post(url, headers: headers, body: body);

      final responseData = jsonDecode(response.body);
      EasyLoading.dismiss();
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
      debugPrint('SignUp error: $error');
      if (!context.mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Network error: Please try again')));
    } finally {}

    return false;
  }

  Future<bool> verifyOTP({required String email, required String otp, required BuildContext context}) async {
    final url = Uri.parse('${APIConfig.url}/submit-otp');

    final body = {
      'email': email,
      'otp': otp,
    };
    final headers = {
      'Accept': 'application/json',
    };

    try {
      final response = await http.post(url, headers: headers, body: body);

      final responseData = jsonDecode(response.body);
      debugPrint('Verify OTP response: ${response.body}');
      EasyLoading.dismiss();
      if (!context.mounted) return false;

      if (response.statusCode == 200) {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));

        String? token = responseData['token'];
        if (token != null) {
          await saveUserData(token: token);
        }

        return true;
      } else {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['error'])));
      }
    } catch (error) {
      debugPrint('VerifyOTP error: $error');
      if (!context.mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Network error: Please try again')));
    } finally {}

    return false;
  }

  Future<bool> resendOTP({required String email, required BuildContext context}) async {
    final url = Uri.parse('${APIConfig.url}/resend-otp');

    final body = {
      'email': email,
    };
    final headers = {
      'Accept': 'application/json',
    };

    try {
      final response = await http.post(url, headers: headers, body: body);

      final responseData = jsonDecode(response.body);
      EasyLoading.dismiss();
      if (!context.mounted) return false;

      if (response.statusCode == 200) {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));
        return true;
      } else {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['error'])));
      }
    } catch (error) {
      debugPrint('ResendOTP error: $error');
      if (!context.mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Network error: Please try again')));
    } finally {}

    return false;
  }
}
