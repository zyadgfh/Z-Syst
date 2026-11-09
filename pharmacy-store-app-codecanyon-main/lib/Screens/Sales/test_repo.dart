import 'dart:convert';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';

class SalesRepository {
  // Z-Syst: route through the configured tenant domain.
  final String baseUrl = APIConfig.url;

  Future<http.Response> postSale(Map<String, dynamic> saleData) async {
    final url = Uri.parse("$baseUrl/purchase");

    try {
      final response = await http.post(
        url,
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "Authorization": await getAuthToken(),
        },
        body: json.encode(saleData),
      );

      print(response.statusCode);
      print(response.body);

      if (response.statusCode == 200 || response.statusCode == 201) {
        return response; // Success
      } else {
        throw Exception("Failed to post sale: ${response.body}");
      }
    } catch (e) {
      rethrow;
    }
  }
}
