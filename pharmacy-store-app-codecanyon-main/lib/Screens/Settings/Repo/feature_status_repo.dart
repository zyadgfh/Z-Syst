import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/model/feature_status_model.dart';

class FeatureStatusRepo {
  Future<List<FeatureStatusModel>> fetchFeatures() async {
    final uri = Uri.parse('${APIConfig.url}/features');
    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': await getAuthToken(),
    });

    if (response.statusCode == 200) {
      final parsed = jsonDecode(response.body);
      final data = parsed['data'];
      if (data is List) {
        return data.map((item) => FeatureStatusModel.fromJson(item as Map<String, dynamic>)).toList();
      }
      return [];
    }

    throw Exception('Failed to fetch features (${response.statusCode})');
  }

  Future<String> triggerBackup() async {
    final uri = Uri.parse('${APIConfig.url}/backup');
    final response = await http.post(uri, headers: {
      'Accept': 'application/json',
      'Authorization': await getAuthToken(),
    });

    if (response.statusCode == 200) {
      final parsed = jsonDecode(response.body);
      return parsed['message']?.toString() ?? 'Backup completed successfully.';
    }

    throw Exception('Backup failed (${response.statusCode})');
  }
}
