//ignore_for_file: file_names, unused_element, unused_local_variable
import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Provider/profile_provider.dart';

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../Model/income_model.dart';

class IncomeRepo {

  // Fetch all Income list
  Future<IncomeListModel?> fetchIncome({
    String? nextPage,
  }) async {
    try {
      String token = await getAuthToken() ?? '';
      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/incomes?page=$nextPage');
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        final data = IncomeListModel.fromJson(jsonDecode(response.body));

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

  Future<void> createIncome({
    required WidgetRef ref,
    required BuildContext context,
    required num amount,
    required num expenseCategoryId,
    required String expanseFor,
    required String paymentType,
    required String referenceNo,
    required String expenseDate,
    required String note,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/incomes');
    final requestBody = jsonEncode({
      'amount': amount,
      'income_category_id': expenseCategoryId,
      'incomeFor': expanseFor,
      'referenceNo': referenceNo,
      'incomeDate': expenseDate,
      'note': note,
      'paymentType': paymentType,
    });

    try {
      var responseData = await http.post(
        uri,
        headers: {"Accept": 'application/json', 'Authorization': await getAuthToken(), 'Content-Type': 'application/json'},
        body: requestBody,
      );

      final parsedData = jsonDecode(responseData.body);

      EasyLoading.dismiss();

      if (responseData.statusCode == 200) {
        var data2 = ref.refresh(businessInfoProvider);
        ref.refresh(summaryInfoProvider);
        // return PurchaseTransaction.fromJson(parsedData);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Income creation failed: ${parsedData['message']}')));
        return;
      }
    } catch (error) {
      // Handle unexpected errors gracefully
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error')));
      // return null;
    }
  }

  // Update Income
  Future<void> updateIncome({
    required String id,
    required num amount,
    required num incomeCatId,
    required String expanseFor,
    required String paymentType,
    required String referenceNo,
    required String incomeDate,
    required String note,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/incomes/$id');
    final requestBody = jsonEncode({
      'amount': amount,
      'income_category_id': incomeCatId,
      'incomeFor': expanseFor,
      'referenceNo': referenceNo,
      'incomeDate': incomeDate,
      'note': note,
      'paymentType': paymentType,
      '_method': 'put',
    });

    try {
      final authToken = await getAuthToken();
      final response = await http.post(
        uri,
        headers: {
          "Accept": 'application/json',
          'Authorization': authToken,
          'Content-Type': 'application/json',
        },
        body: requestBody,
      );

      if (response.statusCode == 200) {
      } else {
        throw Exception('Failed to update income. Status Code: ${response.statusCode} - ${response.body}');
      }
    } catch (error) {
      print('Error updating income: $error');
      throw Exception('Error updating income: $error');
    } finally {
      EasyLoading.dismiss();
    }
  }

  // Delete Income
  Future<bool> deleteIncome({required String id}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) {
        throw Exception('Authentication token is missing or empty');
      }

      final url = Uri.parse('${APIConfig.url}/incomes/$id');
      final headers = {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };
      final response = await http.delete(url, headers: headers);

      if (response.statusCode == 200) {
        return true;
      } else {
        print('Error deleting income: ${response.statusCode} - ${response.body}');
        return false;
      }
    } catch (error) {
      print('Error during delete operation: $error');
      return false;
    } finally {
      EasyLoading.dismiss();
    }
  }
}
