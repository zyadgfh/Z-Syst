import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';

class InsuranceRepo {
  // Get dashboard summary
  Future<InsuranceDashboardResponse?> getDashboard() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/dashboard');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsuranceDashboardResponse.fromJson(jsonDecode(response.body));
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

  // Companies CRUD
  Future<InsuranceCompanyListResponse?> getCompanies() async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/companies');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsuranceCompanyListResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceCompanyActionResponse?> createCompany({
    required String name,
    String? phoneNumber,
    String? email,
    String? address,
    String? website,
    String? contactPerson,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/companies');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'name': name,
        if (phoneNumber != null) 'phone_number': phoneNumber,
        if (email != null) 'email': email,
        if (address != null) 'address': address,
        if (website != null) 'website': website,
        if (contactPerson != null) 'contact_person': contactPerson,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return InsuranceCompanyActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceCompanyActionResponse?> updateCompany({
    required int id,
    required String name,
    String? phoneNumber,
    String? email,
    String? address,
    String? website,
    String? contactPerson,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/companies/$id');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'name': name,
        if (phoneNumber != null) 'phone_number': phoneNumber,
        if (email != null) 'email': email,
        if (address != null) 'address': address,
        if (website != null) 'website': website,
        if (contactPerson != null) 'contact_person': contactPerson,
      });

      final response = await http.put(uri, headers: headers, body: body);

      if (response.statusCode == 200) {
        return InsuranceCompanyActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<ApiResponse?> deleteCompany(int id) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/companies/$id');

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

  // Policies CRUD
  Future<InsurancePolicyListResponse?> getPolicies({
    int? companyId,
    String? status,
    String? search,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/insurance/policies').replace(
        queryParameters: {
          if (companyId != null) 'company_id': companyId.toString(),
          if (status != null && status.isNotEmpty) 'status': status,
          if (search != null && search.isNotEmpty) 'search': search,
        },
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsurancePolicyListResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsurancePolicyActionResponse?> createPolicy({
    required int companyId,
    required String policyNumber,
    required String policyType,
    required String coverageType,
    required dynamic coverageAmount,
    required dynamic premiumAmount,
    required String startDate,
    required String endDate,
    String? status,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/policies');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'company_id': companyId,
        'policy_number': policyNumber,
        'policy_type': policyType,
        'coverage_type': coverageType,
        'coverage_amount': coverageAmount,
        'premium_amount': premiumAmount,
        'start_date': startDate,
        'end_date': endDate,
        if (status != null) 'status': status,
        if (notes != null) 'notes': notes,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return InsurancePolicyActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsurancePolicyActionResponse?> updatePolicy({
    required int id,
    required int companyId,
    required String policyNumber,
    required String policyType,
    required String coverageType,
    required dynamic coverageAmount,
    required dynamic premiumAmount,
    required String startDate,
    required String endDate,
    String? status,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/policies/$id');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'company_id': companyId,
        'policy_number': policyNumber,
        'policy_type': policyType,
        'coverage_type': coverageType,
        'coverage_amount': coverageAmount,
        'premium_amount': premiumAmount,
        'start_date': startDate,
        'end_date': endDate,
        if (status != null) 'status': status,
        if (notes != null) 'notes': notes,
      });

      final response = await http.put(uri, headers: headers, body: body);

      if (response.statusCode == 200) {
        return InsurancePolicyActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<ApiResponse?> deletePolicy(int id) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/policies/$id');

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

  // Claims CRUD + lifecycle
  Future<InsuranceClaimListResponse?> getClaims({
    int? policyId,
    String? status,
    String? search,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/insurance/claims').replace(
        queryParameters: {
          if (policyId != null) 'policy_id': policyId.toString(),
          if (status != null && status.isNotEmpty) 'status': status,
          if (search != null && search.isNotEmpty) 'search': search,
        },
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsuranceClaimListResponse.fromJson(jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceClaimActionResponse?> createClaim({
    required int policyId,
    required String claimType,
    required dynamic claimedAmount,
    required String filingDate,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/claims');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'policy_id': policyId,
        'claim_type': claimType,
        'claimed_amount': claimedAmount,
        'filing_date': filingDate,
        if (notes != null) 'notes': notes,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return InsuranceClaimActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceClaimActionResponse?> updateClaim({
    required int id,
    required int policyId,
    required String claimType,
    required dynamic claimedAmount,
    required String filingDate,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/claims/$id');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'policy_id': policyId,
        'claim_type': claimType,
        'claimed_amount': claimedAmount,
        'filing_date': filingDate,
        if (notes != null) 'notes': notes,
      });

      final response = await http.put(uri, headers: headers, body: body);

      if (response.statusCode == 200) {
        return InsuranceClaimActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<ApiResponse?> deleteClaim(int id) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/claims/$id');

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

  // Claim lifecycle actions
  Future<InsuranceClaimActionResponse?> submitClaim(int id) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/insurance/claims/$id/submit');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsuranceClaimActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceClaimActionResponse?> approveClaim(
    int id, {
    dynamic approvedAmount,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/insurance/claims/$id/approve');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        if (approvedAmount != null) 'approved_amount': approvedAmount,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 200) {
        return InsuranceClaimActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceClaimActionResponse?> rejectClaim(int id) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/claims/$id/reject');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsuranceClaimActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceClaimActionResponse?> payClaim(int id) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/claims/$id/pay');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.post(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsuranceClaimActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Coverage CRUD
  Future<InsuranceCoverageListResponse?> getCoverages({
    int? policyId,
    String? coverageType,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri =
          Uri.parse('${APIConfig.url}/insurance/coverages').replace(
        queryParameters: {
          if (policyId != null) 'policy_id': policyId.toString(),
          if (coverageType != null && coverageType.isNotEmpty)
            'coverage_type': coverageType,
        },
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return InsuranceCoverageListResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceCoverageActionResponse?> createCoverage({
    required int policyId,
    required String coverageType,
    required dynamic coverageLimit,
    dynamic deductible,
    dynamic copay,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/coverages');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'policy_id': policyId,
        'coverage_type': coverageType,
        'coverage_limit': coverageLimit,
        if (deductible != null) 'deductible': deductible,
        if (copay != null) 'copay': copay,
        if (notes != null) 'notes': notes,
      });

      final response = await http.post(uri, headers: headers, body: body);

      if (response.statusCode == 201) {
        return InsuranceCoverageActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<InsuranceCoverageActionResponse?> updateCoverage({
    required int id,
    required int policyId,
    required String coverageType,
    required dynamic coverageLimit,
    dynamic deductible,
    dynamic copay,
    String? notes,
  }) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/coverages/$id');

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
        'Content-Type': 'application/json',
      };

      final body = jsonEncode({
        'policy_id': policyId,
        'coverage_type': coverageType,
        'coverage_limit': coverageLimit,
        if (deductible != null) 'deductible': deductible,
        if (copay != null) 'copay': copay,
        if (notes != null) 'notes': notes,
      });

      final response = await http.put(uri, headers: headers, body: body);

      if (response.statusCode == 200) {
        return InsuranceCoverageActionResponse.fromJson(
            jsonDecode(response.body));
      }

      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  Future<ApiResponse?> deleteCoverage(int id) async {
    try {
      final token = await getAuthToken();
      if (token.isEmpty) throw Exception('Auth token is empty');

      final uri = Uri.parse('${APIConfig.url}/insurance/coverages/$id');

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
