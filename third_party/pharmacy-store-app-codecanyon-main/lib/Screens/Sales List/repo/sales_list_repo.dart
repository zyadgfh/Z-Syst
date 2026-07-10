import 'dart:convert';
import 'dart:io';

import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:http/http.dart' as http;

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../../Sales/Model/sales_details_model.dart';
import '../model/sales_list_paginated_data_model.dart';

class SalesListRepo {
  ///______Get_Sales_List_______________________________________________________
  Future<SalesListPaginatedDataModel?> getSalesList({
    String? nextPage,
    String? search,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/sales?search=${search ?? ''}&page=$nextPage');
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      if (response.statusCode == 200) {
        final data = SalesListPaginatedDataModel.fromJson(jsonDecode(response.body));

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

  ///______Get_sales_details_______________________________________________________
  Future<SalesDetailsModel?> getSalesDetails({required num id}) async {
    try {
      String token = await getAuthToken() ?? '';
      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/sales/$id');

      final headers = {'Accept': 'application/json', 'Authorization': token};

      http.Response response = await http.get(url, headers: headers);
      print(response.statusCode);
      print('rrrrrrr${response.body}');
      if (response.statusCode == 200) {
        final data = SalesDetailsModel.fromJson(jsonDecode(response.body));

        return data;
      } else {
        EasyLoading.showError('Something went wrong');
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
