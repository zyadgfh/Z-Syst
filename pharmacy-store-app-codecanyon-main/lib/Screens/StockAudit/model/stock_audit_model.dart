class StockAuditModel {
  int? id;
  String? auditNumber;
  String? auditType;
  String? status;
  String? auditDate;
  String? completedAt;
  String? notes;
  dynamic metadata;
  int? businessId;
  int? userId;
  StockAuditUser? user;
  int? detailsCount;
  List<StockAuditDetailModel>? details;
  int? reconciliationsCount;
  List<StockReconciliationModel>? reconciliations;
  String? createdAt;
  String? updatedAt;

  StockAuditModel({
    this.id,
    this.auditNumber,
    this.auditType,
    this.status,
    this.auditDate,
    this.completedAt,
    this.notes,
    this.metadata,
    this.businessId,
    this.userId,
    this.user,
    this.detailsCount,
    this.details,
    this.reconciliationsCount,
    this.reconciliations,
    this.createdAt,
    this.updatedAt,
  });

  factory StockAuditModel.fromJson(Map<String, dynamic> json) {
    return StockAuditModel(
      id: json['id'],
      auditNumber: json['audit_number'],
      auditType: json['audit_type'],
      status: json['status'],
      auditDate: json['audit_date'],
      completedAt: json['completed_at'],
      notes: json['notes'],
      metadata: json['metadata'],
      businessId: json['business_id'],
      userId: json['user_id'],
      user: json['user'] != null ? StockAuditUser.fromJson(json['user']) : null,
      detailsCount: json['details_count'],
      details: json['details'] != null
          ? List<StockAuditDetailModel>.from(
              json['details'].map((x) => StockAuditDetailModel.fromJson(x)))
          : null,
      reconciliationsCount: json['reconciliations_count'],
      reconciliations: json['reconciliations'] != null
          ? List<StockReconciliationModel>.from(json['reconciliations']
              .map((x) => StockReconciliationModel.fromJson(x)))
          : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }
}

class StockAuditUser {
  int? id;
  String? name;

  StockAuditUser({this.id, this.name});

  factory StockAuditUser.fromJson(Map<String, dynamic> json) {
    return StockAuditUser(
      id: json['id'],
      name: json['name'],
    );
  }
}

class StockAuditDetailModel {
  int? id;
  int? stockAuditId;
  int? businessId;
  int? productId;
  int? stockId;
  String? batchNo;
  String? expireDate;
  int? systemQuantity;
  int? physicalQuantity;
  int? variance;
  dynamic unitCost;
  dynamic varianceValue;
  String? varianceType;
  String? notes;
  StockAuditProduct? product;
  StockAuditStock? stock;
  String? createdAt;
  String? updatedAt;

  StockAuditDetailModel({
    this.id,
    this.stockAuditId,
    this.businessId,
    this.productId,
    this.stockId,
    this.batchNo,
    this.expireDate,
    this.systemQuantity,
    this.physicalQuantity,
    this.variance,
    this.unitCost,
    this.varianceValue,
    this.varianceType,
    this.notes,
    this.product,
    this.stock,
    this.createdAt,
    this.updatedAt,
  });

  factory StockAuditDetailModel.fromJson(Map<String, dynamic> json) {
    return StockAuditDetailModel(
      id: json['id'],
      stockAuditId: json['stock_audit_id'],
      businessId: json['business_id'],
      productId: json['product_id'],
      stockId: json['stock_id'],
      batchNo: json['batch_no'],
      expireDate: json['expire_date'],
      systemQuantity: json['system_quantity'],
      physicalQuantity: json['physical_quantity'],
      variance: json['variance'],
      unitCost: json['unit_cost'],
      varianceValue: json['variance_value'],
      varianceType: json['variance_type'],
      notes: json['notes'],
      product: json['product'] != null
          ? StockAuditProduct.fromJson(json['product'])
          : null,
      stock: json['stock'] != null ? StockAuditStock.fromJson(json['stock']) : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }
}

class StockAuditProduct {
  int? id;
  String? productName;
  String? productCode;

  StockAuditProduct({this.id, this.productName, this.productCode});

  factory StockAuditProduct.fromJson(Map<String, dynamic> json) {
    return StockAuditProduct(
      id: json['id'],
      productName: json['name'],
      productCode: json['code'],
    );
  }
}

class StockAuditStock {
  int? id;
  String? batchNo;
  int? quantity;

  StockAuditStock({this.id, this.batchNo, this.quantity});

  factory StockAuditStock.fromJson(Map<String, dynamic> json) {
    return StockAuditStock(
      id: json['id'],
      batchNo: json['batch_no'],
      quantity: json['quantity'],
    );
  }
}

class StockReconciliationModel {
  int? id;
  int? businessId;
  int? stockAuditId;
  int? userId;
  int? productId;
  int? stockId;
  String? batchNo;
  String? expireDate;
  String? adjustmentType;
  int? previousQuantity;
  int? newQuantity;
  int? adjustmentQuantity;
  dynamic unitCost;
  dynamic adjustmentValue;
  String? referenceType;
  int? referenceId;
  String? reason;
  bool? isPosted;
  String? postedAt;
  StockAuditUser? user;
  StockAuditProduct? product;
  StockAuditStock? stock;
  String? createdAt;
  String? updatedAt;

  StockReconciliationModel({
    this.id,
    this.businessId,
    this.stockAuditId,
    this.userId,
    this.productId,
    this.stockId,
    this.batchNo,
    this.expireDate,
    this.adjustmentType,
    this.previousQuantity,
    this.newQuantity,
    this.adjustmentQuantity,
    this.unitCost,
    this.adjustmentValue,
    this.referenceType,
    this.referenceId,
    this.reason,
    this.isPosted,
    this.postedAt,
    this.user,
    this.product,
    this.stock,
    this.createdAt,
    this.updatedAt,
  });

  factory StockReconciliationModel.fromJson(Map<String, dynamic> json) {
    return StockReconciliationModel(
      id: json['id'],
      businessId: json['business_id'],
      stockAuditId: json['stock_audit_id'],
      userId: json['user_id'],
      productId: json['product_id'],
      stockId: json['stock_id'],
      batchNo: json['batch_no'],
      expireDate: json['expire_date'],
      adjustmentType: json['adjustment_type'],
      previousQuantity: json['previous_quantity'],
      newQuantity: json['new_quantity'],
      adjustmentQuantity: json['adjustment_quantity'],
      unitCost: json['unit_cost'],
      adjustmentValue: json['adjustment_value'],
      referenceType: json['reference_type'],
      referenceId: json['reference_id'],
      reason: json['reason'],
      isPosted: json['is_posted'],
      postedAt: json['posted_at'],
      user: json['user'] != null ? StockAuditUser.fromJson(json['user']) : null,
      product: json['product'] != null
          ? StockAuditProduct.fromJson(json['product'])
          : null,
      stock: json['stock'] != null ? StockAuditStock.fromJson(json['stock']) : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }
}

class StockAuditListResponse {
  String? message;
  List<StockAuditModel>? audits;

  StockAuditListResponse({this.message, this.audits});

  factory StockAuditListResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditListResponse(
      message: json['message'],
      audits: json['audits'] != null
          ? List<StockAuditModel>.from(
              json['audits'].map((x) => StockAuditModel.fromJson(x)))
          : [],
    );
  }
}

class StockAuditDetailResponse {
  String? message;
  StockAuditModel? audit;
  Map<String, dynamic>? summary;

  StockAuditDetailResponse({this.message, this.audit, this.summary});

  factory StockAuditDetailResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditDetailResponse(
      message: json['message'],
      audit: json['audit'] != null ? StockAuditModel.fromJson(json['audit']) : null,
      summary: json['summary'],
    );
  }
}

class StockAuditVarianceReport {
  String? message;
  StockAuditVarianceData? report;

  StockAuditVarianceReport({this.message, this.report});

  factory StockAuditVarianceReport.fromJson(Map<String, dynamic> json) {
    return StockAuditVarianceReport(
      message: json['message'],
      report: json['report'] != null
          ? StockAuditVarianceData.fromJson(json['report'])
          : null,
    );
  }
}

class StockAuditVarianceData {
  Map<String, dynamic>? audit;
  List<VarianceItem>? variances;

  StockAuditVarianceData({this.audit, this.variances});

  factory StockAuditVarianceData.fromJson(Map<String, dynamic> json) {
    return StockAuditVarianceData(
      audit: json['audit'],
      variances: json['variances'] != null
          ? List<VarianceItem>.from(
              json['variances'].map((x) => VarianceItem.fromJson(x)))
          : [],
    );
  }
}

class VarianceItem {
  int? productId;
  String? productName;
  String? batchNo;
  int? systemQuantity;
  int? physicalQuantity;
  int? variance;
  String? varianceType;
  dynamic varianceValue;

  VarianceItem({
    this.productId,
    this.productName,
    this.batchNo,
    this.systemQuantity,
    this.physicalQuantity,
    this.variance,
    this.varianceType,
    this.varianceValue,
  });

  factory VarianceItem.fromJson(Map<String, dynamic> json) {
    return VarianceItem(
      productId: json['product_id'],
      productName: json['product_name'],
      batchNo: json['batch_no'],
      systemQuantity: json['system_quantity'],
      physicalQuantity: json['physical_quantity'],
      variance: json['variance'],
      varianceType: json['variance_type'],
      varianceValue: json['variance_value'],
    );
  }
}

class StockAuditSummary {
  int? totalItems;
  int? totalSystemQuantity;
  int? totalPhysicalQuantity;
  int? totalVariance;
  dynamic totalVarianceValue;
  int? positiveVariances;
  int? negativeVariances;
  int? noVariances;
  int? reconciliationsCreated;
  int? reconciliationsPosted;

  StockAuditSummary({
    this.totalItems,
    this.totalSystemQuantity,
    this.totalPhysicalQuantity,
    this.totalVariance,
    this.totalVarianceValue,
    this.positiveVariances,
    this.negativeVariances,
    this.noVariances,
    this.reconciliationsCreated,
    this.reconciliationsPosted,
  });

  factory StockAuditSummary.fromJson(Map<String, dynamic> json) {
    return StockAuditSummary(
      totalItems: json['total_items'],
      totalSystemQuantity: json['total_system_quantity'],
      totalPhysicalQuantity: json['total_physical_quantity'],
      totalVariance: json['total_variance'],
      totalVarianceValue: json['total_variance_value'],
      positiveVariances: json['positive_variances'],
      negativeVariances: json['negative_variances'],
      noVariances: json['no_variances'],
      reconciliationsCreated: json['reconciliations_created'],
      reconciliationsPosted: json['reconciliations_posted'],
    );
  }
}

class StockAuditListResponseMeta {
  int? currentPage;
  int? lastPage;
  int? perPage;
  int? total;

  StockAuditListResponseMeta({
    this.currentPage,
    this.lastPage,
    this.perPage,
    this.total,
  });

  factory StockAuditListResponseMeta.fromJson(Map<String, dynamic> json) {
    return StockAuditListResponseMeta(
      currentPage: json['current_page'],
      lastPage: json['last_page'],
      perPage: json['per_page'],
      total: json['total'],
    );
  }
}

class StockAuditPaginatedResponse {
  String? message;
  List<StockAuditModel>? audits;
  StockAuditListResponseMeta? meta;

  StockAuditPaginatedResponse({this.message, this.audits, this.meta});

  factory StockAuditPaginatedResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditPaginatedResponse(
      message: json['message'],
      audits: json['audits'] is List
          ? List<StockAuditModel>.from(
              json['audits'].map((x) => StockAuditModel.fromJson(x)))
          : null,
      meta: json['meta'] != null
          ? StockAuditListResponseMeta.fromJson(json['meta'])
          : null,
    );
  }
}

class StockAuditCreateResponse {
  String? message;
  StockAuditModel? audit;

  StockAuditCreateResponse({this.message, this.audit});

  factory StockAuditCreateResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditCreateResponse(
      message: json['message'],
      audit: json['audit'] != null ? StockAuditModel.fromJson(json['audit']) : null,
    );
  }
}

class StockAuditActionResponse {
  String? message;
  StockAuditModel? audit;

  StockAuditActionResponse({this.message, this.audit});

  factory StockAuditActionResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditActionResponse(
      message: json['message'],
      audit: json['audit'] != null ? StockAuditModel.fromJson(json['audit']) : null,
    );
  }
}

class StockAuditDetailAddResponse {
  String? message;
  StockAuditDetailModel? detail;

  StockAuditDetailAddResponse({this.message, this.detail});

  factory StockAuditDetailAddResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditDetailAddResponse(
      message: json['message'],
      detail: json['detail'] != null
          ? StockAuditDetailModel.fromJson(json['detail'])
          : null,
    );
  }
}

class StockAuditReconciliationResponse {
  String? message;
  StockReconciliationModel? reconciliation;

  StockAuditReconciliationResponse({this.message, this.reconciliation});

  factory StockAuditReconciliationResponse.fromJson(Map<String, dynamic> json) {
    return StockAuditReconciliationResponse(
      message: json['message'],
      reconciliation: json['reconciliation'] != null
          ? StockReconciliationModel.fromJson(json['reconciliation'])
          : null,
    );
  }
}
