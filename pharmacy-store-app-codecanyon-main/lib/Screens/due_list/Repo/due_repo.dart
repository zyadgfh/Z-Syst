// ignore_for_file: unused_local_variable
import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;

import '../../../app_config/api_config.dart';
import '../../../Provider/profile_provider.dart';
import '../../../Provider/transactions_provider.dart';
import '../../../Repository/constant_functions.dart';
import '../../Customers/Provider/customer_provider.dart';
import '../Model/collected_due_list_model.dart';
import '../Model/due_collection_invoice_model.dart';
import '../Model/due_collection_model.dart';
import '../Model/due_invoice_list_model.dart';
import '../Model/due_list_model.dart';
import '../Providers/due_provider.dart';
import '../due_collection_screen.dart';

class DueRepo {
  // Fetch due list
  Future<DueListModel?> getDueList({
    String? nextPage,
    String? search,
    String? status,
  }) async {
    try {
      String token = await getAuthToken();

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse(
        '${APIConfig.url}/dues-list?search=${search ?? ''}&type=${status ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        final data = DueListModel.fromJson(jsonDecode(response.body));

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

  // Fetch Due Collected list
  Future<CollectedDueListModel?> getCollectedDueList({
    String? nextPage,
    String? search,
    String? status,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse(
        '${APIConfig.url}/dues?search=${search ?? ''}&type=${status ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);
      print(response.statusCode);
      if (response.statusCode == 200) {
        final data = CollectedDueListModel.fromJson(jsonDecode(response.body));

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

  // Fetch due invoice list
  Future<DueInvoiceListModel> fetchAllDueInvoiceList({required num id}) async {
    final uri = Uri.parse('${APIConfig.url}/invoices?party_id=$id');

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': await getAuthToken(),
    });

    print(response.statusCode);
    print('---invoice aa${response.body}');

    if (response.statusCode == 200) {
      final parsedData = jsonDecode(response.body);
      print('---invoice bb${response.body}');
      return DueInvoiceListModel.fromJson(parsedData);
    } else {
      throw Exception('Failed to fetch Sales List');
    }
  }

  // due collects repo
  Future<bool> collectDue({
    required DueCollectModel data,
  }) async {
    final url = Uri.parse('${APIConfig.url}/dues');
    String token = await getAuthToken() ?? '';
    final headers = {
      'Accept': 'application/json',
      'Authorization': token,
    };
    final requestBody = {
      "party_id": data.partyId.toString() ?? '',
      "invoiceNumber": data.invoiceNumber ?? '',
      "paymentDate": data.paymentDate?.toString() ?? '',
      "paymentType": data.paymentType.toString() ?? '',
      "payDueAmount": data.payDueAmount.toString() ?? '',
    };
    try {
      final response = await http.post(url, headers: headers, body: requestBody);
      if (response.statusCode == 200) {
        return true;
      } else {
        final data = jsonDecode(response.body);
        EasyLoading.showError(data['message'] ?? 'Unknown error');
        return false;
      }
    } catch (e) {
      EasyLoading.showError('Error: ${e.toString()}');
      return false;
    }
  }

  Future<List<DueCollection>> fetchDueCollectionList() async {
    final uri = Uri.parse('${APIConfig.url}/dues');

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': await getAuthToken(),
    });

    if (response.statusCode == 200) {
      final parsedData = jsonDecode(response.body) as Map<String, dynamic>;

      final dueList = parsedData['data'] as List<dynamic>;
      return dueList.map((due) => DueCollection.fromJson(due)).toList();
    } else {
      throw Exception('Failed to fetch Due List');
    }
  }

  Future<DueCollectionInvoice> fetchDueInvoiceList({required int id}) async {
    final uri = Uri.parse('${APIConfig.url}/invoices?party_id=$id');

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': await getAuthToken(),
    });

    if (response.statusCode == 200) {
      final parsedData = jsonDecode(response.body);
      return DueCollectionInvoice.fromJson(parsedData['data']);
    } else {
      throw Exception('Failed to fetch Sales List');
    }
  }

  Future<DueCollection?> dueCollect({
    required WidgetRef ref,
    required BuildContext context,
    required num partyId,
    required String? invoiceNumber,
    required String paymentDate,
    required String paymentType,
    required num payDueAmount,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/dues');
    final requestBody = jsonEncode({
      'party_id': partyId,
      'invoiceNumber': invoiceNumber,
      'paymentDate': paymentDate,
      'paymentType': paymentType,
      'payDueAmount': payDueAmount,
    });

    try {
      var responseData = await http.post(
        uri,
        headers: {"Accept": 'application/json', 'Authorization': await getAuthToken(), 'Content-Type': 'application/json'},
        body: requestBody,
      );

      final parsedData = jsonDecode(responseData.body);

      if (responseData.statusCode == 200) {
        EasyLoading.showSuccess('Collected successful!');

        var refreshParty = ref.refresh(partiesProvider);
        var purchaseTransactionRefresh = ref.refresh(purchaseTransactionProvider);
        var salesTransactionRefresh = ref.refresh(salesTransactionProvider);
        var businessInfoRefresh = ref.refresh(businessInfoProvider);
        var dueInvoiceListRefresh = ref.refresh(dueInvoiceListProvider(partyId.round()));
        var dueCollectionListRefresh = ref.refresh(dueCollectionListProvider);
        var data = ref.refresh(summaryInfoProvider);

        return DueCollection.fromJson(parsedData['data']);
        // Navigator.pop(context);
        // return PurchaseTransaction.fromJson(parsedData);
      } else {
        EasyLoading.dismiss().then(
          (value) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Purchase creation failed: ${parsedData['message']}'))),
        );
        return null;
      }
    } catch (error) {
      EasyLoading.dismiss().then(
        (value) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error'))),
      );
      return null;
    }
  }
}
