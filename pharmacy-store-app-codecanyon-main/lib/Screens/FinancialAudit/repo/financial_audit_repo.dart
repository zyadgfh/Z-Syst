import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'model/financial_audit_model.dart';

class FinancialAuditRepo {
  // Fetch paginated list of financial audits
  Future<FinancialAuditListResponse?> getFinancialAudits({
    String? status,
    String? auditType,
    String? search,
    int? page,
    int? perPage,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/financial-audits').replace(
        queryParameters: {
          if (status != null && status.isNotEmpty) 'status': status,
          if (auditType != null && auditType.isNotEmpty)
            'audit_type': auditType,
          if (search != null && search.isNotEmpty) 'search': search,
          if (page != null) 'page': page.toString(),
          if (perPage != null) 'per_page': perPage.toString(),
        },
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return FinancialAuditListResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on http.ClientException catch (e) {
      print('ClientException: ${e.message}');
      return null;
    } on SocketException catch (e) {
      print('SocketException: ${e.message}');
      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Create a new financial audit
  Future<FinancialAuditActionResponse?> createAudit({
    required int businessId,
    required String auditType,
    required String startDate,
    required String endDate,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/financial-audits');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'business_id': businessId,
        'audit_type': auditType,
        'start_date': startDate,
        'end_date': endDate,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return FinancialAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Get audit detail
  Future<FinancialAuditDetailResponse?> getAuditDetail(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/financial-audits/$auditId');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return FinancialAuditDetailResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Start audit
  Future<FinancialAuditActionResponse?> startAudit(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/financial-audits/$auditId/start');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return FinancialAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Execute audit with balances
  Future<FinancialAuditActionResponse?> executeAudit(
    int auditId, {
    required double openingBalance,
    required double closingBalance,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/financial-audits/$auditId/execute');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'opening_balance': openingBalance,
        'closing_balance': closingBalance,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 200) {
        return FinancialAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Complete audit
  Future<FinancialAuditActionResponse?> completeAudit(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/financial-audits/$auditId/complete');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return FinancialAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Cancel audit
  Future<FinancialAuditActionResponse?> cancelAudit(
      int auditId, String? reason) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/financial-audits/$auditId/cancel');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        if (reason != null) 'reason': reason,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 200) {
        return FinancialAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Get audit report
  Future<FinancialAuditReportResponse?> getReport(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/financial-audits/$auditId/report');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return FinancialAuditReportResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Get transaction details by type
  Future<TransactionDetailsResponse?> getTransactionDetails(
    int auditId,
    String type,
  ) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse(
          '${APIConfig.url}/financial-audits/$auditId/transactions').replace(
        queryParameters: {'type': type},
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return TransactionDetailsResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Get statistics
  Future<FinancialAuditStatisticsResponse?> getStatistics({
    String? startDate,
    String? endDate,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/financial-audits/statistics').replace(
        queryParameters: {
          if (startDate != null) 'start_date': startDate,
          if (endDate != null) 'end_date': endDate,
        },
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return FinancialAuditStatisticsResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }
}
