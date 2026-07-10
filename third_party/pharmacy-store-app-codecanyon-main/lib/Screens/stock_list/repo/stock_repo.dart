import 'dart:convert';
import 'dart:io';

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../model/stock_list.dart';
import 'package:http/http.dart' as http;

class ProductStockRepo {
  Future<StockListModel?> getStockList({
    String? nextPage,
    String? search,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/stocks?search=${search ?? ''}&page=$nextPage');

      final headers = {'Accept': 'application/json', 'Authorization': token};
      print(url);
      http.Response _response = await http.get(url, headers: headers);

      if (_response.statusCode == 200) {
        final data = StockListModel.fromJson(jsonDecode(_response.body));

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
