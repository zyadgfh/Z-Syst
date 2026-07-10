import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';

class SalesRepository {
  final String baseUrl = "https://acnoopharmacy.acnoo.com/api/v1";

  Future<http.Response> postSale(Map<String, dynamic> saleData) async {
    final url = Uri.parse("$baseUrl/purchase");

    try {
      final response = await http.post(
        url,
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "Authorization": "Bearer ${await getAuthToken()}",
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
