import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import '../model/expiry_alert_model.dart';

class ExpiryAlertRepo {
  /// Fetch expiry alert statistics (counts per category)
  Future<ExpiryAlertStats?> getStats() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/expiry-alerts/stats');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        final data = ExpiryAlertResponse.fromJson(jsonDecode(response.body));
        return data.data;
      }

      return null;
    } on http.ClientException catch (e) {
      print('ClientException: ${e.message}');
      return null;
    } on SocketException catch (e) {
      print('SocketException: ${e.message}');
      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  /// Fetch paginated list of expiring/expired products
  Future<ExpiryAlertPaginatedData?> getExpiryAlerts({
    String? threshold, // expired, today, 7, 30, 60, 90, 365, all
    String? search,
    int? page,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/expiry-alerts').replace(
        queryParameters: {
          if (threshold != null) 'threshold': threshold,
          if (search != null && search.isNotEmpty) 'search': search,
          if (page != null) 'page': page.toString(),
        },
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        final data = ExpiryAlertListResponse.fromJson(jsonDecode(response.body));
        return data.data;
      }

      return null;
    } on http.ClientException catch (e) {
      print('ClientException: ${e.message}');
      return null;
    } on SocketException catch (e) {
      print('SocketException: ${e.message}');
      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }
}

