import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:http/http.dart' as http;

import '../../../../app_config/api_config.dart';
import '../../../../Repository/constant_functions.dart';
import '../../../Home/home.dart';
import '../../Sign Up/verify_email.dart';
import '../../profile_setup_screen.dart';

class LogInRepo {
  Future<bool> logIn({
    required String email,
    required String password,
    required BuildContext context,
  }) async {
    final url = Uri.parse('${APIConfig.url}/sign-in');

    final body = {
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

        bool isSetupDone = responseData['data']['is_setup'];
        if (!isSetupDone) {
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const ProfileSetup()));
        } else {
          await saveUserData(
            token: responseData['data']['token'],
          );
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const Home()));
        }

        return true;
      } else if (response.statusCode == 201) {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => VerifyEmail(
              email: email,
              isFormForgotPass: false,
            ),
          ),
        );

        return true;
      } else {
        if (!context.mounted) return false;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));
      }
    } catch (error) {
      debugPrint('Login error: $error');
      if (!context.mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Network error: Please try again')));
    } finally {}

    return false;
  }
}
