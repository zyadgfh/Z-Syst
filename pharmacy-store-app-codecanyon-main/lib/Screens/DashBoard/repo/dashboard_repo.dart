import 'dart:convert';

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../model/dashboard_overview_model.dart';
import 'package:http/http.dart' as http;

class DashboardRepo {
  Future<DashboardOverviewModel> dashboardData(String type) async {
    final uri = Uri.parse('${APIConfig.url}/dashboard?duration=$type');
    final token = await getAuthToken();

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token', // Assuming Bearer token format
    });
    print(response.body);
    if (response.statusCode == 200) {
      return DashboardOverviewModel.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to fetch business data ${response.statusCode}');
    }
  }
}
