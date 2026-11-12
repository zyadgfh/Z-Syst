class UserRoleModel {
  UserRoleModel({
    this.id,
    this.businessId,
    this.email,
    this.name,
    this.role,
    this.phone,
    this.image,
    this.lang,
    this.visibility,
    this.createdAt,
    this.updatedAt,
  });

  UserRoleModel.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    email = json['email'];
    name = json['name'];
    role = json['role'];
    phone = json['phone'];
    image = json['image'];
    lang = json['lang'];
    visibility = json['visibility'] != null ? Permission.fromJson(json['visibility']) : null;
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }
  num? id;
  num? businessId;
  String? email;
  String? name;
  String? role;
  dynamic phone;
  dynamic image;
  dynamic lang;
  Permission? visibility;
  String? createdAt;
  String? updatedAt;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['email'] = email;
    map['name'] = name;
    map['role'] = role;
    map['phone'] = phone;
    map['image'] = image;
    map['lang'] = lang;
    if (visibility != null) {
      map['visibility'] = visibility?.toJson();
    }
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    return map;
  }
}

class Permission {
  Permission({
    this.addExpensePermission,
    this.dueListPermission,
    this.lossProfitPermission,
    this.partiesPermission,
    this.productPermission,
    this.profileEditPermission,
    this.purchaseListPermission,
    this.purchasePermission,
    this.reportsPermission,
    this.salePermission,
    this.salesListPermission,
    this.stockPermission,
  });

  Permission.fromJson(dynamic json) {
    addExpensePermission = (json['add_expense_permission'] ?? json['addExpensePermission']);
    dueListPermission = (json['due_list_permission'] ?? json['dueListPermission']);
    lossProfitPermission = (json['loss_profit_permission'] ?? json['lossProfitPermission']);
    partiesPermission = (json['parties_permission'] ?? json['partiesPermission']);
    productPermission = (json['product_permission'] ?? json['productPermission']);
    profileEditPermission = (json['profile_edit_permission'] ?? json['profileEditPermission']);
    purchaseListPermission = (json['purchase_list_permission'] ?? json['purchaseListPermission']);
    purchasePermission = (json['purchase_permission'] ?? json['purchasePermission']);
    reportsPermission = (json['reports_permission'] ?? json['reportsPermission']);
    salePermission = (json['sale_permission'] ?? json['salePermission']);
    salesListPermission = (json['sales_list_permission'] ?? json['salesListPermission']);
    stockPermission = (json['stock_permission'] ?? json['stockPermission']);
  }
  bool? addExpensePermission;
  bool? dueListPermission;
  bool? lossProfitPermission;
  bool? partiesPermission;
  bool? productPermission;
  bool? profileEditPermission;
  bool? purchaseListPermission;
  bool? purchasePermission;
  bool? reportsPermission;
  bool? salePermission;
  bool? salesListPermission;
  bool? stockPermission;

  Map<String, String> toJson() {
    final map = <String, String>{};
    map['addExpensePermission'] = addExpensePermission.toString();
    map['dueListPermission'] = dueListPermission.toString();
    map['lossProfitPermission'] = lossProfitPermission.toString();
    map['partiesPermission'] = partiesPermission.toString();
    map['productPermission'] = productPermission.toString();
    map['profileEditPermission'] = profileEditPermission.toString();
    map['purchaseListPermission'] = purchaseListPermission.toString();
    map['purchasePermission'] = purchasePermission.toString();
    map['reportsPermission'] = reportsPermission.toString();
    map['salePermission'] = salePermission.toString();
    map['salesListPermission'] = salesListPermission.toString();
    map['stockPermission'] = stockPermission.toString();
    return map;
  }
}
