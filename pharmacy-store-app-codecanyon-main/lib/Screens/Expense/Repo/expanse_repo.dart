import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;
import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../Model/expense_modle.dart';

class ExpenseRepo {
  // Fetch All Expense List
  Future<ExpenseModel?> fetchExpense({
    String? nextPage,
  }) async {
    try {
      String token = await getAuthToken() ?? '';
      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/expenses?page=$nextPage');
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        final data = ExpenseModel.fromJson(jsonDecode(response.body));

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

  // Create Expense
  Future<void> createExpense({
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
    final uri = Uri.parse('${APIConfig.url}/expenses');
    final requestBody = jsonEncode({
      'amount': amount,
      'expense_category_id': expenseCategoryId,
      'expanseFor': expanseFor,
      'referenceNo': referenceNo,
      'expenseDate': expenseDate,
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
        return;
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Expense creation failed: ${parsedData['message']}')));
        return;
      }
    } catch (error) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error')));
    }
  }

  // Update Expense
  Future<void> updateExpense({
    required String id,
    required num amount,
    required num expenseCategoryId,
    required String expanseFor,
    required String paymentType,
    required String referenceNo,
    required String expenseDate,
    required String note,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/expenses/$id');
    final requestBody = jsonEncode({
      'amount': amount,
      'expense_category_id': expenseCategoryId,
      'expanseFor': expanseFor,
      'referenceNo': referenceNo,
      'expenseDate': expenseDate,
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
        final parsedData = jsonDecode(response.body);
      } else {
        throw Exception('Failed to update expense. Status Code: ${response.statusCode} - ${response.body}');
      }
    } catch (error) {
      print('Error updating expense: $error');
      throw Exception('Error updating expense: $error');
    } finally {
      EasyLoading.dismiss();
    }
  }

  // Delete Expense
  Future<bool> deleteExpense({required String id}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) {
        throw Exception('Authentication token is missing or empty');
      }

      final url = Uri.parse('${APIConfig.url}/expenses/$id');
      final headers = {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };
      final response = await http.delete(url, headers: headers);

      if (response.statusCode == 200) {
        return true;
      } else {
        print('Error deleting expense: ${response.statusCode} - ${response.body}');
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
