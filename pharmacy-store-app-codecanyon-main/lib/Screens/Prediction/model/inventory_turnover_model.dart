import 'dart:convert';

class InventoryTurnoverSummary {
  int? businessId;
  int? totalProducts;
  double? totalStockQty;
  double? totalInventoryValue;
  ReportsAvailable? reportsAvailable;
  MovementDistribution? movementDistribution;
  AbcDistribution? abcDistribution;
  String? generatedAt;

  InventoryTurnoverSummary({
    this.businessId,
    this.totalProducts,
    this.totalStockQty,
    this.totalInventoryValue,
    this.reportsAvailable,
    this.movementDistribution,
    this.abcDistribution,
    this.generatedAt,
  });

  factory InventoryTurnoverSummary.fromJson(Map<String, dynamic> json) {
    return InventoryTurnoverSummary(
      businessId: json['business_id'],
      totalProducts: json['total_products'],
      totalStockQty: (json['total_stock_qty']?.toDouble()),
      totalInventoryValue: (json['total_inventory_value']?.toDouble()),
      reportsAvailable: json['reports_available'] != null
          ? ReportsAvailable.fromJson(json['reports_available'])
          : null,
      movementDistribution: json['movement_distribution'] != null
          ? MovementDistribution.fromJson(json['movement_distribution'])
          : null,
      abcDistribution: json['abc_distribution'] != null
          ? AbcDistribution.fromJson(json['abc_distribution'])
          : null,
      generatedAt: json['generated_at'],
    );
  }
}

class ReportsAvailable {
  InventoryTurnoverReport? monthly;
  InventoryTurnoverReport? quarterly;
  InventoryTurnoverReport? yearly;

  ReportsAvailable({this.monthly, this.quarterly, this.yearly});

  factory ReportsAvailable.fromJson(Map<String, dynamic> json) {
    return ReportsAvailable(
      monthly: json['monthly'] != null
          ? InventoryTurnoverReport.fromJson(json['monthly'])
          : null,
      quarterly: json['quarterly'] != null
          ? InventoryTurnoverReport.fromJson(json['quarterly'])
          : null,
      yearly: json['yearly'] != null
          ? InventoryTurnoverReport.fromJson(json['yearly'])
          : null,
    );
  }
}

class InventoryTurnoverReport {
  int? id;
  String? reportType;
  String? periodStart;
  String? periodEnd;
  String? periodLabel;
  double? totalCogs;
  double? averageInventoryValue;
  double? inventoryTurnoverRatio;
  double? daysInventoryOutstanding;
  int? totalProductsAnalyzed;
  int? slowMovingCount;
  int? deadStockCount;
  double? totalSlowMovingValue;
  double? totalDeadStockValue;
  double? totalInventoryValue;
  String? createdAt;

  InventoryTurnoverReport({
    this.id,
    this.reportType,
    this.periodStart,
    this.periodEnd,
    this.periodLabel,
    this.totalCogs,
    this.averageInventoryValue,
    this.inventoryTurnoverRatio,
    this.daysInventoryOutstanding,
    this.totalProductsAnalyzed,
    this.slowMovingCount,
    this.deadStockCount,
    this.totalSlowMovingValue,
    this.totalDeadStockValue,
    this.totalInventoryValue,
    this.createdAt,
  });

  factory InventoryTurnoverReport.fromJson(Map<String, dynamic> json) {
    return InventoryTurnoverReport(
      id: json['id'],
      reportType: json['report_type'],
      periodStart: json['period_start'],
      periodEnd: json['period_end'],
      periodLabel: json['period_label'],
      totalCogs: (json['total_cogs']?.toDouble()),
      averageInventoryValue: (json['average_inventory_value']?.toDouble()),
      inventoryTurnoverRatio: (json['inventory_turnover_ratio']?.toDouble()),
      daysInventoryOutstanding: (json['days_inventory_outstanding']?.toDouble()),
      totalProductsAnalyzed: json['total_products_analyzed'],
      slowMovingCount: json['slow_moving_count'],
      deadStockCount: json['dead_stock_count'],
      totalSlowMovingValue: (json['total_slow_moving_value']?.toDouble()),
      totalDeadStockValue: (json['total_dead_stock_value']?.toDouble()),
      totalInventoryValue: (json['total_inventory_value']?.toDouble()),
      createdAt: json['created_at'],
    );
  }
}

class MovementDistribution {
  int? fast;
  int? medium;
  int? slow;
  int? dead;

  MovementDistribution({this.fast, this.medium, this.slow, this.dead});

  factory MovementDistribution.fromJson(Map<String, dynamic> json) {
    return MovementDistribution(
      fast: json['fast'],
      medium: json['medium'],
      slow: json['slow'],
      dead: json['dead'],
    );
  }
}

class AbcDistribution {
  int? a;
  int? b;
  int? c;

  AbcDistribution({this.a, this.b, this.c});

  factory AbcDistribution.fromJson(Map<String, dynamic> json) {
    return AbcDistribution(
      a: json['A'],
      b: json['B'],
      c: json['C'],
    );
  }
}

class ProductAnalysisResult {
  int? id;
  int? productId;
  String? productName;
  String? productCode;
  double? totalQuantitySold;
  double? totalSalesValue;
  double? totalCogs;
  double? averageStockLevel;
  double? turnoverRatio;
  double? daysInventoryOutstanding;
  String? abcCategory;
  String? movementCategory;
  String? movementLabel;
  String? abcLabel;
  double? currentStockValue;
  double? currentStockQty;
  double? stockVelocity;
  double? salesPrice;
  double? purchasePrice;

  ProductAnalysisResult({
    this.id,
    this.productId,
    this.productName,
    this.productCode,
    this.totalQuantitySold,
    this.totalSalesValue,
    this.totalCogs,
    this.averageStockLevel,
    this.turnoverRatio,
    this.daysInventoryOutstanding,
    this.abcCategory,
    this.movementCategory,
    this.movementLabel,
    this.abcLabel,
    this.currentStockValue,
    this.currentStockQty,
    this.stockVelocity,
    this.salesPrice,
    this.purchasePrice,
  });

  factory ProductAnalysisResult.fromJson(Map<String, dynamic> json) {
    return ProductAnalysisResult(
      id: json['id'],
      productId: json['product_id'],
      productName: json['productName'] ?? json['product_name'],
      productCode: json['productCode'] ?? json['product_code'],
      totalQuantitySold: (json['total_quantity_sold']?.toDouble()),
      totalSalesValue: (json['total_sales_value']?.toDouble()),
      totalCogs: (json['total_cogs']?.toDouble()),
      averageStockLevel: (json['average_stock_level']?.toDouble()),
      turnoverRatio: (json['turnover_ratio']?.toDouble()),
      daysInventoryOutstanding: (json['days_inventory_outstanding']?.toDouble()),
      abcCategory: json['abc_category'],
      movementCategory: json['movement_category'],
      movementLabel: json['movement_label'],
      abcLabel: json['abc_label'],
      currentStockValue: (json['current_stock_value']?.toDouble()),
      currentStockQty: (json['current_stock_qty']?.toDouble()),
      stockVelocity: (json['stock_velocity']?.toDouble()),
      salesPrice: (json['sales_price']?.toDouble()),
      purchasePrice: (json['purchase_price']?.toDouble()),
    );
  }

  String get movementStatusLabel {
    switch (movementCategory) {
      case 'fast': return 'سريع';
      case 'medium': return 'متوسط';
      case 'slow': return 'بطيء';
      case 'dead': return 'راكد';
      default: return 'غير محدد';
    }
  }

  String get abcStatusLabel {
    switch (abcCategory) {
      case 'A': return 'A - عالي';
      case 'B': return 'B - متوسط';
      case 'C': return 'C - منخفض';
      default: return 'غير مصنف';
    }
  }
}

class ProductAnalysisResponse {
  List<ProductAnalysisResult>? products;
  ProductAnalysisStats? stats;
  int? currentPage;
  int? lastPage;
  int? total;
  int? perPage;

  ProductAnalysisResponse({
    this.products,
    this.stats,
    this.currentPage,
    this.lastPage,
    this.total,
    this.perPage,
  });

  factory ProductAnalysisResponse.fromJson(Map<String, dynamic> json) {
    return ProductAnalysisResponse(
      products: json['data'] != null
          ? List<ProductAnalysisResult>.from(
              json['data'].map((x) => ProductAnalysisResult.fromJson(x)))
          : [],
      stats: json['stats'] != null
          ? ProductAnalysisStats.fromJson(json['stats'])
          : null,
      currentPage: json['data']?['current_page'],
      lastPage: json['data']?['last_page'],
      total: json['data']?['total'],
      perPage: json['data']?['per_page'],
    );
  }
}

class ProductAnalysisStats {
  int? totalFast;
  int? totalMedium;
  int? totalSlow;
  int? totalDead;

  ProductAnalysisStats({
    this.totalFast,
    this.totalMedium,
    this.totalSlow,
    this.totalDead,
  });

  factory ProductAnalysisStats.fromJson(Map<String, dynamic> json) {
    return ProductAnalysisStats(
      totalFast: json['total_fast'],
      totalMedium: json['total_medium'],
      totalSlow: json['total_slow'],
      totalDead: json['total_dead'],
    );
  }
}

class SlowMovingResult {
  int? businessId;
  String? category;
  Map<String, String>? period;
  int? totalProducts;
  double? totalInventoryValue;
  double? totalInventoryQty;
  List<ProductAnalysisResult>? products;

  SlowMovingResult({
    this.businessId,
    this.category,
    this.period,
    this.totalProducts,
    this.totalInventoryValue,
    this.totalInventoryQty,
    this.products,
  });

  factory SlowMovingResult.fromJson(Map<String, dynamic> json) {
    return SlowMovingResult(
      businessId: json['business_id'],
      category: json['category'],
      period: Map<String, String>.from(json['period'] ?? {}),
      totalProducts: json['total_products'],
      totalInventoryValue: (json['total_inventory_value']?.toDouble()),
      totalInventoryQty: (json['total_inventory_qty']?.toDouble()),
      products: json['products'] != null
          ? List<ProductAnalysisResult>.from(
              json['products'].map((x) => ProductAnalysisResult.fromJson(x)))
          : [],
    );
  }
}

class AbcAnalysisResult {
  int? businessId;
  double? totalValue;
  Map<String, AbcCategoryInfo>? categories;
  List<AbcProductItem>? products;

  AbcAnalysisResult({
    this.businessId,
    this.totalValue,
    this.categories,
    this.products,
  });

  factory AbcAnalysisResult.fromJson(Map<String, dynamic> json) {
    return AbcAnalysisResult(
      businessId: json['business_id'],
      totalValue: (json['total_value']?.toDouble()),
      categories: json['categories'] != null
          ? Map<String, AbcCategoryInfo>.from(
              (json['categories'] as Map).map(
                (k, v) => MapEntry(k, AbcCategoryInfo.fromJson(v)),
              ),
            )
          : null,
      products: json['products'] != null
          ? List<AbcProductItem>.from(
              json['products'].map((x) => AbcProductItem.fromJson(x)))
          : [],
    );
  }
}

class AbcCategoryInfo {
  int? count;
  double? totalValue;
  double? percentage;
  String? description;

  AbcCategoryInfo({this.count, this.totalValue, this.percentage, this.description});

  factory AbcCategoryInfo.fromJson(Map<String, dynamic> json) {
    return AbcCategoryInfo(
      count: json['count'],
      totalValue: (json['total_value']?.toDouble()),
      percentage: (json['percentage']?.toDouble()),
      description: json['description'],
    );
  }
}

class AbcProductItem {
  int? productId;
  String? productName;
  String? productCode;
  double? totalSalesValue;
  double? totalQuantity;
  double? percentageOfTotal;
  double? cumulativePercentage;
  String? abcCategory;
  String? productNameAr;

  AbcProductItem({
    this.productId,
    this.productName,
    this.productCode,
    this.totalSalesValue,
    this.totalQuantity,
    this.percentageOfTotal,
    this.cumulativePercentage,
    this.abcCategory,
    this.productNameAr,
  });

  factory AbcProductItem.fromJson(Map<String, dynamic> json) {
    return AbcProductItem(
      productId: json['product_id'],
      productName: json['product_name'],
      productCode: json['product_code'],
      totalSalesValue: (json['total_sales_value']?.toDouble()),
      totalQuantity: (json['total_quantity']?.toDouble()),
      percentageOfTotal: (json['percentage_of_total']?.toDouble()),
      cumulativePercentage: (json['cumulative_percentage']?.toDouble()),
      abcCategory: json['abc_category'],
    );
  }
}

