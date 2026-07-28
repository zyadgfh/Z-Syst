import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Provider/product_provider.dart';
import 'package:mobile_pos/Screens/Sales%20List/provider/sales_details_provider.dart';

import '../../../app_config/api_config.dart';
import '../../../Provider/profile_provider.dart';
import '../../../Provider/transactions_provider.dart';
import '../../../Repository/constant_functions.dart';
import '../../../model/sale_transaction_model.dart';
import '../../Customers/Provider/customer_provider.dart';
import '../Model/sales_data_share_model.dart';
import '../Model/sales_details_model.dart';

class SaleRepo {
  Future<List<SalesTransactionModel>> fetchSalesList({bool? salesReturn}) async {
    final uri = Uri.parse('${APIConfig.url}/sales${(salesReturn ?? false) ? "?returned-sales=true" : ''}');

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': await getAuthToken(),
    });

    if (response.statusCode == 200) {
      final parsedData = jsonDecode(response.body) as Map<String, dynamic>;

      final partyList = parsedData['data'] as List<dynamic>;
      return partyList.map((category) => SalesTransactionModel.fromJson(category)).toList();
      // Parse into Party objects
    } else {
      throw Exception('Failed to fetch Sales List');
    }
  }

  Future<SalesDetailsModel?> createSale({
    required WidgetRef ref,
    required BuildContext context,
    required SalesDataShareModel data,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/sales');

    try {
      var responseData = await http.post(
        uri,
        headers: {"Accept": 'application/json', 'Authorization': await getAuthToken(), 'Content-Type': 'application/json'},
        body: json.encode(data.toJson()),
      );

      final parsedData = jsonDecode(responseData.body);
      print('Sales Create: ${responseData.statusCode}');
      print('Sales Create: ${responseData.body}');
      print('Sales Create: ${json.encode(data.toJson())}');

      if (responseData.statusCode == 200) {
        EasyLoading.showSuccess('Added successful!');
        ref.refresh(productProvider);
        ref.refresh(partiesProvider);
        ref.refresh(salesTransactionProvider);
        ref.refresh(businessInfoProvider);
        ref.refresh(summaryInfoProvider);
        return SalesDetailsModel.fromJson(parsedData);
      } else {
        EasyLoading.dismiss().then(
          (value) {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Sales creation failed: ${parsedData['message']}')));
          },
        );
        return null;
      }
    } catch (error) {
      EasyLoading.dismiss().then(
        (value) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error')));
        },
      );
      return null;
    }
  }

  Future<void> updateSale({
    required WidgetRef ref,
    required BuildContext context,
    required num salesId,
    required SalesDataShareModel data,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/sales/$salesId');

    try {
      var responseData = await http.post(
        uri,
        headers: {"Accept": 'application/json', 'Authorization': await getAuthToken(), 'Content-Type': 'application/json'},
        body: json.encode(data.toJsonForUpdate()),
      );

      final parsedData = jsonDecode(responseData.body);

      if (responseData.statusCode == 200) {
        EasyLoading.showSuccess('Update successful!').then((value) {
          ref.refresh(productProvider);
          ref.refresh(partiesProvider);
          ref.refresh(salesDetailsProvider(salesId));
          ref.refresh(businessInfoProvider);
          Navigator.pop(context);
        });
      } else {
        EasyLoading.dismiss().then((value) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Sales creation failed: ${parsedData['message']}')));
        });
        return;
      }
    } catch (error) {
      EasyLoading.dismiss().then((value) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error')));
      });
      return;
    }
  }
  //
  // Future<void> deletePurchase({
  //   required String id,
  //   required BuildContext context,
  //   required WidgetRef ref,
  // }) async {
  //   final String apiUrl = '${APIConfig.url}/purchase/$id';
  //
  //   try {
  //     final response = await http.delete(
  //       Uri.parse(apiUrl),
  //       headers: {
  //         'Accept': 'application/json',
  //         'Authorization': await getAuthToken(), // Implement your getAuthToken function
  //       },
  //     );
  //
  //     EasyLoading.dismiss();
  //
  //     if (response.statusCode == 200) {
  //       ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Product deleted successfully')));
  //
  //       ref.refresh(productProvider);
  //
  //       Navigator.pop(context); // Assuming you want to close the screen after deletion
  //       Navigator.pop(context); // Assuming you want to close the screen after deletion
  //       // Navigator.pop(context); // Assuming you want to close the screen after deletion
  //     } else {
  //       final parsedData = jsonDecode(response.body);
  //       ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to delete product: ${parsedData['message']}')));
  //     }
  //   } catch (e) {
  //     EasyLoading.dismiss();
  //     ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
  //   }
  // }
}
