class FefoSettings {
  int? id;
  int? businessId;
  bool fefoEnabled;
  String deductionMode;
  int expiryGraceDays;
  bool autoDeductExpiredStock;
  bool notifyOnFefoDeduction;
  int minStockForFefo;

  FefoSettings({
    this.id,
    this.businessId,
    this.fefoEnabled = true,
    this.deductionMode = 'automatic',
    this.expiryGraceDays = 30,
    this.autoDeductExpiredStock = false,
    this.notifyOnFefoDeduction = true,
    this.minStockForFefo = 0,
  });

  factory FefoSettings.fromJson(Map<String, dynamic> json) {
    return FefoSettings(
      id: json['id'],
      businessId: json['business_id'],
      fefoEnabled: json['fefo_enabled'] ?? true,
      deductionMode: json['deduction_mode'] ?? 'automatic',
      expiryGraceDays: json['expiry_grace_days'] ?? 30,
      autoDeductExpiredStock: json['auto_deduct_expired_stock'] ?? false,
      notifyOnFefoDeduction: json['notify_on_fefo_deduction'] ?? true,
      minStockForFefo: json['min_stock_for_fefo'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'fefo_enabled': fefoEnabled,
      'deduction_mode': deductionMode,
      'expiry_grace_days': expiryGraceDays,
      'auto_deduct_expired_stock': autoDeductExpiredStock,
      'notify_on_fefo_deduction': notifyOnFefoDeduction,
      'min_stock_for_fefo': minStockForFefo,
    };
  }
}

class FefoBatchSuggestion {
  int? stockId;
  String? batchNo;
  String? expireDate;
  int? availableQty;
  int? allocatedQty;
  double? fefoScore;
  bool? isValid;
  bool? isExpiringSoon;

  FefoBatchSuggestion({
    this.stockId,
    this.batchNo,
    this.expireDate,
    this.availableQty,
    this.allocatedQty,
    this.fefoScore,
    this.isValid,
    this.isExpiringSoon,
  });

  factory FefoBatchSuggestion.fromJson(Map<String, dynamic> json) {
    return FefoBatchSuggestion(
      stockId: json['stock_id'],
      batchNo: json['batch_no'],
      expireDate: json['expire_date'],
      availableQty: json['available_qty'],
      allocatedQty: json['allocated_qty'],
      fefoScore: (json['fefo_score'] ?? json['fefo_priority'])?.toDouble(),
      isValid: json['is_valid'],
      isExpiringSoon: json['is_expiring_soon'],
    );
  }
}

class FefoProductSuggestion {
  int? productId;
  String? productName;
  int? requestedQty;
  int? availableQty;
  bool? sufficient;
  List<FefoBatchSuggestion>? batches;

  FefoProductSuggestion({
    this.productId,
    this.productName,
    this.requestedQty,
    this.availableQty,
    this.sufficient,
    this.batches,
  });

  factory FefoProductSuggestion.fromJson(Map<String, dynamic> json) {
    return FefoProductSuggestion(
      productId: json['product_id'],
      productName: json['product_name'],
      requestedQty: json['requested_qty'],
      availableQty: json['available_qty'],
      sufficient: json['sufficient'],
      batches: json['batches'] != null
          ? List<FefoBatchSuggestion>.from(
              json['batches'].map((x) => FefoBatchSuggestion.fromJson(x)))
          : [],
    );
  }
}

class FefoReport {
  int? totalBatches;
  int? totalStockQty;
  int? expiredBatches;
  int? expiredQty;
  int? expiringWithin30Days;
  int? expiring30Qty;
  int? expiringWithin7Days;
  int? expiring7Qty;
  int? noExpiryBatches;
  int? noExpiryQty;
  List<FefoPriorityProduct>? priorityProducts;

  FefoReport({
    this.totalBatches,
    this.totalStockQty,
    this.expiredBatches,
    this.expiredQty,
    this.expiringWithin30Days,
    this.expiring30Qty,
    this.expiringWithin7Days,
    this.expiring7Qty,
    this.noExpiryBatches,
    this.noExpiryQty,
    this.priorityProducts,
  });

  factory FefoReport.fromJson(Map<String, dynamic> json) {
    return FefoReport(
      totalBatches: json['total_batches'],
      totalStockQty: json['total_stock_qty'],
      expiredBatches: json['expired_batches'],
      expiredQty: json['expired_qty'],
      expiringWithin30Days: json['expiring_within_30_days'],
      expiring30Qty: json['expiring_30_qty'],
      expiringWithin7Days: json['expiring_within_7_days'],
      expiring7Qty: json['expiring_7_qty'],
      noExpiryBatches: json['no_expiry_batches'],
      noExpiryQty: json['no_expiry_qty'],
      priorityProducts: json['priority_products'] != null
          ? List<FefoPriorityProduct>.from(
              json['priority_products'].map((x) => FefoPriorityProduct.fromJson(x)))
          : [],
    );
  }
}

class FefoPriorityProduct {
  int? productId;
  String? productName;
  int? totalStock;
  int? nearExpiryQty;

  FefoPriorityProduct({
    this.productId,
    this.productName,
    this.totalStock,
    this.nearExpiryQty,
  });

  factory FefoPriorityProduct.fromJson(Map<String, dynamic> json) {
    return FefoPriorityProduct(
      productId: json['product_id'],
      productName: json['product_name'],
      totalStock: json['total_stock'],
      nearExpiryQty: json['near_expiry_qty'],
    );
  }
}

class FefoLogItem {
  int? id;
  int? businessId;
  int? productId;
  int? stockId;
  int? saleId;
  int? saleDetailId;
  String? batchNo;
  String? expireDate;
  int? quantityDeducted;
  int? quantityRemainingAfter;
  String? actionType;
  String? notes;
  FefoLogProduct? product;
  FefoLogSale? sale;
  String? createdAt;

  FefoLogItem({
    this.id,
    this.businessId,
    this.productId,
    this.stockId,
    this.saleId,
    this.saleDetailId,
    this.batchNo,
    this.expireDate,
    this.quantityDeducted,
    this.quantityRemainingAfter,
    this.actionType,
    this.notes,
    this.product,
    this.sale,
    this.createdAt,
  });

  factory FefoLogItem.fromJson(Map<String, dynamic> json) {
    return FefoLogItem(
      id: json['id'],
      businessId: json['business_id'],
      productId: json['product_id'],
      stockId: json['stock_id'],
      saleId: json['sale_id'],
      saleDetailId: json['sale_detail_id'],
      batchNo: json['batch_no'],
      expireDate: json['expire_date'],
      quantityDeducted: json['quantity_deducted'],
      quantityRemainingAfter: json['quantity_remaining_after'],
      actionType: json['action_type'],
      notes: json['notes'],
      product: json['product'] != null ? FefoLogProduct.fromJson(json['product']) : null,
      sale: json['sale'] != null ? FefoLogSale.fromJson(json['sale']) : null,
      createdAt: json['created_at'],
    );
  }
}

class FefoLogProduct {
  int? id;
  String? productName;

  FefoLogProduct({this.id, this.productName});

  factory FefoLogProduct.fromJson(Map<String, dynamic> json) {
    return FefoLogProduct(
      id: json['id'],
      productName: json['productName'],
    );
  }
}

class FefoLogSale {
  int? id;
  String? invoiceNumber;

  FefoLogSale({this.id, this.invoiceNumber});

  factory FefoLogSale.fromJson(Map<String, dynamic> json) {
    return FefoLogSale(
      id: json['id'],
      invoiceNumber: json['invoiceNumber'],
    );
  }
}

