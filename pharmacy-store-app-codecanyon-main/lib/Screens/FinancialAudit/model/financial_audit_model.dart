class FinancialAuditModel {
  int? id;
  String? auditNumber;
  String? auditType;
  String? startDate;
  String? endDate;
  String? status;
  dynamic openingBalance;
  dynamic totalRevenue;
  dynamic totalExpenses;
  dynamic closingBalance;
  dynamic variance;
  String? notes;
  dynamic metadata;
  int? businessId;
  int? userId;
  FinancialAuditUser? user;
  String? completedAt;
  String? createdAt;
  String? updatedAt;

  FinancialAuditModel({
    this.id,
    this.auditNumber,
    this.auditType,
    this.startDate,
    this.endDate,
    this.status,
    this.openingBalance,
    this.totalRevenue,
    this.totalExpenses,
    this.closingBalance,
    this.variance,
    this.notes,
    this.metadata,
    this.businessId,
    this.userId,
    this.user,
    this.completedAt,
    this.createdAt,
    this.updatedAt,
  });

  factory FinancialAuditModel.fromJson(Map<String, dynamic> json) {
    return FinancialAuditModel(
      id: json['id'],
      auditNumber: json['audit_number'],
      auditType: json['audit_type'],
      startDate: json['start_date'],
      endDate: json['end_date'],
      status: json['status'],
      openingBalance: json['opening_balance'],
      totalRevenue: json['total_revenue'],
      totalExpenses: json['total_expenses'],
      closingBalance: json['closing_balance'],
      variance: json['variance'],
      notes: json['notes'],
      metadata: json['metadata'],
      businessId: json['business_id'],
      userId: json['user_id'],
      user: json['user'] != null
          ? FinancialAuditUser.fromJson(json['user'])
          : null,
      completedAt: json['completed_at'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }
}

class FinancialAuditUser {
  int? id;
  String? name;

  FinancialAuditUser({this.id, this.name});

  factory FinancialAuditUser.fromJson(Map<String, dynamic> json) {
    return FinancialAuditUser(
      id: json['id'],
      name: json['name'],
    );
  }
}

class FinancialAuditListResponse {
  String? message;
  List<FinancialAuditModel>? audits;
  FinancialAuditMeta? meta;

  FinancialAuditListResponse({this.message, this.audits, this.meta});

  factory FinancialAuditListResponse.fromJson(Map<String, dynamic> json) {
    return FinancialAuditListResponse(
      message: json['message'],
      audits: json['audits'] != null
          ? List<FinancialAuditModel>.from(
              json['audits'].map((x) => FinancialAuditModel.fromJson(x)))
          : [],
      meta: json['meta'] != null ? FinancialAuditMeta.fromJson(json['meta']) : null,
    );
  }
}

class FinancialAuditMeta {
  int? currentPage;
  int? lastPage;
  int? perPage;
  int? total;

  FinancialAuditMeta({
    this.currentPage,
    this.lastPage,
    this.perPage,
    this.total,
  });

  factory FinancialAuditMeta.fromJson(Map<String, dynamic> json) {
    return FinancialAuditMeta(
      currentPage: json['current_page'],
      lastPage: json['last_page'],
      perPage: json['per_page'],
      total: json['total'],
    );
  }
}

class FinancialAuditDetailResponse {
  String? message;
  FinancialAuditModel? audit;

  FinancialAuditDetailResponse({this.message, this.audit});

  factory FinancialAuditDetailResponse.fromJson(Map<String, dynamic> json) {
    return FinancialAuditDetailResponse(
      message: json['message'],
      audit: json['audit'] != null
          ? FinancialAuditModel.fromJson(json['audit'])
          : null,
    );
  }
}

class FinancialAuditReportResponse {
  String? message;
  FinancialAuditReportData? report;

  FinancialAuditReportResponse({this.message, this.report});

  factory FinancialAuditReportResponse.fromJson(Map<String, dynamic> json) {
    return FinancialAuditReportResponse(
      message: json['message'],
      report: json['report'] != null
          ? FinancialAuditReportData.fromJson(json['report'])
          : null,
    );
  }
}

class FinancialAuditReportData {
  AuditInfo? auditInfo;
  FinancialSummary? financialSummary;
  VarianceAnalysis? varianceAnalysis;

  FinancialAuditReportData({
    this.auditInfo,
    this.financialSummary,
    this.varianceAnalysis,
  });

  factory FinancialAuditReportData.fromJson(Map<String, dynamic> json) {
    return FinancialAuditReportData(
      auditInfo: json['audit_info'] != null
          ? AuditInfo.fromJson(json['audit_info'])
          : null,
      financialSummary: json['financial_summary'] != null
          ? FinancialSummary.fromJson(json['financial_summary'])
          : null,
      varianceAnalysis: json['variance_analysis'] != null
          ? VarianceAnalysis.fromJson(json['variance_analysis'])
          : null,
    );
  }
}

class AuditInfo {
  String? auditNumber;
  String? auditType;
  Period? period;
  String? status;

  AuditInfo({this.auditNumber, this.auditType, this.period, this.status});

  factory AuditInfo.fromJson(Map<String, dynamic> json) {
    return AuditInfo(
      auditNumber: json['audit_number'],
      auditType: json['audit_type'],
      period:
          json['period'] != null ? Period.fromJson(json['period']) : null,
      status: json['status'],
    );
  }
}

class Period {
  String? startDate;
  String? endDate;

  Period({this.startDate, this.endDate});

  factory Period.fromJson(Map<String, dynamic> json) {
    return Period(
      startDate: json['start_date'],
      endDate: json['end_date'],
    );
  }
}

class FinancialSummary {
  dynamic openingBalance;
  dynamic totalSales;
  dynamic totalOtherIncome;
  dynamic totalRevenue;
  dynamic totalExpenses;
  dynamic totalPurchases;
  dynamic closingBalance;
  dynamic expectedClosingBalance;
  dynamic variance;

  FinancialSummary({
    this.openingBalance,
    this.totalSales,
    this.totalOtherIncome,
    this.totalRevenue,
    this.totalExpenses,
    this.totalPurchases,
    this.closingBalance,
    this.expectedClosingBalance,
    this.variance,
  });

  factory FinancialSummary.fromJson(Map<String, dynamic> json) {
    return FinancialSummary(
      openingBalance: json['opening_balance'],
      totalSales: json['total_sales'],
      totalOtherIncome: json['total_other_income'],
      totalRevenue: json['total_revenue'],
      totalExpenses: json['total_expenses'],
      totalPurchases: json['total_purchases'],
      closingBalance: json['closing_balance'],
      expectedClosingBalance: json['expected_closing_balance'],
      variance: json['variance'],
    );
  }
}

class VarianceAnalysis {
  bool? isBalanced;
  dynamic variancePercentage;
  bool? requiresInvestigation;

  VarianceAnalysis({this.isBalanced, this.variancePercentage, this.requiresInvestigation});

  factory VarianceAnalysis.fromJson(Map<String, dynamic> json) {
    return VarianceAnalysis(
      isBalanced: json['is_balanced'],
      variancePercentage: json['variance_percentage'],
      requiresInvestigation: json['requires_investigation'],
    );
  }
}

class FinancialAuditStatisticsResponse {
  String? message;
  FinancialAuditStatistics? statistics;

  FinancialAuditStatisticsResponse({this.message, this.statistics});

  factory FinancialAuditStatisticsResponse.fromJson(Map<String, dynamic> json) {
    return FinancialAuditStatisticsResponse(
      message: json['message'],
      statistics: json['statistics'] != null
          ? FinancialAuditStatistics.fromJson(json['statistics'])
          : null,
    );
  }
}

class FinancialAuditStatistics {
  int? completedAudits;
  int? pendingAudits;
  dynamic totalVariance;
  Period? period;

  FinancialAuditStatistics({
    this.completedAudits,
    this.pendingAudits,
    this.totalVariance,
    this.period,
  });

  factory FinancialAuditStatistics.fromJson(Map<String, dynamic> json) {
    return FinancialAuditStatistics(
      completedAudits: json['completed_audits'],
      pendingAudits: json['pending_audits'],
      totalVariance: json['total_variance'],
      period: json['period'] != null ? Period.fromJson(json['period']) : null,
    );
  }
}

class TransactionDetail {
  int? id;
  String? invoiceNumber;
  String? date;
  dynamic amount;
  String? customer;
  String? paymentStatus;
  String? category;
  String? description;

  TransactionDetail({
    this.id,
    this.invoiceNumber,
    this.date,
    this.amount,
    this.customer,
    this.paymentStatus,
    this.category,
    this.description,
  });

  factory TransactionDetail.fromJson(Map<String, dynamic> json) {
    return TransactionDetail(
      id: json['id'],
      invoiceNumber: json['invoice_number'] ?? json['invoiceNumber'],
      date: json['date'],
      amount: json['amount'],
      customer: json['customer'],
      paymentStatus: json['payment_status'] ?? json['paymentStatus'],
      category: json['category'],
      description: json['description'],
    );
  }
}

class TransactionDetailsResponse {
  String? message;
  String? type;
  List<TransactionDetail>? details;

  TransactionDetailsResponse({this.message, this.type, this.details});

  factory TransactionDetailsResponse.fromJson(Map<String, dynamic> json) {
    return TransactionDetailsResponse(
      message: json['message'],
      type: json['type'],
      details: json['details'] != null
          ? List<TransactionDetail>.from(
              json['details'].map((x) => TransactionDetail.fromJson(x)))
          : [],
    );
  }
}

class FinancialAuditActionResponse {
  String? message;
  FinancialAuditModel? audit;

  FinancialAuditActionResponse({this.message, this.audit});

  factory FinancialAuditActionResponse.fromJson(Map<String, dynamic> json) {
    return FinancialAuditActionResponse(
      message: json['message'],
      audit: json['audit'] != null
          ? FinancialAuditModel.fromJson(json['audit'])
          : null,
    );
  }
}
