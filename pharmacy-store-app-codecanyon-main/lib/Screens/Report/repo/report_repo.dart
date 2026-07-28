import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:mobile_pos/Screens/Report/model/expense_report_model.dart';
import 'package:mobile_pos/Screens/Report/model/income_report_model.dart';

import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../model/PurchaseReportModel.dart';
import '../model/due_report_model.dart';
import '../model/purchase_return_report.dart';
import '../model/sale_return_report_model.dart';
import '../model/sales_report_model.dart';

class ReportRepo {
  //--- Purchase Report
  Future<PurchaseReportModel?> getPurchaseReportList({
    String? nextPage,
    String? search,
    String? fromDate,
    String? toDate,
    String? status,
    num? id,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse(
        '${APIConfig.url}/purchase-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&payment_status=${status ?? ''}&party_id=${id ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      print(url);
      print(response.body);
      if (response.statusCode == 200) {
        final data = PurchaseReportModel.fromJson(jsonDecode(response.body));

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

  //--- Purchase Return Report
  Future<PurchaseReturnReportModel?> getPurchaseReturnReportList({
    String? nextPage,
    String? search,
    String? fromDate,
    String? toDate,
    String? status,
    num? id,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse(
        '${APIConfig.url}/purchase-return-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&party_id=${id ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        final data = PurchaseReturnReportModel.fromJson(jsonDecode(response.body));

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

  //--- Sales Report
  Future<SaleReportModel?> getSalesReportList({
    String? nextPage,
    String? search,
    String? fromDate,
    String? toDate,
    String? status,
    num? id,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse(
          '${APIConfig.url}/sales-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&payment_status=${status ?? ''}&party_id=${id ?? ''}&page=$nextPage');
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      print(url);
      print(response.body);
      if (response.statusCode == 200) {
        final data = SaleReportModel.fromJson(jsonDecode(response.body));

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

  //--- Sales Return Report
  Future<SaleReturnReportModel?> getSalesReturnReportList({
    String? nextPage,
    String? search,
    String? fromDate,
    String? toDate,
    String? status,
    num? id,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse(
        '${APIConfig.url}/sales-return-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&party_id=${id ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      print(url);
      print(response.body);
      if (response.statusCode == 200) {
        final data = SaleReturnReportModel.fromJson(jsonDecode(response.body));

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

  //--- Income Report
  Future<IncomeReportModel?> getIncomeReportList({
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
        '${APIConfig.url}/income-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&payment_status=${status ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      print(url);
      print(response.body);
      if (response.statusCode == 200) {
        final data = IncomeReportModel.fromJson(jsonDecode(response.body));
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

  //--- Expense Report
  Future<ExpenseReportModel?> getExpenseReportList({
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
        '${APIConfig.url}/expense-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&payment_status=${status ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      print(url);
      print(response.body);
      if (response.statusCode == 200) {
        final data = ExpenseReportModel.fromJson(jsonDecode(response.body));
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

  // due Report
  Future<DueReportModel?> getDueReportList({
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
        '${APIConfig.url}/due-collects-report?search=${search ?? ''}&from_date=${fromDate ?? ''}&to_date=${toDate ?? ''}&page=$nextPage',
      );
      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      print(url);
      print(response.body);
      if (response.statusCode == 200) {
        final data = DueReportModel.fromJson(jsonDecode(response.body));

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
