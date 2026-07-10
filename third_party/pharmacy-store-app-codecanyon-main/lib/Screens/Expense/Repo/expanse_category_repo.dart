//ignore_for_file: file_names, unused_element, unused_local_variable
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Screens/Expense/Model/expanse_category.dart';
import 'package:mobile_pos/Screens/Expense/Providers/expense_category_proivder.dart';

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';

class ExpanseCategoryRepo {
  Future<List<ExpenseCategoryModel>> fetchAllExpanseCategory() async {
    final uri = Uri.parse('${APIConfig.url}/expense-categories');

    try {
      final response = await http.get(uri, headers: {
        'Accept': 'application/json',
        'Authorization': await getAuthToken(),
      });

      if (response.statusCode == 200) {
        final parsedData = jsonDecode(response.body) as Map<String, dynamic>;
        final categoryList = parsedData['data'] as List<dynamic>;
        return categoryList.map((category) => ExpenseCategoryModel.fromJson(category)).toList();
      } else {
        // Handle specific error cases based on response codes
        throw Exception('Failed to fetch categories: ${response.statusCode}');
      }
    } catch (error) {
      // Handle unexpected errors gracefully
      rethrow; // Re-throw to allow further handling upstream
    }
  }

  Future<void> addExpanseCategory({
    required WidgetRef ref,
    required BuildContext context,
    required String categoryName,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/expense-categories');

    var responseData = await http.post(uri, headers: {
      "Accept": 'application/json',
      'Authorization': await getAuthToken(),
    }, body: {
      'categoryName': categoryName,
    });

    EasyLoading.dismiss();

    try {
      final parsedData = jsonDecode(responseData.body);

      if (responseData.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Added successful!')));
        var data1 = ref.refresh(expanseCategoryProvider);
        // Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Category creation failed: ${parsedData['message']}')));
      }
    } catch (error) {
      // Handle unexpected errors gracefully
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error')));
    }
  }

  // Update Expense Category
  Future<ExpenseCategoryModel> updateExpanseCategory({required String id, required String name}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty ?? true) {
        throw Exception('Authentication token is missing or empty');
      }
      final url = Uri.parse('${APIConfig.url}/expense-categories/$id');
      final headers = {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };

      final body = {
        'categoryName': name,
        '_method': 'put',
      };

      final response = await http.post(url, headers: headers, body: body);

      if (response.statusCode == 200) {
        return ExpenseCategoryModel.fromJson(jsonDecode(response.body));
      } else {
        throw Exception('Failed to update category: ${response.statusCode} - ${response.body}');
      }
    } catch (e) {
      print('Error updating category: $e');
      throw Exception('Error updating category: $e');
    }
  }

  // Delete Expense Category
  Future<bool> deleteExpanseCategory({required String id}) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) {
        throw Exception('Authentication token is missing or empty');
      }

      final url = Uri.parse('${APIConfig.url}/expense-categories/$id');
      final headers = {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };
      final response = await http.delete(url, headers: headers);

      if (response.statusCode == 200) {
        return true;
      } else {
        print('Error deleting category: ${response.statusCode} - ${response.body}');
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
