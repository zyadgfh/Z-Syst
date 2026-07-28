import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import '../model/prediction_model.dart';
import '../model/auto_order_model.dart';
import '../model/inventory_turnover_model.dart';

class PredictionRepo {
  // ========== Prediction Settings ==========

  Future<PredictionSettings?> getSettings() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/predictions/settings');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return PredictionSettings.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getSettings error: $e');
      return null;
    }
  }

  Future<bool> updateSettings(PredictionSettings settings) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/predictions/settings');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.put(
        uri,
        headers: headers,
        body: jsonEncode(settings.toJson()),
      );
      return response.statusCode == 200;
    } catch (e) {
      print('PredictionRepo.updateSettings error: $e');
      return false;
    }
  }

  // ========== Forecasting ==========

  Future<ForecastResult?> forecastProduct(int productId, {int? days}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{};
      if (days != null) params['days'] = days.toString();

      final uri = Uri.parse('${APIConfig.url}/predictions/forecast/$productId')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return ForecastResult.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.forecastProduct error: $e');
      return null;
    }
  }

  Future<List<ForecastResult>> batchForecast(List<int> productIds) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/predictions/batch-forecast');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.post(
        uri,
        headers: headers,
        body: jsonEncode({'product_ids': productIds}),
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return (body['data'] as List)
            .map((x) => ForecastResult.fromJson(x))
            .toList();
      }
      return [];
    } catch (e) {
      print('PredictionRepo.batchForecast error: $e');
      return [];
    }
  }

  Future<List<ForecastResult>> forecastAll() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/predictions/forecast-all');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.post(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return (body['data']['results'] as List)
            .map((x) => ForecastResult.fromJson(x))
            .toList();
      }
      return [];
    } catch (e) {
      print('PredictionRepo.forecastAll error: $e');
      return [];
    }
  }

  // ========== Demand Report ==========

  Future<DemandReport?> getDemandReport({String period = 'daily'}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/predictions/demand-report')
          .replace(queryParameters: {'period': period});
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return DemandReport.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getDemandReport error: $e');
      return null;
    }
  }

  // ========== Reorder Point ==========

  Future<ReorderPoint?> getReorderPoint(int productId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse(
          '${APIConfig.url}/predictions/reorder-point/$productId');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return ReorderPoint.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getReorderPoint error: $e');
      return null;
    }
  }

  // ========== Auto-Order Settings ==========

  Future<Map<String, dynamic>?> getAutoOrderSettings() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/auto-order/settings');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return {
          'rules': body['data'],
          'stats': body['stats'],
        };
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getAutoOrderSettings error: $e');
      return null;
    }
  }

  Future<AutoOrderRule?> getAutoOrderRule(int productId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/auto-order/settings/$productId');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['data'] == null) return null;
        return AutoOrderRule.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getAutoOrderRule error: $e');
      return null;
    }
  }

  Future<bool> updateAutoOrderRule(int productId, AutoOrderRule rule) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/auto-order/settings/$productId');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.put(
        uri,
        headers: headers,
        body: jsonEncode(rule.toJson()),
      );
      return response.statusCode == 200;
    } catch (e) {
      print('PredictionRepo.updateAutoOrderRule error: $e');
      return false;
    }
  }

  // ========== Auto-Order Suggestions ==========

  Future<Map<String, dynamic>?> generateSuggestions() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/auto-order/generate');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.post(uri, headers: headers);
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.generateSuggestions error: $e');
      return null;
    }
  }

  Future<Map<String, dynamic>?> getSuggestions({
    String? status,
    String? priority,
    String? search,
    int page = 1,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'page': page.toString(),
        if (status != null) 'status': status,
        if (priority != null) 'priority': priority,
        if (search != null) 'search': search,
      };

      final uri = Uri.parse('${APIConfig.url}/auto-order/suggestions')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getSuggestions error: $e');
      return null;
    }
  }

  Future<bool> approveSuggestion(int suggestionId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/auto-order/suggestions/$suggestionId/approve');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.post(uri, headers: headers);
      return response.statusCode == 200;
    } catch (e) {
      print('PredictionRepo.approveSuggestion error: $e');
      return false;
    }
  }

  Future<bool> rejectSuggestion(int suggestionId, {String? reason}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/auto-order/suggestions/$suggestionId/reject');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.post(
        uri,
        headers: headers,
        body: jsonEncode({'reason': reason}),
      );
      return response.statusCode == 200;
    } catch (e) {
      print('PredictionRepo.rejectSuggestion error: $e');
      return false;
    }
  }

  Future<Map<String, dynamic>?> confirmSuggestion(int suggestionId,
      {Map<String, dynamic>? purchaseData}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/auto-order/suggestions/$suggestionId/confirm');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response = await http.post(
        uri,
        headers: headers,
        body: jsonEncode(purchaseData ?? {}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.confirmSuggestion error: $e');
      return null;
    }
  }

  Future<Map<String, dynamic>?> getAutoOrderReport() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/auto-order/report');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getAutoOrderReport error: $e');
      return null;
    }
  }

  // ========== Inventory Turnover Analysis ==========

  /// Get inventory turnover summary with key metrics.
  Future<InventoryTurnoverSummary?> getInventoryTurnoverSummary() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/inventory-turnover/summary');
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return InventoryTurnoverSummary.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getInventoryTurnoverSummary error: $e');
      return null;
    }
  }

  /// Generate or get inventory turnover report for a period.
  Future<InventoryTurnoverReport?> getTurnoverReport({
    String reportType = 'monthly',
    String? date,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'report_type': reportType,
        if (date != null) 'date': date,
      };

      final uri = Uri.parse('${APIConfig.url}/inventory-turnover/report')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return InventoryTurnoverReport.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getTurnoverReport error: $e');
      return null;
    }
  }

  /// Get product inventory analysis list.
  Future<ProductAnalysisResponse?> getProductAnalysis({
    String? movement,
    String? abc,
    String? search,
    String? sortBy,
    String? sortDir,
    String reportType = 'monthly',
    int page = 1,
    int perPage = 20,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'page': page.toString(),
        'per_page': perPage.toString(),
        'report_type': reportType,
        if (movement != null) 'movement': movement,
        if (abc != null) 'abc': abc,
        if (search != null) 'search': search,
        if (sortBy != null) 'sort_by': sortBy,
        if (sortDir != null) 'sort_dir': sortDir,
      };

      final uri = Uri.parse('${APIConfig.url}/inventory-turnover/products')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return ProductAnalysisResponse.fromJson(body);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getProductAnalysis error: $e');
      return null;
    }
  }

  /// Get analysis for a specific product.
  Future<Map<String, dynamic>?> getProductTurnoverAnalysis(int productId,
      {String reportType = 'monthly'}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'report_type': reportType,
      };

      final uri = Uri.parse(
              '${APIConfig.url}/inventory-turnover/product/$productId')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getProductTurnoverAnalysis error: $e');
      return null;
    }
  }

  /// Get slow-moving and dead stock products.
  Future<SlowMovingResult?> getSlowMovingProducts({
    String category = 'all',
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'category': category,
      };

      final uri = Uri.parse('${APIConfig.url}/inventory-turnover/slow-moving')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return SlowMovingResult.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getSlowMovingProducts error: $e');
      return null;
    }
  }

  /// Get ABC analysis for products.
  Future<AbcAnalysisResult?> getAbcAnalysis({String period = 'monthly'}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'period': period,
      };

      final uri = Uri.parse('${APIConfig.url}/inventory-turnover/abc-analysis')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return AbcAnalysisResult.fromJson(body['data']);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getAbcAnalysis error: $e');
      return null;
    }
  }

  /// Get historical trends for inventory turnover.
  Future<Map<String, dynamic>?> getTurnoverTrends({
    String reportType = 'monthly',
    int limit = 6,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final params = <String, String>{
        'report_type': reportType,
        'limit': limit.toString(),
      };

      final uri = Uri.parse('${APIConfig.url}/inventory-turnover/trends')
          .replace(queryParameters: params);
      final headers = {'Accept': 'application/json', 'Authorization': token};

      final response = await http.get(uri, headers: headers);
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('PredictionRepo.getTurnoverTrends error: $e');
      return null;
    }
  }
}
