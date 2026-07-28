import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import '../model/fefo_model.dart';

class FefoRepo {
  /// Get FEFO settings
  Future<FefoSettings?> getSettings() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/fefo/settings');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return FefoSettings.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('FefoRepo.getSettings error: $e');
      return null;
    }
  }

  /// Update FEFO settings
  Future<bool> updateSettings(FefoSettings settings) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/fefo/settings');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.put(uri, headers: headers, body: jsonEncode(settings.toJson()));
      return response.statusCode == 200;
    } catch (e) {
      print('FefoRepo.updateSettings error: $e');
      return false;
    }
  }

  /// Get FEFO suggestions for a product
  Future<FefoProductSuggestion?> getProductSuggestions(int productId, {int quantity = 1}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/fefo/suggestions/$productId')
          .replace(queryParameters: {'quantity': quantity.toString()});
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return FefoProductSuggestion.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('FefoRepo.getProductSuggestions error: $e');
      return null;
    }
  }

  /// Get all batches for a product sorted by FEFO
  Future<List<FefoBatchSuggestion>> getProductBatches(int productId, {bool includeExpired = false}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/fefo/product-batches/$productId')
          .replace(queryParameters: {'include_expired': includeExpired.toString()});
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return (body['data']['batches'] as List)
            .map((x) => FefoBatchSuggestion.fromJson(x))
            .toList();
      }
      return [];
    } catch (e) {
      print('FefoRepo.getProductBatches error: $e');
      return [];
    }
  }

  /// Get FEFO sale suggestions for a full cart
  Future<List<FefoProductSuggestion>> getSaleSuggestions(List<Map<String, dynamic>> products) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/fefo/sale-suggestions');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.post(uri, headers: headers, body: jsonEncode({'products': products}));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return (body['data'] as List)
            .map((x) => FefoProductSuggestion.fromJson(x))
            .toList();
      }
      return [];
    } catch (e) {
      print('FefoRepo.getSaleSuggestions error: $e');
      return [];
    }
  }

  /// Get FEFO report
  Future<FefoReport?> getReport() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/fefo/report');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return FefoReport.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('FefoRepo.getReport error: $e');
      return null;
    }
  }

  /// Get FEFO logs
  Future<List<FefoLogItem>> getLogs({int? productId, String? fromDate, String? toDate, int page = 1}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'page': page.toString(),
        if (productId != null) 'product_id': productId.toString(),
        if (fromDate != null) 'from_date': fromDate,
        if (toDate != null) 'to_date': toDate,
      };

      final uri = Uri.parse('${APIConfig.url}/fefo/logs').replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return (body['data']['data'] as List)
            .map((x) => FefoLogItem.fromJson(x))
            .toList();
      }
      return [];
    } catch (e) {
      print('FefoRepo.getLogs error: $e');
      return [];
    }
  }

  /// Remove expired stock automatically
  Future<int> removeExpiredStock() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/fefo/remove-expired');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.post(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data']['batches_affected'] ?? 0;
      }
      return 0;
    } catch (e) {
      print('FefoRepo.removeExpiredStock error: $e');
      return 0;
    }
  }
}

