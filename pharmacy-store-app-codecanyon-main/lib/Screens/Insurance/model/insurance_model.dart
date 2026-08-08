class InsuranceCompanyModel {
  int? id;
  String? name;
  String? phoneNumber;
  String? email;
  String? address;
  String? website;
  String? contactPerson;
  int? businessId;
  int? userId;
  String? createdAt;
  String? updatedAt;

  InsuranceCompanyModel({
    this.id,
    this.name,
    this.phoneNumber,
    this.email,
    this.address,
    this.website,
    this.contactPerson,
    this.businessId,
    this.userId,
    this.createdAt,
    this.updatedAt,
  });

  factory InsuranceCompanyModel.fromJson(Map<String, dynamic> json) {
    return InsuranceCompanyModel(
      id: json['id'],
      name: json['name'],
      phoneNumber: json['phone_number'] ?? json['phone'],
      email: json['email'],
      address: json['address'],
      website: json['website'],
      contactPerson: json['contact_person'],
      businessId: json['business_id'],
      userId: json['user_id'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone_number': phoneNumber,
      'email': email,
      'address': address,
      'website': website,
      'contact_person': contactPerson,
      'business_id': businessId,
      'user_id': userId,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}

class InsuranceCompanyListResponse {
  String? message;
  List<InsuranceCompanyModel>? companies;

  InsuranceCompanyListResponse({this.message, this.companies});

  factory InsuranceCompanyListResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceCompanyListResponse(
      message: json['message'],
      companies: json['companies'] != null
          ? List<InsuranceCompanyModel>.from(
              json['companies'].map((x) => InsuranceCompanyModel.fromJson(x)))
          : [],
    );
  }
}

class InsuranceCompanyDetailResponse {
  String? message;
  InsuranceCompanyModel? company;

  InsuranceCompanyDetailResponse({this.message, this.company});

  factory InsuranceCompanyDetailResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceCompanyDetailResponse(
      message: json['message'],
      company: json['company'] != null
          ? InsuranceCompanyModel.fromJson(json['company'])
          : null,
    );
  }
}

class InsuranceCompanyActionResponse {
  String? message;
  InsuranceCompanyModel? company;

  InsuranceCompanyActionResponse({this.message, this.company});

  factory InsuranceCompanyActionResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceCompanyActionResponse(
      message: json['message'],
      company: json['company'] != null
          ? InsuranceCompanyModel.fromJson(json['company'])
          : null,
    );
  }
}

class InsurancePolicyModel {
  int? id;
  int? companyId;
  InsuranceCompanyModel? company;
  String? policyNumber;
  String? policyType;
  String? coverageType;
  dynamic coverageAmount;
  dynamic premiumAmount;
  String? startDate;
  String? endDate;
  String? status;
  String? notes;
  int? businessId;
  int? userId;
  String? createdAt;
  String? updatedAt;

  InsurancePolicyModel({
    this.id,
    this.companyId,
    this.company,
    this.policyNumber,
    this.policyType,
    this.coverageType,
    this.coverageAmount,
    this.premiumAmount,
    this.startDate,
    this.endDate,
    this.status,
    this.notes,
    this.businessId,
    this.userId,
    this.createdAt,
    this.updatedAt,
  });

  factory InsurancePolicyModel.fromJson(Map<String, dynamic> json) {
    return InsurancePolicyModel(
      id: json['id'],
      companyId: json['company_id'],
      company: json['company'] != null
          ? InsuranceCompanyModel.fromJson(json['company'])
          : null,
      policyNumber: json['policy_number'],
      policyType: json['policy_type'],
      coverageType: json['coverage_type'],
      coverageAmount: json['coverage_amount'],
      premiumAmount: json['premium_amount'],
      startDate: json['start_date'],
      endDate: json['end_date'],
      status: json['status'],
      notes: json['notes'],
      businessId: json['business_id'],
      userId: json['user_id'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'company_id': companyId,
      'policy_number': policyNumber,
      'policy_type': policyType,
      'coverage_type': coverageType,
      'coverage_amount': coverageAmount,
      'premium_amount': premiumAmount,
      'start_date': startDate,
      'end_date': endDate,
      'status': status,
      'notes': notes,
    };
  }
}

class InsurancePolicyListResponse {
  String? message;
  List<InsurancePolicyModel>? policies;

  InsurancePolicyListResponse({this.message, this.policies});

  factory InsurancePolicyListResponse.fromJson(Map<String, dynamic> json) {
    return InsurancePolicyListResponse(
      message: json['message'],
      policies: json['policies'] != null
          ? List<InsurancePolicyModel>.from(
              json['policies'].map((x) => InsurancePolicyModel.fromJson(x)))
          : [],
    );
  }
}

class InsurancePolicyDetailResponse {
  String? message;
  InsurancePolicyModel? policy;

  InsurancePolicyDetailResponse({this.message, this.policy});

  factory InsurancePolicyDetailResponse.fromJson(Map<String, dynamic> json) {
    return InsurancePolicyDetailResponse(
      message: json['message'],
      policy: json['policy'] != null
          ? InsurancePolicyModel.fromJson(json['policy'])
          : null,
    );
  }
}

class InsurancePolicyActionResponse {
  String? message;
  InsurancePolicyModel? policy;

  InsurancePolicyActionResponse({this.message, this.policy});

  factory InsurancePolicyActionResponse.fromJson(Map<String, dynamic> json) {
    return InsurancePolicyActionResponse(
      message: json['message'],
      policy: json['policy'] != null
          ? InsurancePolicyModel.fromJson(json['policy'])
          : null,
    );
  }
}

class InsuranceClaimModel {
  int? id;
  int? policyId;
  InsurancePolicyModel? policy;
  String? claimNumber;
  String? status;
  String? claimType;
  dynamic claimedAmount;
  dynamic approvedAmount;
  String? filingDate;
  String? resolutionDate;
  String? notes;
  int? businessId;
  int? userId;
  InsuredParty? patient;
  String? createdAt;
  String? updatedAt;

  InsuranceClaimModel({
    this.id,
    this.policyId,
    this.policy,
    this.claimNumber,
    this.status,
    this.claimType,
    this.claimedAmount,
    this.approvedAmount,
    this.filingDate,
    this.resolutionDate,
    this.notes,
    this.businessId,
    this.userId,
    this.patient,
    this.createdAt,
    this.updatedAt,
  });

  factory InsuranceClaimModel.fromJson(Map<String, dynamic> json) {
    return InsuranceClaimModel(
      id: json['id'],
      policyId: json['policy_id'],
      policy: json['policy'] != null
          ? InsurancePolicyModel.fromJson(json['policy'])
          : null,
      claimNumber: json['claim_number'],
      status: json['status'],
      claimType: json['claim_type'],
      claimedAmount: json['claimed_amount'],
      approvedAmount: json['approved_amount'],
      filingDate: json['filing_date'],
      resolutionDate: json['resolution_date'],
      notes: json['notes'],
      businessId: json['business_id'],
      userId: json['user_id'],
      patient: json['patient'] != null
          ? InsuredParty.fromJson(json['patient'])
          : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }
}

class InsuredParty {
  int? id;
  String? name;
  String? email;
  String? phoneNumber;
  String? address;

  InsuredParty({this.id, this.name, this.email, this.phoneNumber, this.address});

  factory InsuredParty.fromJson(Map<String, dynamic> json) {
    return InsuredParty(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      phoneNumber: json['phone_number'],
      address: json['address'],
    );
  }
}

class InsuranceClaimListResponse {
  String? message;
  List<InsuranceClaimModel>? claims;

  InsuranceClaimListResponse({this.message, this.claims});

  factory InsuranceClaimListResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceClaimListResponse(
      message: json['message'],
      claims: json['claims'] != null
          ? List<InsuranceClaimModel>.from(
              json['claims'].map((x) => InsuranceClaimModel.fromJson(x)))
          : [],
    );
  }
}

class InsuranceClaimDetailResponse {
  String? message;
  InsuranceClaimModel? claim;

  InsuranceClaimDetailResponse({this.message, this.claim});

  factory InsuranceClaimDetailResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceClaimDetailResponse(
      message: json['message'],
      claim: json['claim'] != null
          ? InsuranceClaimModel.fromJson(json['claim'])
          : null,
    );
  }
}

class InsuranceClaimActionResponse {
  String? message;
  InsuranceClaimModel? claim;

  InsuranceClaimActionResponse({this.message, this.claim});

  factory InsuranceClaimActionResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceClaimActionResponse(
      message: json['message'],
      claim: json['claim'] != null
          ? InsuranceClaimModel.fromJson(json['claim'])
          : null,
    );
  }
}

class InsuranceCoverageModel {
  int? id;
  int? policyId;
  InsurancePolicyModel? policy;
  String? coverageType;
  dynamic coverageLimit;
  dynamic deductible;
  dynamic copay;
  String? notes;
  int? businessId;
  int? userId;
  String? createdAt;
  String? updatedAt;

  InsuranceCoverageModel({
    this.id,
    this.policyId,
    this.policy,
    this.coverageType,
    this.coverageLimit,
    this.deductible,
    this.copay,
    this.notes,
    this.businessId,
    this.userId,
    this.createdAt,
    this.updatedAt,
  });

  factory InsuranceCoverageModel.fromJson(Map<String, dynamic> json) {
    return InsuranceCoverageModel(
      id: json['id'],
      policyId: json['policy_id'],
      policy: json['policy'] != null
          ? InsurancePolicyModel.fromJson(json['policy'])
          : null,
      coverageType: json['coverage_type'],
      coverageLimit: json['coverage_limit'],
      deductible: json['deductible'],
      copay: json['copay'],
      notes: json['notes'],
      businessId: json['business_id'],
      userId: json['user_id'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'policy_id': policyId,
      'coverage_type': coverageType,
      'coverage_limit': coverageLimit,
      'deductible': deductible,
      'copay': copay,
      'notes': notes,
    };
  }
}

class InsuranceCoverageListResponse {
  String? message;
  List<InsuranceCoverageModel>? coverages;

  InsuranceCoverageListResponse({this.message, this.coverages});

  factory InsuranceCoverageListResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceCoverageListResponse(
      message: json['message'],
      coverages: json['coverages'] != null
          ? List<InsuranceCoverageModel>.from(
              json['coverages'].map((x) => InsuranceCoverageModel.fromJson(x)))
          : [],
    );
  }
}

class InsuranceDashboardResponse {
  String? message;
  int? companiesCount;
  int? policiesCount;
  int? claimsCount;
  int? activePoliciesCount;
  int? expiredPoliciesCount;
  int? pendingClaimsCount;
  int? approvedClaimsCount;
  int? rejectedClaimsCount;
  List<ExpiringPolicy>? expiringPolicies;
  dynamic totalCoverageAmount;
  dynamic totalClaimsAmount;

  InsuranceDashboardResponse({
    this.message,
    this.companiesCount,
    this.policiesCount,
    this.claimsCount,
    this.activePoliciesCount,
    this.expiredPoliciesCount,
    this.pendingClaimsCount,
    this.approvedClaimsCount,
    this.rejectedClaimsCount,
    this.expiringPolicies,
    this.totalCoverageAmount,
    this.totalClaimsAmount,
  });

  factory InsuranceDashboardResponse.fromJson(Map<String, dynamic> json) {
    return InsuranceDashboardResponse(
      message: json['message'],
      companiesCount: json['companies_count'],
      policiesCount: json['policies_count'],
      claimsCount: json['claims_count'],
      activePoliciesCount: json['active_policies_count'],
      expiredPoliciesCount: json['expired_policies_count'],
      pendingClaimsCount: json['pending_claims_count'],
      approvedClaimsCount: json['approved_claims_count'],
      rejectedClaimsCount: json['rejected_claims_count'],
      expiringPolicies: json['expiring_policies'] != null
          ? List<ExpiringPolicy>.from(
              json['expiring_policies'].map((x) => ExpiringPolicy.fromJson(x)))
          : [],
      totalCoverageAmount: json['total_coverage_amount'],
      totalClaimsAmount: json['total_claims_amount'],
    );
  }
}

class ExpiringPolicy {
  int? id;
  String? policyNumber;
  String? endDate;
  String? companyName;
  dynamic coverageAmount;
  dynamic premiumAmount;

  ExpiringPolicy({
    this.id,
    this.policyNumber,
    this.endDate,
    this.companyName,
    this.coverageAmount,
    this.premiumAmount,
  });

  factory ExpiringPolicy.fromJson(Map<String, dynamic> json) {
    return ExpiringPolicy(
      id: json['id'],
      policyNumber: json['policy_number'],
      endDate: json['end_date'],
      companyName: json['company_name'],
      coverageAmount: json['coverage_amount'],
      premiumAmount: json['premium_amount'],
    );
  }
}

class InsuranceCoverageActionResponse {
  String? message;
  InsuranceCoverageModel? coverage;

  InsuranceCoverageActionResponse({this.message, this.coverage});

  factory InsuranceCoverageActionResponse.fromJson(
      Map<String, dynamic> json) {
    return InsuranceCoverageActionResponse(
      message: json['message'],
      coverage: json['coverage'] != null
          ? InsuranceCoverageModel.fromJson(json['coverage'])
          : null,
    );
  }
}

class ApiResponse {
  final String? message;
  final bool? success;

  ApiResponse({this.message, this.success});

  factory ApiResponse.fromJson(Map<String, dynamic> json) {
    return ApiResponse(
      message: json['message'],
      success: json['success'],
    );
  }
}
