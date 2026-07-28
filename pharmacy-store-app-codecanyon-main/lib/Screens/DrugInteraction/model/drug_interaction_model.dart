class DrugInteractionCheckRequest {
  final List<int> productIds;

  DrugInteractionCheckRequest({required this.productIds});

  Map<String, dynamic> toJson() => {
        'product_ids': productIds,
      };
}

class DrugInteractionCheckResponse {
  final String? message;
  final DrugInteractionCheckData? data;

  DrugInteractionCheckResponse({this.message, this.data});

  factory DrugInteractionCheckResponse.fromJson(Map<String, dynamic> json) {
    return DrugInteractionCheckResponse(
      message: json['message'],
      data: json['data'] != null ? DrugInteractionCheckData.fromJson(json['data']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class DrugInteractionCheckData {
  final List<CheckedProduct>? checkedProducts;
  final List<DrugInteractionResult>? interactions;
  final int? totalInteractions;
  final bool? hasCritical;
  final GroupedInteractions? grouped;

  DrugInteractionCheckData({
    this.checkedProducts,
    this.interactions,
    this.totalInteractions,
    this.hasCritical,
    this.grouped,
  });

  factory DrugInteractionCheckData.fromJson(Map<String, dynamic> json) {
    return DrugInteractionCheckData(
      checkedProducts: json['checked_products'] != null
          ? (json['checked_products'] as List).map((e) => CheckedProduct.fromJson(e)).toList()
          : [],
      interactions: json['interactions'] != null
          ? (json['interactions'] as List).map((e) => DrugInteractionResult.fromJson(e)).toList()
          : [],
      totalInteractions: json['total_interactions'],
      hasCritical: json['has_critical'],
      grouped: json['grouped'] != null ? GroupedInteractions.fromJson(json['grouped']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    if (checkedProducts != null) {
      map['checked_products'] = checkedProducts?.map((e) => e.toJson()).toList();
    }
    if (interactions != null) {
      map['interactions'] = interactions?.map((e) => e.toJson()).toList();
    }
    map['total_interactions'] = totalInteractions;
    map['has_critical'] = hasCritical;
    if (grouped != null) {
      map['grouped'] = grouped?.toJson();
    }
    return map;
  }
}

class CheckedProduct {
  final int? productId;
  final String? productName;
  final String? genericName;
  final String? searchName;

  CheckedProduct({
    this.productId,
    this.productName,
    this.genericName,
    this.searchName,
  });

  factory CheckedProduct.fromJson(Map<String, dynamic> json) {
    return CheckedProduct(
      productId: json['product_id'],
      productName: json['product_name'],
      genericName: json['generic_name'],
      searchName: json['search_name'],
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['product_id'] = productId;
    map['product_name'] = productName;
    map['generic_name'] = genericName;
    map['search_name'] = searchName;
    return map;
  }
}

class DrugInteractionResult {
  final int? id;
  final String? severity;
  final String? severityLabel;
  final String? severityColor;
  final String? description;
  final String? mechanism;
  final String? recommendation;
  final String? source;
  final String? category;
  final InteractionDrug? drugA;
  final InteractionDrug? drugB;

  DrugInteractionResult({
    this.id,
    this.severity,
    this.severityLabel,
    this.severityColor,
    this.description,
    this.mechanism,
    this.recommendation,
    this.source,
    this.category,
    this.drugA,
    this.drugB,
  });

  factory DrugInteractionResult.fromJson(Map<String, dynamic> json) {
    return DrugInteractionResult(
      id: json['id'],
      severity: json['severity'],
      severityLabel: json['severity_label'],
      severityColor: json['severity_color'],
      description: json['description'],
      mechanism: json['mechanism'],
      recommendation: json['recommendation'],
      source: json['source'],
      category: json['category'],
      drugA: json['drug_a'] != null ? InteractionDrug.fromJson(json['drug_a']) : null,
      drugB: json['drug_b'] != null ? InteractionDrug.fromJson(json['drug_b']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['severity'] = severity;
    map['severity_label'] = severityLabel;
    map['severity_color'] = severityColor;
    map['description'] = description;
    map['mechanism'] = mechanism;
    map['recommendation'] = recommendation;
    map['source'] = source;
    map['category'] = category;
    if (drugA != null) {
      map['drug_a'] = drugA?.toJson();
    }
    if (drugB != null) {
      map['drug_b'] = drugB?.toJson();
    }
    return map;
  }
}

class InteractionDrug {
  final int? productId;
  final String? productName;
  final String? genericName;
  final String? matchedName;

  InteractionDrug({
    this.productId,
    this.productName,
    this.genericName,
    this.matchedName,
  });

  factory InteractionDrug.fromJson(Map<String, dynamic> json) {
    return InteractionDrug(
      productId: json['product_id'],
      productName: json['product_name'],
      genericName: json['generic_name'],
      matchedName: json['matched_name'],
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['product_id'] = productId;
    map['product_name'] = productName;
    map['generic_name'] = genericName;
    map['matched_name'] = matchedName;
    return map;
  }
}

class GroupedInteractions {
  final List<DrugInteractionResult>? contraindicated;
  final List<DrugInteractionResult>? severe;
  final List<DrugInteractionResult>? moderate;
  final List<DrugInteractionResult>? minor;

  GroupedInteractions({
    this.contraindicated,
    this.severe,
    this.moderate,
    this.minor,
  });

  factory GroupedInteractions.fromJson(Map<String, dynamic> json) {
    return GroupedInteractions(
      contraindicated: json['contraindicated'] != null
          ? (json['contraindicated'] as List).map((e) => DrugInteractionResult.fromJson(e)).toList()
          : [],
      severe: json['severe'] != null
          ? (json['severe'] as List).map((e) => DrugInteractionResult.fromJson(e)).toList()
          : [],
      moderate: json['moderate'] != null
          ? (json['moderate'] as List).map((e) => DrugInteractionResult.fromJson(e)).toList()
          : [],
      minor: json['minor'] != null
          ? (json['minor'] as List).map((e) => DrugInteractionResult.fromJson(e)).toList()
          : [],
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    if (contraindicated != null) {
      map['contraindicated'] = contraindicated?.map((e) => e.toJson()).toList();
    }
    if (severe != null) {
      map['severe'] = severe?.map((e) => e.toJson()).toList();
    }
    if (moderate != null) {
      map['moderate'] = moderate?.map((e) => e.toJson()).toList();
    }
    if (minor != null) {
      map['minor'] = minor?.map((e) => e.toJson()).toList();
    }
    return map;
  }
}

// === Paginated Model for listing all interactions ===

class DrugInteractionListModel {
  final String? message;
  final DrugInteractionPaginationData? data;

  DrugInteractionListModel({this.message, this.data});

  factory DrugInteractionListModel.fromJson(Map<String, dynamic> json) {
    return DrugInteractionListModel(
      message: json['message'],
      data: json['data'] != null ? DrugInteractionPaginationData.fromJson(json['data']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class DrugInteractionPaginationData {
  final int? currentPage;
  final List<DrugInteractionData>? interactions;
  final int? from;
  final int? lastPage;
  final int? perPage;
  final int? to;
  final int? total;
  final String? nextPageUrl;
  final String? prevPageUrl;

  DrugInteractionPaginationData({
    this.currentPage,
    this.interactions,
    this.from,
    this.lastPage,
    this.perPage,
    this.to,
    this.total,
    this.nextPageUrl,
    this.prevPageUrl,
  });

  factory DrugInteractionPaginationData.fromJson(Map<String, dynamic> json) {
    return DrugInteractionPaginationData(
      currentPage: json['current_page'],
      interactions: json['data'] != null
          ? (json['data'] as List).map((e) => DrugInteractionData.fromJson(e)).toList()
          : [],
      from: json['from'],
      lastPage: json['last_page'],
      perPage: json['per_page'],
      to: json['to'],
      total: json['total'],
      nextPageUrl: json['next_page_url'],
      prevPageUrl: json['prev_page_url'],
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['current_page'] = currentPage;
    if (interactions != null) {
      map['data'] = interactions?.map((e) => e.toJson()).toList();
    }
    map['from'] = from;
    map['last_page'] = lastPage;
    map['per_page'] = perPage;
    map['to'] = to;
    map['total'] = total;
    map['next_page_url'] = nextPageUrl;
    map['prev_page_url'] = prevPageUrl;
    return map;
  }
}

class DrugInteractionData {
  final int? id;
  final int? businessId;
  final String? drugAName;
  final String? drugBName;
  final String? severity;
  final String? description;
  final String? mechanism;
  final String? recommendation;
  final String? source;
  final String? category;
  final String? createdAt;
  final String? updatedAt;

  DrugInteractionData({
    this.id,
    this.businessId,
    this.drugAName,
    this.drugBName,
    this.severity,
    this.description,
    this.mechanism,
    this.recommendation,
    this.source,
    this.category,
    this.createdAt,
    this.updatedAt,
  });

  factory DrugInteractionData.fromJson(Map<String, dynamic> json) {
    return DrugInteractionData(
      id: json['id'],
      businessId: json['business_id'],
      drugAName: json['drug_a_name'],
      drugBName: json['drug_b_name'],
      severity: json['severity'],
      description: json['description'],
      mechanism: json['mechanism'],
      recommendation: json['recommendation'],
      source: json['source'],
      category: json['category'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['drug_a_name'] = drugAName;
    map['drug_b_name'] = drugBName;
    map['severity'] = severity;
    map['description'] = description;
    map['mechanism'] = mechanism;
    map['recommendation'] = recommendation;
    map['source'] = source;
    map['category'] = category;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    return map;
  }
}

