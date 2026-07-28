class AutoOrderRule {
  int? id;
  int? businessId;
  int? productId;
  bool enabled;
  double? minStockLevel;
  double? maxStockLevel;
  double? reorderPoint;
  double? safetyStock;
  int? leadTimeDays;
  double? minOrderQty;
  double? maxOrderQty;
  double? orderMultiple;
  int? preferredSupplierId;
  bool autoApprove;
  AutoOrderProduct? product;
  AutoOrderSupplier? preferredSupplier;

  AutoOrderRule({
    this.id,
    this.businessId,
    this.productId,
    required this.enabled,
    this.minStockLevel,
    this.maxStockLevel,
    this.reorderPoint,
    this.safetyStock,
    this.leadTimeDays,
    this.minOrderQty,
    this.maxOrderQty,
    this.orderMultiple,
    this.preferredSupplierId,
    required this.autoApprove,
    this.product,
    this.preferredSupplier,
  });

  factory AutoOrderRule.defaults() {
    return AutoOrderRule(
      enabled: true,
      autoApprove: false,
      leadTimeDays: 7,
      minOrderQty: 1,
      orderMultiple: 1,
    );
  }

  factory AutoOrderRule.fromJson(Map<String, dynamic> json) {
    return AutoOrderRule(
      id: json['id'],
      businessId: json['business_id'],
      productId: json['product_id'],
      enabled: json['enabled'] ?? true,
      minStockLevel: (json['min_stock_level']?.toDouble()),
      maxStockLevel: (json['max_stock_level']?.toDouble()),
      reorderPoint: (json['reorder_point']?.toDouble()),
      safetyStock: (json['safety_stock']?.toDouble()),
      leadTimeDays: json['lead_time_days'],
      minOrderQty: (json['min_order_qty']?.toDouble()),
      maxOrderQty: (json['max_order_qty']?.toDouble()),
      orderMultiple: (json['order_multiple']?.toDouble()),
      preferredSupplierId: json['preferred_supplier_id'],
      autoApprove: json['auto_approve'] ?? false,
      product: json['product'] != null
          ? AutoOrderProduct.fromJson(json['product'])
          : null,
      preferredSupplier: json['preferred_supplier'] != null
          ? AutoOrderSupplier.fromJson(json['preferred_supplier'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'enabled': enabled,
      'min_stock_level': minStockLevel,
      'max_stock_level': maxStockLevel,
      'reorder_point': reorderPoint,
      'lead_time_days': leadTimeDays,
      'min_order_qty': minOrderQty,
      'max_order_qty': maxOrderQty,
      'order_multiple': orderMultiple,
      'preferred_supplier_id': preferredSupplierId,
      'auto_approve': autoApprove,
    };
  }
}

class AutoOrderProduct {
  int? id;
  String? productName;
  String? productCode;
  double? salesPrice;

  AutoOrderProduct({this.id, this.productName, this.productCode, this.salesPrice});

  factory AutoOrderProduct.fromJson(Map<String, dynamic> json) {
    return AutoOrderProduct(
      id: json['id'],
      productName: json['productName'],
      productCode: json['productCode'],
      salesPrice: (json['sales_price']?.toDouble()),
    );
  }
}

class AutoOrderSupplier {
  int? id;
  String? name;
  String? phone;

  AutoOrderSupplier({this.id, this.name, this.phone});

  factory AutoOrderSupplier.fromJson(Map<String, dynamic> json) {
    return AutoOrderSupplier(
      id: json['id'],
      name: json['name'],
      phone: json['phone'],
    );
  }
}

class AutoOrderSuggestion {
  int? id;
  int? productId;
  String? productName;
  String? productCode;
  double? currentStock;
  double? pendingPurchases;
  double? predictedDemand;
  double? suggestedOrderQty;
  double? confidenceScore;
  String? priority;
  String? status;
  int? preferredSupplierId;
  double? reorderPoint;
  double? salesPrice;
  double? purchasePrice;
  String? notes;
  String? createdAt;

  AutoOrderSuggestion({
    this.id,
    this.productId,
    this.productName,
    this.productCode,
    this.currentStock,
    this.pendingPurchases,
    this.predictedDemand,
    this.suggestedOrderQty,
    this.confidenceScore,
    this.priority,
    this.status,
    this.preferredSupplierId,
    this.reorderPoint,
    this.salesPrice,
    this.purchasePrice,
    this.notes,
    this.createdAt,
  });

  factory AutoOrderSuggestion.fromJson(Map<String, dynamic> json) {
    return AutoOrderSuggestion(
      id: json['id'],
      productId: json['product_id'],
      productName: json['product_name'],
      productCode: json['product_code'],
      currentStock: (json['current_stock']?.toDouble()),
      pendingPurchases: (json['pending_purchases']?.toDouble()),
      predictedDemand: (json['predicted_demand']?.toDouble()),
      suggestedOrderQty: (json['suggested_order_qty']?.toDouble()),
      confidenceScore: (json['confidence_score']?.toDouble()),
      priority: json['priority'],
      status: json['status'],
      preferredSupplierId: json['preferred_supplier_id'],
      reorderPoint: (json['reorder_point']?.toDouble()),
      salesPrice: (json['sales_price']?.toDouble()),
      purchasePrice: (json['purchase_price']?.toDouble()),
      notes: json['notes'],
      createdAt: json['created_at'],
    );
  }

  String get statusLabel {
    switch (status) {
      case 'pending': return 'قيد الانتظار';
      case 'approved': return 'تمت الموافقة';
      case 'converted': return 'تم التحويل';
      case 'rejected': return 'مرفوض';
      default: return status ?? '';
    }
  }

  String get priorityLabel {
    switch (priority) {
      case 'high': return 'عالية';
      case 'medium': return 'متوسطة';
      case 'low': return 'منخفضة';
      default: return priority ?? '';
    }
  }
}

class AutoOrderSettingsStats {
  int? totalRules;
  int? enabledRules;
  int? autoApproveRules;

  AutoOrderSettingsStats({this.totalRules, this.enabledRules, this.autoApproveRules});

  factory AutoOrderSettingsStats.fromJson(Map<String, dynamic> json) {
    return AutoOrderSettingsStats(
      totalRules: json['total_rules'],
      enabledRules: json['enabled_rules'],
      autoApproveRules: json['auto_approve_rules'],
    );
  }
}

