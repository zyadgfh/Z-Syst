import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../model/loss_profit_model.dart';

class LossProfitRepo {
  //--- Loss Profit Report
  Future<LossProfitModel?> getPurchaseReportList({
    String? nextPage,
    String? search,
    String? fromDate,
    String? toDate,
    String? status,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse(
        '${APIConfig.url}/loss-profit-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&payment_status=${status ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      print(url);
      print(response.body);
      if (response.statusCode == 200) {
        final data = LossProfitModel.fromJson(jsonDecode(response.body));

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
