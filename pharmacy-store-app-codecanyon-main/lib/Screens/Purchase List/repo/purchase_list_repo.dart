import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../model/PurchaseListModel.dart';
import '../model/purchase_details_model.dart';

class PurchaseListRepo {
  // -----Purchase list
  Future<PurchaseListModel?> getPurchaseList({
    String? nextPage,
    String? search,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/purchase?search=${search ?? ''}&page=$nextPage');
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      if (response.statusCode == 200) {
        final data = PurchaseListModel.fromJson(jsonDecode(response.body));

        return data;
      }

      return null;
    } on http.ClientException catch (e) {
      print(e.message);
      return null;
    } on SocketException catch (e) {
      print(e.message);
      return null;
    }
  }

  //----- Purchase details
  Future<PurchaseDetailsModel?> getPurchaseDetails({required num id}) async {
    try {
      String token = await getAuthToken() ?? '';
      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/purchase/$id');

      final headers = {'Accept': 'application/json', 'Authorization': token};

      http.Response response = await http.get(url, headers: headers);
      print(response.statusCode);
      print('rrrrrrr${response.body}');
      if (response.statusCode == 200) {
        final data = PurchaseDetailsModel.fromJson(jsonDecode(response.body));

        return data;
      }

      return null;
    } on http.ClientException catch (e) {
      print(e.message);
      return null;
    } on SocketException catch (e) {
      print(e.message);
      return null;
    }
  }
}
