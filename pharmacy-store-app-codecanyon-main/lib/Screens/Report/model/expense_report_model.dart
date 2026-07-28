class ExpenseReportModel {
  ExpenseReportModel({
    this.message,
    this.totalExpense,
    this.data,
  });

  ExpenseReportModel.fromJson(dynamic json) {
    message = json['message'];
    totalExpense = json['total_expense'];
    data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }
  String? message;
  num? totalExpense;
  Data? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    map['total_expense'] = totalExpense;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class Data {
  Data({
    this.currentPage,
    this.data,
    this.firstPageUrl,
    this.from,
    this.lastPage,
    this.lastPageUrl,
    this.links,
    this.nextPageUrl,
    this.path,
    this.perPage,
    this.prevPageUrl,
    this.to,
    this.total,
  });

  Data.fromJson(dynamic json) {
    currentPage = json['current_page'];
    if (json['data'] != null) {
      data = [];
      json['data'].forEach((v) {
        data?.add(ExpenseReportData.fromJson(v));
      });
    }
    firstPageUrl = json['first_page_url'];
    from = json['from'];
    lastPage = json['last_page'];
    lastPageUrl = json['last_page_url'];
    if (json['links'] != null) {
      links = [];
      json['links'].forEach((v) {
        links?.add(Links.fromJson(v));
      });
    }
    nextPageUrl = json['next_page_url'];
    path = json['path'];
    perPage = json['per_page'];
    prevPageUrl = json['prev_page_url'];
    to = json['to'];
    total = json['total'];
  }
  num? currentPage;
  List<ExpenseReportData>? data;
  String? firstPageUrl;
  num? from;
  num? lastPage;
  String? lastPageUrl;
  List<Links>? links;
  dynamic nextPageUrl;
  String? path;
  num? perPage;
  dynamic prevPageUrl;
  num? to;
  num? total;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['current_page'] = currentPage;
    if (data != null) {
      map['data'] = data?.map((v) => v.toJson()).toList();
    }
    map['first_page_url'] = firstPageUrl;
    map['from'] = from;
    map['last_page'] = lastPage;
    map['last_page_url'] = lastPageUrl;
    if (links != null) {
      map['links'] = links?.map((v) => v.toJson()).toList();
    }
    map['next_page_url'] = nextPageUrl;
    map['path'] = path;
    map['per_page'] = perPage;
    map['prev_page_url'] = prevPageUrl;
    map['to'] = to;
    map['total'] = total;
    return map;
  }
}

class Links {
  Links({
    this.url,
    this.label,
    this.active,
  });

  Links.fromJson(dynamic json) {
    url = json['url'];
    label = json['label'];
    active = json['active'];
  }
  dynamic url;
  String? label;
  bool? active;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['url'] = url;
    map['label'] = label;
    map['active'] = active;
    return map;
  }
}

class ExpenseReportData {
  ExpenseReportData({
    this.id,
    this.amount,
    this.expenseCategoryId,
    this.userId,
    this.businessId,
    this.expanseFor,
    this.paymentType,
    this.referenceNo,
    this.note,
    this.expenseDate,
    this.createdAt,
    this.updatedAt,
    this.category,
  });

  ExpenseReportData.fromJson(dynamic json) {
    id = json['id'];
    amount = json['amount'];
    expenseCategoryId = json['expense_category_id'];
    userId = json['user_id'];
    businessId = json['business_id'];
    expanseFor = json['expanseFor'];
    paymentType = json['paymentType'];
    referenceNo = json['referenceNo'];
    note = json['note'];
    expenseDate = json['expenseDate'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    category = json['category'] != null ? Category.fromJson(json['category']) : null;
  }
  num? id;
  num? amount;
  num? expenseCategoryId;
  num? userId;
  num? businessId;
  String? expanseFor;
  String? paymentType;
  String? referenceNo;
  String? note;
  String? expenseDate;
  String? createdAt;
  String? updatedAt;
  Category? category;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['amount'] = amount;
    map['expense_category_id'] = expenseCategoryId;
    map['user_id'] = userId;
    map['business_id'] = businessId;
    map['expanseFor'] = expanseFor;
    map['paymentType'] = paymentType;
    map['referenceNo'] = referenceNo;
    map['note'] = note;
    map['expenseDate'] = expenseDate;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (category != null) {
      map['category'] = category?.toJson();
    }
    return map;
  }
}

class Category {
  Category({
    this.id,
    this.categoryName,
  });

  Category.fromJson(dynamic json) {
    id = json['id'];
    categoryName = json['categoryName'];
  }
  num? id;
  String? categoryName;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['categoryName'] = categoryName;
    return map;
  }
}
