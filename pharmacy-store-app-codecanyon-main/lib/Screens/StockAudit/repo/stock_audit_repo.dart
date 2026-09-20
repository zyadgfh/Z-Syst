import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import '../model/stock_audit_model.dart';

class StockAuditRepo {
  // Fetch paginated list of stock audits
  Future<StockAuditPaginatedResponse?> getStockAudits({
    String? status,
    String? auditType,
    String? search,
    int? page,
    int? perPage,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits').replace(
        queryParameters: {
          if (status != null && status.isNotEmpty) 'status': status,
          if (auditType != null && auditType.isNotEmpty) 'audit_type': auditType,
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
        return StockAuditPaginatedResponse.fromJson(jsonDecode(response.body));
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

  // Create a new stock audit
  Future<StockAuditCreateResponse?> createAudit({
    required int businessId,
    required String auditType,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'business_id': businessId,
        'audit_type': auditType,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return StockAuditCreateResponse.fromJson(jsonDecode(response.body));
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

  // Get audit detail by id
  Future<StockAuditDetailResponse?> getAuditDetail(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$auditId');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return StockAuditDetailResponse.fromJson(jsonDecode(response.body));
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

  // Start an audit
  Future<StockAuditActionResponse?> startAudit(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$auditId/start');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return StockAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Complete an audit
  Future<StockAuditActionResponse?> completeAudit(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$auditId/complete');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return StockAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Cancel an audit
  Future<StockAuditActionResponse?> cancelAudit(int auditId, String? reason) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$auditId/cancel');

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
        return StockAuditActionResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Auto-populate audit with current stock
  Future<StockAuditAutoPopulateResponse?> autoPopulate(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$auditId/auto-populate');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return StockAuditAutoPopulateResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Add a detail to the audit
  Future<StockAuditDetailAddResponse?> addDetail({
    required int stockAuditId,
    required int productId,
    int? stockId,
    required int physicalQuantity,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$stockAuditId/details');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'stock_audit_id': stockAuditId,
        'product_id': productId,
        if (stockId != null) 'stock_id': stockId,
        'physical_quantity': physicalQuantity,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return StockAuditDetailAddResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Add bulk details to the audit
  Future<StockAuditBulkDetailsResponse?> addBulkDetails({
    required int auditId,
    required List<Map<String, dynamic>> details,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$auditId/bulk-details');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'details': details,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return StockAuditBulkDetailsResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Get variance report
  Future<StockAuditVarianceReport?> getVarianceReport(int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/stock-audits/$auditId/variance-report');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return StockAuditVarianceReport.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Create reconciliation from detail
  Future<StockAuditReconciliationResponse?> createReconciliation(
    int detailId,
    String? reason,
  ) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse(
          '${APIConfig.url}/stock-audits/details/$detailId/reconcile');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        if (reason != null) 'reason': reason,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return StockAuditReconciliationResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Post a single reconciliation
  Future<StockAuditReconciliationResponse?> postReconciliation(int reconciliationId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse(
          '${APIConfig.url}/stock-audits/reconciliations/$reconciliationId/post');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return StockAuditReconciliationResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Post all reconciliations for an audit
  Future<StockAuditPostAllReconciliationsResponse?> postAllReconciliations(
      int auditId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse(
          '${APIConfig.url}/stock-audits/$auditId/post-all-reconciliations');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return StockAuditPostAllReconciliationsResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Update reconciliation
  Future<StockAuditReconciliationResponse?> updateReconciliation(
    int reconciliationId,
    Map<String, dynamic> data,
  ) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse(
          '${APIConfig.url}/stock-audits/reconciliations/$reconciliationId');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final response =
          await http.put(uri, headers: headers, body: jsonEncode(data));

      if (response.statusCode == 200) {
        return StockAuditReconciliationResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Delete reconciliation
  Future<ApiResponse?> deleteReconciliation(int reconciliationId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse(
          '${APIConfig.url}/stock-audits/reconciliations/$reconciliationId');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.delete(uri, headers: headers);

      if (response.statusCode == 200) {
        return ApiResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Delete detail
  Future<ApiResponse?> deleteDetail(int detailId) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/stock-audits/details/$detailId');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.delete(uri, headers: headers);

      if (response.statusCode == 200) {
        return ApiResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }
}

class StockAuditAutoPopulateResponse {
  String? message;
  int? detailsCount;
  List<StockAuditDetailModel>? details;

  StockAuditAutoPopulateResponse({this.message, this.detailsCount, this.details});

  factory StockAuditAutoPopulateResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditAutoPopulateResponse(
      message: json['message'],
      detailsCount: json['details_count'],
      details: json['details'] != null
          ? List<StockAuditDetailModel>.from(
              json['details'].map((x) => StockAuditDetailModel.fromJson(x)))
          : [],
    );
  }
}

class StockAuditBulkDetailsResponse {
  String? message;
  int? detailsCount;
  List<StockAuditDetailModel>? details;

  StockAuditBulkDetailsResponse({this.message, this.detailsCount, this.details});

  factory StockAuditBulkDetailsResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditBulkDetailsResponse(
      message: json['message'],
      detailsCount: json['details_count'],
      details: json['details'] != null
          ? List<StockAuditDetailModel>.from(
              json['details'].map((x) => StockAuditDetailModel.fromJson(x)))
          : [],
    );
  }
}

class StockAuditPostAllReconciliationsResponse {
  String? message;
  int? reconciliationsCount;
  List<StockReconciliationModel>? reconciliations;

  StockAuditPostAllReconciliationsResponse(
      {this.message, this.reconciliationsCount, this.reconciliations});

  factory StockAuditPostAllReconciliationsResponse.fromJson(
      Map<String, dynamic> json) {
    return StockAuditPostAllReconciliationsResponse(
      message: json['message'],
      reconciliationsCount: json['reconciliations_count'],
      reconciliations: json['reconciliations'] != null
          ? List<StockReconciliationModel>.from(json['reconciliations']
              .map((x) => StockReconciliationModel.fromJson(x)))
          : [],
    );
  }
}

class ApiResponse {
  String? message;

  ApiResponse({this.message});

  factory ApiResponse.fromJson(Map<String, dynamic> json) {
    return ApiResponse(message: json['message']);
  }
}
