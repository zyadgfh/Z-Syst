import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Screens/DrugInteraction/model/drug_interaction_model.dart';
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';

class DrugInteractionRepo {
  /// Check interactions for a list of product IDs
  Future<DrugInteractionCheckResponse?> checkInteractions({
    required List<int> productIds,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/drug-interactions/check');
    final token = await getAuthToken();

    final response = await http.post(
      uri,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': token,
      },
      body: jsonEncode({
        'product_ids': productIds,
      }),
    );

    if (response.statusCode == 200) {
      return DrugInteractionCheckResponse.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to check drug interactions: ${response.body}');
    }
  }

  /// Get paginated list of drug interactions
  Future<DrugInteractionListModel?> getDrugInteractions({
    String? search,
    String? nextPage,
    String? severity,
  }) async {
    final queryParams = <String, String>{};
    if (nextPage != null && nextPage.isNotEmpty) queryParams['page'] = nextPage;
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (severity != null && severity.isNotEmpty) queryParams['severity'] = severity;

    final uri = Uri.parse('${APIConfig.url}/drug-interactions').replace(queryParameters: queryParams.isNotEmpty ? queryParams : null);
    final token = await getAuthToken();

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': token,
    });

    if (response.statusCode == 200) {
      return DrugInteractionListModel.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to fetch drug interactions');
    }
  }

  /// Create a new drug interaction
  Future<Map<String, dynamic>> createDrugInteraction({
    required String drugAName,
    required String drugBName,
    required String severity,
    required String description,
    String? mechanism,
    String? recommendation,
    String? source,
    String? category,
  }) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/drug-interactions');

    final response = await http.post(
      uri,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': token,
      },
      body: jsonEncode({
        'drug_a_name': drugAName,
        'drug_b_name': drugBName,
        'severity': severity,
        'description': description,
        'mechanism': mechanism,
        'recommendation': recommendation,
        'source': source,
        'category': category,
      }),
    );

    if (response.statusCode == 200 || response.statusCode == 201) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to create drug interaction');
    }
  }

  /// Update an existing drug interaction
  Future<Map<String, dynamic>> updateDrugInteraction({
    required int id,
    required String drugAName,
    required String drugBName,
    required String severity,
    required String description,
    String? mechanism,
    String? recommendation,
    String? source,
    String? category,
  }) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/drug-interactions/$id');

    final response = await http.put(
      uri,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': token,
      },
      body: jsonEncode({
        'drug_a_name': drugAName,
        'drug_b_name': drugBName,
        'severity': severity,
        'description': description,
        'mechanism': mechanism,
        'recommendation': recommendation,
        'source': source,
        'category': category,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to update drug interaction');
    }
  }

  /// Delete a drug interaction
  Future<Map<String, dynamic>> deleteDrugInteraction(int id) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/drug-interactions/$id');

    final response = await http.delete(uri, headers: {
      'Accept': 'application/json',
      'Authorization': token,
    });

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to delete drug interaction');
    }
  }

  /// Bulk import drug interactions
  Future<Map<String, dynamic>> bulkImport({
    required List<Map<String, dynamic>> interactions,
  }) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/drug-interactions/bulk-import');

    final response = await http.post(
      uri,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': token,
      },
      body: jsonEncode({
        'interactions': interactions,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to bulk import drug interactions');
    }
  }
}

