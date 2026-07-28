import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:http/http.dart' as http;

import '../../../../app_config/api_config.dart';
import '../../profile_setup_screen.dart';
import '../../success_screen.dart';

class PhoneAuthRepo {
  Future<bool> sentOTP({
    required String phoneNumber,
    required BuildContext context,
  }) async {
    final url = Uri.parse('${APIConfig.url}/send-otp');
    final body = {
      'phone': phoneNumber,
    };
    final headers = {
      'Accept': 'application/json',
    };

    try {
      final response = await http.post(url, headers: headers, body: body);

      final errorData = jsonDecode(response.body);
      EasyLoading.dismiss();
      if (!context.mounted) return false;

      if (response.statusCode == 200) {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(errorData['message'])));
        return true;
      } else {
        EasyLoading.showError(errorData['message']);
      }
    } catch (error) {
      EasyLoading.showError('Network error: Please try again');
    } finally {}

    return false;
  }

  Future<void> submitOTP({
    required String phoneNumber,
    required String otp,
    required BuildContext context,
  }) async {
    final url = Uri.parse('${APIConfig.url}/submit-otp');
    final body = {
      'phone': phoneNumber,
      'otp': otp,
    };
    final headers = {
      'Accept': 'application/json',
    };

    try {
      final response = await http.post(url, headers: headers, body: body);

      final data = jsonDecode(response.body);
      debugPrint('submitOTP status: ${response.statusCode}');

      EasyLoading.dismiss();
      if (!context.mounted) return;

      if (response.statusCode == 200) {
        if (!context.mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'])));
        bool isSetup = data['is_setup'] ?? false;
        if (isSetup) {
          Navigator.push(context, MaterialPageRoute(builder: (context) => SuccessScreen(email: 'phone')));
        } else {
          Navigator.push(context, MaterialPageRoute(builder: (context) => ProfileSetup()));
        }
      } else {
        EasyLoading.showError(data['message']);
      }
    } catch (error) {
      debugPrint('submitOTP error: $error');
      EasyLoading.showError('Network error: Please try again');
    } finally {}
  }
}
