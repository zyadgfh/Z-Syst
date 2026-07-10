// Recent Purchase Transaction
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../model/banner_model.dart';

class BannerRepository {
  Future<BannerModel> getBannerReportList() async {
    final uri = Uri.parse('${APIConfig.url}/banners');
    final token = await getAuthToken();

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    });
    if (response.statusCode == 200) {
      print(response.body);
      return BannerModel.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to fetch business data');
    }
  }
}
