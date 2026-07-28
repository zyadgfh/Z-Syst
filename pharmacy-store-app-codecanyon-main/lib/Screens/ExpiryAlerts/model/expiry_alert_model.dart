class ExpiryAlertStats {
  int? expired;
  int? expiringToday;
  int? expiring7Days;
  int? expiring30Days;
  int? expiring60Days;
  int? expiring90Days;
  int? expiring365Days;
  int? total;

  ExpiryAlertStats({
    this.expired,
    this.expiringToday,
    this.expiring7Days,
    this.expiring30Days,
    this.expiring60Days,
    this.expiring90Days,
    this.expiring365Days,
    this.total,
  });

  factory ExpiryAlertStats.fromJson(Map<String, dynamic> json) {
    return ExpiryAlertStats(
      expired: json['expired'] ?? 0,
      expiringToday: json['expiring_today'] ?? 0,
      expiring7Days: json['expiring_7_days'] ?? 0,
      expiring30Days: json['expiring_30_days'] ?? 0,
      expiring60Days: json['expiring_60_days'] ?? 0,
      expiring90Days: json['expiring_90_days'] ?? 0,
      expiring365Days: json['expiring_365_days'] ?? 0,
      total: json['total'] ?? 0,
    );
  }

  int get totalExpiring =>
      (expiringToday ?? 0) +
      (expiring7Days ?? 0) +
      (expiring30Days ?? 0) +
      (expiring60Days ?? 0) +
      (expiring90Days ?? 0) +
      (expiring365Days ?? 0);

  int get totalCritical => (expired ?? 0) + (expiringToday ?? 0);
}

class ExpiryAlertResponse {
  String? message;
  ExpiryAlertStats? data;

  ExpiryAlertResponse({this.message, this.data});

  factory ExpiryAlertResponse.fromJson(Map<String, dynamic> json) {
    return ExpiryAlertResponse(
      message: json['message'],
      data: json['data'] != null ? ExpiryAlertStats.fromJson(json['data']) : null,
    );
  }
}

class ExpiryAlertItem {
  int? id;
  int? productId;
  int? productStock;
  String? batchNo;
  String? expireDate;
  int? daysRemaining;
  String? severity;
  String? severityLabel;
  ExpiryAlertProduct? product;
  String? createdAt;
  String? updatedAt;

  ExpiryAlertItem({
    this.id,
    this.productId,
    this.productStock,
    this.batchNo,
    this.expireDate,
    this.daysRemaining,
    this.severity,
    this.severityLabel,
    this.product,
    this.createdAt,
    this.updatedAt,
  });

  factory ExpiryAlertItem.fromJson(Map<String, dynamic> json) {
    return ExpiryAlertItem(
      id: json['id'],
      productId: json['product_id'],
      productStock: json['productStock'],
      batchNo: json['batch_no'],
      expireDate: json['expire_date'],
      daysRemaining: json['days_remaining'],
      severity: json['severity'],
      severityLabel: json['severity_label'],
      product: json['product'] != null ? ExpiryAlertProduct.fromJson(json['product']) : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }
}

class ExpiryAlertProduct {
  int? id;
  String? productName;
  String? productCode;
  dynamic salesPrice;
  dynamic purchaseWithTax;
  ExpiryAlertCategory? category;

  ExpiryAlertProduct({
    this.id,
    this.productName,
    this.productCode,
    this.salesPrice,
    this.purchaseWithTax,
    this.category,
  });

  factory ExpiryAlertProduct.fromJson(Map<String, dynamic> json) {
    return ExpiryAlertProduct(
      id: json['id'],
      productName: json['productName'],
      productCode: json['productCode'],
      salesPrice: json['sales_price'],
      purchaseWithTax: json['purchase_with_tax'],
      category: json['category'] != null ? ExpiryAlertCategory.fromJson(json['category']) : null,
    );
  }
}

class ExpiryAlertCategory {
  int? id;
  String? categoryName;

  ExpiryAlertCategory({this.id, this.categoryName});

  factory ExpiryAlertCategory.fromJson(Map<String, dynamic> json) {
    return ExpiryAlertCategory(
      id: json['id'],
      categoryName: json['categoryName'],
    );
  }
}

class ExpiryAlertListResponse {
  String? message;
  ExpiryAlertPaginatedData? data;

  ExpiryAlertListResponse({this.message, this.data});

  factory ExpiryAlertListResponse.fromJson(Map<String, dynamic> json) {
    return ExpiryAlertListResponse(
      message: json['message'],
      data: json['data'] != null ? ExpiryAlertPaginatedData.fromJson(json['data']) : null,
    );
  }
}

class ExpiryAlertPaginatedData {
  int? currentPage;
  List<ExpiryAlertItem>? data;
  int? from;
  int? lastPage;
  int? perPage;
  int? to;
  int? total;

  ExpiryAlertPaginatedData({
    this.currentPage,
    this.data,
    this.from,
    this.lastPage,
    this.perPage,
    this.to,
    this.total,
  });

  factory ExpiryAlertPaginatedData.fromJson(Map<String, dynamic> json) {
    return ExpiryAlertPaginatedData(
      currentPage: json['current_page'],
      data: json['data'] != null
          ? List<ExpiryAlertItem>.from(json['data'].map((x) => ExpiryAlertItem.fromJson(x)))
          : [],
      from: json['from'],
      lastPage: json['last_page'],
      perPage: json['per_page'],
      to: json['to'],
      total: json['total'],
    );
  }
}

