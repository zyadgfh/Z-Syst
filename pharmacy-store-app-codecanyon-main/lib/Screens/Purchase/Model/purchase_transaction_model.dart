class PurchaseTransaction {
  PurchaseTransaction({
    this.id,
    this.partyId,
    this.businessId,
    this.userId,
    this.discountAmount,
    this.dueAmount,
    this.paidAmount,
    this.totalAmount,
    this.invoiceNumber,
    this.isPaid,
    this.hasReturn,
    this.paymentType,
    this.purchaseDate,
    this.createdAt,
    this.updatedAt,
    this.user,
    this.party,
    this.details,
    this.purchaseReturns
  });

  PurchaseTransaction.fromJson(dynamic json) {
    id = json['id'];
    partyId = json['party_id'];
    businessId = json['business_id'];
    userId = json['user_id'];
    discountAmount = json['discountAmount'];
    dueAmount = json['dueAmount'];
    paidAmount = json['paidAmount'];
    hasReturn = json['has_return'];
    totalAmount = json['totalAmount'];
    invoiceNumber = json['invoiceNumber'];
    isPaid = json['isPaid'];
    paymentType = json['paymentType'];
    purchaseDate = json['purchaseDate'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    user = json['user'] != null ? User.fromJson(json['user']) : null;
    party = json['party'] != null ? Party.fromJson(json['party']) : null;
    if (json['details'] != null) {
      details = [];
      json['details'].forEach((v) {
        details?.add(PurchaseDetails.fromJson(v));
      });
    }
    if (json['purchase_returns'] != null) {
      purchaseReturns = [];
      json['purchase_returns'].forEach((v) {
        purchaseReturns?.add(PurchaseReturn.fromJson(v));
      });
    }
  }
  num? id;
  num? partyId;
  num? businessId;
  num? userId;
  num? discountAmount;
  num? dueAmount;
  num? paidAmount;
  num? totalAmount;
  String? invoiceNumber;
  bool? isPaid;
  bool? hasReturn;
  String? paymentType;
  String? purchaseDate;
  String? createdAt;
  String? updatedAt;
  User? user;
  Party? party;
  List<PurchaseDetails>? details;
  List<PurchaseReturn>? purchaseReturns;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['party_id'] = partyId;
    map['business_id'] = businessId;
    map['user_id'] = userId;
    map['discountAmount'] = discountAmount;
    map['dueAmount'] = dueAmount;
    map['paidAmount'] = paidAmount;
    map['totalAmount'] = totalAmount;
    map['invoiceNumber'] = invoiceNumber;
    map['isPaid'] = isPaid;
    map['has_return'] = hasReturn;
    map['paymentType'] = paymentType;
    map['purchaseDate'] = purchaseDate;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (user != null) {
      map['user'] = user?.toJson();
    }
    if (party != null) {
      map['party'] = party?.toJson();
    }
    if (details != null) {
      map['details'] = details?.map((v) => v.toJson()).toList();
    }
    if (purchaseReturns != null) {
      map['purchase_returns'] = purchaseReturns?.map((v) => v.toJson()).toList();
    }
    return map;
  }

}

class PurchaseDetails {
  PurchaseDetails({
    this.id,
    this.purchaseId,
    this.productId,
    this.productPurchasePrice,
    this.quantities,
    this.product,
  });

  PurchaseDetails.fromJson(dynamic json) {
    id = json['id'];
    purchaseId = json['purchase_id'];
    productId = json['product_id'];
    productPurchasePrice = json['productPurchasePrice'];
    quantities = json['quantities'];
    product = json['product'] != null ? Product.fromJson(json['product']) : null;
  }
  num? id;
  num? purchaseId;
  num? productId;
  num? productPurchasePrice;
  num? quantities;
  Product? product;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['purchase_id'] = purchaseId;
    map['product_id'] = productId;
    map['productPurchasePrice'] = productPurchasePrice;
    map['quantities'] = quantities;
    if (product != null) {
      map['product'] = product?.toJson();
    }
    return map;
  }
}

class Product {
  Product({
    this.id,
    this.productName,
    this.categoryId,
    this.category,
  });

  Product.fromJson(dynamic json) {
    id = json['id'];
    productName = json['productName'];
    categoryId = json['category_id'];
    category = json['category'] != null ? Category.fromJson(json['category']) : null;
  }
  num? id;
  String? productName;
  num? categoryId;
  Category? category;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['productName'] = productName;
    map['category_id'] = categoryId;
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

class Party {
  Party({
    this.id,
    this.name,
    this.email,
    this.phone,
  });

  Party.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    email = json['email'];
    phone = json['phone'];
  }
  num? id;
  String? name;
  String? email;
  String? phone;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['email'] = email;
    map['phone'] = phone;
    return map;
  }
}

class User {
  User({
    this.id,
    this.name,
  });

  User.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
  }
  num? id;
  dynamic name;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    return map;
  }
}

///----------purchase return----------------------------
class PurchaseReturn {
  PurchaseReturn({
    this.id,
    this.businessId,
    this.purchaseId,
    this.invoiceNo,
    this.returnDate,
    this.createdAt,
    this.updatedAt,
    this.purchaseReturnDetails,
  });

  PurchaseReturn.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    purchaseId = json['purchase_id'];
    invoiceNo = json['invoice_no'];
    returnDate = json['return_date'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    if (json['details'] != null) {
      purchaseReturnDetails = [];
      json['details'].forEach((v) {
        purchaseReturnDetails?.add(PurchaseReturnDetails.fromJson(v));
      });
    }
  }
  num? id;
  num? businessId;
  num? purchaseId;
  String? invoiceNo;
  String? returnDate;
  String? createdAt;
  String? updatedAt;
  List<PurchaseReturnDetails>? purchaseReturnDetails;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['purchase_id'] = purchaseId;
    map['invoice_no'] = invoiceNo;
    map['return_date'] = returnDate;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (purchaseReturnDetails != null) {
      map['details'] = purchaseReturnDetails?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class PurchaseReturnDetails {
  PurchaseReturnDetails({
    this.id,
    this.businessId,
    this.purchaseReturnId,
    this.purchaseDetailId,
    this.returnAmount,
    this.returnQty,
  });

  PurchaseReturnDetails.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    purchaseReturnId = json['purchase_return_id'];
    purchaseDetailId = json['purchase_detail_id'];
    returnAmount = json['return_amount'];
    returnQty = json['return_qty'];
  }
  num? id;
  num? businessId;
  num? purchaseReturnId;
  num? purchaseDetailId;
  num? returnAmount;
  num? returnQty;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['purchase_return_id'] = purchaseReturnId;
    map['purchase_detail_id'] = purchaseDetailId;
    map['return_amount'] = returnAmount;
    map['return_qty'] = returnQty;
    return map;
  }
}
