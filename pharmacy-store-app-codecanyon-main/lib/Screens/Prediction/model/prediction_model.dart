class PredictionSettings {
  int? id;
  int? businessId;
  bool predictionEnabled;
  String forecastPeriod;
  int forecastDays;
  int historicalMonths;
  String predictionMethod;
  bool seasonalAdjustment;
  double safetyStockMultiplier;
  int leadTimeDays;
  double confidenceThreshold;
  bool autoOrderEnabled;

  PredictionSettings({
    this.id,
    this.businessId,
    required this.predictionEnabled,
    required this.forecastPeriod,
    required this.forecastDays,
    required this.historicalMonths,
    required this.predictionMethod,
    required this.seasonalAdjustment,
    required this.safetyStockMultiplier,
    required this.leadTimeDays,
    required this.confidenceThreshold,
    required this.autoOrderEnabled,
  });

  factory PredictionSettings.defaults() {
    return PredictionSettings(
      predictionEnabled: true,
      forecastPeriod: 'daily',
      forecastDays: 30,
      historicalMonths: 6,
      predictionMethod: 'combined',
      seasonalAdjustment: true,
      safetyStockMultiplier: 1.5,
      leadTimeDays: 7,
      confidenceThreshold: 0.7,
      autoOrderEnabled: false,
    );
  }

  factory PredictionSettings.fromJson(Map<String, dynamic> json) {
    return PredictionSettings(
      id: json['id'],
      businessId: json['business_id'],
      predictionEnabled: json['prediction_enabled'] ?? true,
      forecastPeriod: json['forecast_period'] ?? 'daily',
      forecastDays: json['forecast_days'] ?? 30,
      historicalMonths: json['historical_months'] ?? 6,
      predictionMethod: json['prediction_method'] ?? 'combined',
      seasonalAdjustment: json['seasonal_adjustment'] ?? true,
      safetyStockMultiplier: (json['safety_stock_multiplier'] ?? 1.5).toDouble(),
      leadTimeDays: json['lead_time_days'] ?? 7,
      confidenceThreshold: (json['confidence_threshold'] ?? 0.7).toDouble(),
      autoOrderEnabled: json['auto_order_enabled'] ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'prediction_enabled': predictionEnabled,
      'forecast_period': forecastPeriod,
      'forecast_days': forecastDays,
      'historical_months': historicalMonths,
      'prediction_method': predictionMethod,
      'seasonal_adjustment': seasonalAdjustment,
      'safety_stock_multiplier': safetyStockMultiplier,
      'lead_time_days': leadTimeDays,
      'confidence_threshold': confidenceThreshold,
      'auto_order_enabled': autoOrderEnabled,
    };
  }
}

class SalesForecast {
  int? id;
  int? businessId;
  int? productId;
  String? forecastDate;
  double predictedQuantity;
  double predictedRevenue;
  double? confidenceScore;
  double? lowerBound;
  double? upperBound;
  String? methodUsed;
  bool? isActive;

  SalesForecast({
    this.id,
    this.businessId,
    this.productId,
    this.forecastDate,
    required this.predictedQuantity,
    required this.predictedRevenue,
    this.confidenceScore,
    this.lowerBound,
    this.upperBound,
    this.methodUsed,
    this.isActive,
  });

  factory SalesForecast.defaults() {
    return SalesForecast(
      predictedQuantity: 0,
      predictedRevenue: 0,
    );
  }

  factory SalesForecast.fromJson(Map<String, dynamic> json) {
    return SalesForecast(
      id: json['id'],
      businessId: json['business_id'],
      productId: json['product_id'],
      forecastDate: json['forecast_date'],
      predictedQuantity: (json['predicted_quantity'] ?? 0).toDouble(),
      predictedRevenue: (json['predicted_revenue'] ?? 0).toDouble(),
      confidenceScore: (json['confidence_score']?.toDouble()),
      lowerBound: (json['lower_bound']?.toDouble()),
      upperBound: (json['upper_bound']?.toDouble()),
      methodUsed: json['method_used'],
      isActive: json['is_active'],
    );
  }
}

class ForecastResult {
  int? productId;
  String? productName;
  List<SalesForecast>? forecasts;
  ForecastSummary? summary;

  ForecastResult({this.productId, this.productName, this.forecasts, this.summary});

  factory ForecastResult.fromJson(Map<String, dynamic> json) {
    return ForecastResult(
      productId: json['product_id'],
      productName: json['product_name'],
      forecasts: json['forecasts'] != null
          ? List<SalesForecast>.from(
              json['forecasts'].map((x) => SalesForecast.fromJson(x)))
          : [],
      summary: json['summary'] != null
          ? ForecastSummary.fromJson(json['summary'])
          : null,
    );
  }
}

class ForecastSummary {
  double? totalPredictedQty;
  double? totalPredictedRevenue;
  double? dailyAverage;
  int? forecastDays;
  String? method;

  ForecastSummary({
    this.totalPredictedQty,
    this.totalPredictedRevenue,
    this.dailyAverage,
    this.forecastDays,
    this.method,
  });

  factory ForecastSummary.fromJson(Map<String, dynamic> json) {
    return ForecastSummary(
      totalPredictedQty: (json['total_predicted_qty']?.toDouble()),
      totalPredictedRevenue: (json['total_predicted_revenue']?.toDouble()),
      dailyAverage: (json['daily_average']?.toDouble()),
      forecastDays: json['forecast_days'],
      method: json['method'],
    );
  }
}

class DemandReportItem {
  int? productId;
  String? productName;
  double? currentStock;
  double? totalPredictedDemand;
  double? averageConfidence;
  double? stockCoverageDays;
  bool? needsReorder;
  List<dynamic>? periodData;

  DemandReportItem({
    this.productId,
    this.productName,
    this.currentStock,
    this.totalPredictedDemand,
    this.averageConfidence,
    this.stockCoverageDays,
    this.needsReorder,
    this.periodData,
  });

  factory DemandReportItem.fromJson(Map<String, dynamic> json) {
    return DemandReportItem(
      productId: json['product_id'],
      productName: json['product_name'],
      currentStock: (json['current_stock']?.toDouble()),
      totalPredictedDemand: (json['total_predicted_demand']?.toDouble()),
      averageConfidence: (json['average_confidence']?.toDouble()),
      stockCoverageDays: (json['stock_coverage_days']?.toDouble()),
      needsReorder: json['needs_reorder'],
      periodData: json['period_data'],
    );
  }
}

class DemandReport {
  int? businessId;
  String? period;
  int? forecastDays;
  int? productsCount;
  List<DemandReportItem>? products;
  String? generatedAt;

  DemandReport({
    this.businessId,
    this.period,
    this.forecastDays,
    this.productsCount,
    this.products,
    this.generatedAt,
  });

  factory DemandReport.fromJson(Map<String, dynamic> json) {
    return DemandReport(
      businessId: json['business_id'],
      period: json['period'],
      forecastDays: json['forecast_days'],
      productsCount: json['products_count'],
      products: json['products'] != null
          ? List<DemandReportItem>.from(
              json['products'].map((x) => DemandReportItem.fromJson(x)))
          : [],
      generatedAt: json['generated_at'],
    );
  }
}

class ReorderPoint {
  int? productId;
  double? dailyAverageDemand;
  int? leadTimeDays;
  double? leadTimeDemand;
  double? stdDev;
  double? safetyStock;
  double? reorderPoint;
  double? economicOrderQty;
  String? serviceLevel;

  ReorderPoint({
    this.productId,
    this.dailyAverageDemand,
    this.leadTimeDays,
    this.leadTimeDemand,
    this.stdDev,
    this.safetyStock,
    this.reorderPoint,
    this.economicOrderQty,
    this.serviceLevel,
  });

  factory ReorderPoint.fromJson(Map<String, dynamic> json) {
    return ReorderPoint(
      productId: json['product_id'],
      dailyAverageDemand: (json['daily_average_demand']?.toDouble()),
      leadTimeDays: json['lead_time_days'],
      leadTimeDemand: (json['lead_time_demand']?.toDouble()),
      stdDev: (json['std_dev']?.toDouble()),
      safetyStock: (json['safety_stock']?.toDouble()),
      reorderPoint: (json['reorder_point']?.toDouble()),
      economicOrderQty: (json['economic_order_qty']?.toDouble()),
      serviceLevel: json['service_level'],
    );
  }
}

