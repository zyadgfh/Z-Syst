class PurchaseDetailsModel {
  PurchaseDetailsModel({
    this.message,
    this.data,
  });

  PurchaseDetailsModel.fromJson(dynamic json) {
    message = json['message'];
    data = json['data'] != null ? PurchaseDetailsData.fromJson(json['data']) : null;
  }
  String? message;
  PurchaseDetailsData? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class PurchaseDetailsData {
  PurchaseDetailsData({
    this.id,
    this.partyId,
    this.businessId,
    this.userId,
    this.taxId,
    this.discountAmount,
    this.taxAmount,
    this.dueAmount,
    this.paidAmount,
    this.totalAmount,
    this.invoiceNumber,
    this.isPaid,
    this.purchaseData,
    this.paymentType,
    this.purchaseDate,
    this.note,
    this.createdAt,
    this.updatedAt,
    this.tax,
    this.party,
    this.details,
    this.returns,
    this.purchaseBy,
  });

  PurchaseDetailsData.fromJson(dynamic json) {
    id = json['id'];
    partyId = json['party_id'];
    businessId = json['business_id'];
    userId = json['user_id'];
    taxId = json['tax_id'];
    discountAmount = json['discountAmount'];
    taxAmount = json['tax_amount'];
    dueAmount = json['dueAmount'];
    paidAmount = json['paidAmount'];
    totalAmount = json['totalAmount'];
    invoiceNumber = json['invoiceNumber'];
    isPaid = json['isPaid'];
    paymentType = json['paymentType'];
    purchaseDate = json['purchaseDate'];
    note = json['note'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    tax = json['tax'] != null ? Tax.fromJson(json['tax']) : null;
    purchaseData = json['purchase_data'] != null ? PurchaseDetailsData.fromJson(json['purchase_data']) : null;
    purchaseBy = json['user'] != null ? User.fromJson(json['user']) : null;
    party = json['party'] != null ? Party.fromJson(json['party']) : null;
    if (json['details'] != null) {
      details = [];
      json['details'].forEach((v) {
        details?.add(Details.fromJson(v));
      });
    }
    if (json['purchase_returns'] != null) {
      returns = [];
      json['purchase_returns'].forEach((v) {
        returns?.add(PurchaseReturnInPurchase.fromJson(v));
      });
    }
  }
  num? id;
  num? partyId;
  num? businessId;
  num? userId;
  num? taxId;
  num? discountAmount;
  num? taxAmount;
  num? dueAmount;
  num? paidAmount;
  num? totalAmount;
  PurchaseDetailsData? purchaseData;
  String? invoiceNumber;
  bool? isPaid;
  String? paymentType;
  String? purchaseDate;
  dynamic note;
  String? createdAt;
  String? updatedAt;
  Tax? tax;
  User? purchaseBy;
  Party? party;
  List<Details>? details;
  List<PurchaseReturnInPurchase>? returns;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['party_id'] = partyId;
    map['business_id'] = businessId;
    map['user_id'] = userId;
    map['tax_id'] = taxId;
    map['discountAmount'] = discountAmount;
    map['tax_amount'] = taxAmount;
    map['dueAmount'] = dueAmount;
    map['paidAmount'] = paidAmount;
    map['totalAmount'] = totalAmount;
    map['invoiceNumber'] = invoiceNumber;
    map['isPaid'] = isPaid;
    map['paymentType'] = paymentType;
    map['purchaseDate'] = purchaseDate;
    map['note'] = note;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (tax != null) {
      map['tax'] = tax?.toJson();
    }
    if (purchaseBy != null) {
      map['user'] = purchaseBy?.toJson();
    }
    if (party != null) {
      map['party'] = party?.toJson();
    }
    if (details != null) {
      map['details'] = details?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class Details {
  Details({
    this.id,
    this.purchaseId,
    this.productId,
    this.purchaseWithTax,
    this.quantities,
    this.batchNo,
    this.purchaseWithoutTax,
    this.profitPercent,
    this.salesPrice,
    this.wholesalePrice,
    this.product,
  });

  Details.fromJson(dynamic json) {
    id = json['id'];
    purchaseId = json['purchase_id'];
    productId = json['product_id'];
    purchaseWithTax = json['purchase_with_tax'];
    quantities = json['quantities'];
    batchNo = json['batch_no'];
    purchaseWithoutTax = json['purchase_without_tax'];
    profitPercent = json['profit_percent'];
    salesPrice = json['sales_price'];
    wholesalePrice = json['wholesale_price'];
    product = json['product'] != null ? Product.fromJson(json['product']) : null;
  }
  num? id;
  num? purchaseId;
  num? productId;
  num? purchaseWithTax;
  num? quantities;
  String? batchNo;
  num? purchaseWithoutTax;
  num? profitPercent;
  num? salesPrice;
  num? wholesalePrice;
  Product? product;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['purchase_id'] = purchaseId;
    map['product_id'] = productId;
    map['purchase_with_tax'] = purchaseWithTax;
    map['quantities'] = quantities;
    map['batch_no'] = batchNo;
    map['purchase_without_tax'] = purchaseWithoutTax;
    map['profit_percent'] = profitPercent;
    map['sales_price'] = salesPrice;
    map['wholesale_price'] = wholesalePrice;
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
    this.taxType,
  });

  Product.fromJson(dynamic json) {
    id = json['id'];
    productName = json['productName'];
    taxType = json['tax_type'];
  }
  num? id;
  String? productName;
  String? taxType;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['productName'] = productName;
    map['tax_type'] = taxType;
    return map;
  }
}

class Party {
  Party({
    this.id,
    this.name,
    this.phone,
  });

  Party.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    phone = json['phone'];
  }
  num? id;
  String? name;
  String? phone;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
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
  String? name;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    return map;
  }
}

class Tax {
  Tax({
    this.id,
    this.name,
    this.businessId,
    this.rate,
    this.subTax,
    this.status,
    this.createdAt,
    this.updatedAt,
  });

  Tax.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    businessId = json['business_id'];
    rate = json['rate'];
    subTax = json['sub_tax'];
    status = json['status'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }
  num? id;
  String? name;
  num? businessId;
  num? rate;
  dynamic subTax;
  bool? status;
  String? createdAt;
  String? updatedAt;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['business_id'] = businessId;
    map['rate'] = rate;
    map['sub_tax'] = subTax;
    map['status'] = status;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    return map;
  }
}

class PurchaseReturnInPurchase {
  PurchaseReturnInPurchase({
    this.id,
    this.businessId,
    this.purchaseId,
    this.invoiceNo,
    this.returnDate,
    this.createdAt,
    this.updatedAt,
    this.details,
  });

  PurchaseReturnInPurchase.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    purchaseId = json['purchase_id'];
    invoiceNo = json['invoice_no'];
    returnDate = json['return_date'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    if (json['details'] != null) {
      details = [];
      json['details'].forEach((v) {
        details?.add(RetrurnDetails.fromJson(v));
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
  List<RetrurnDetails>? details;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['purchase_id'] = purchaseId;
    map['invoice_no'] = invoiceNo;
    map['return_date'] = returnDate;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (details != null) {
      map['details'] = details?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class RetrurnDetails {
  RetrurnDetails({
    this.id,
    this.businessId,
    this.purchaseReturnId,
    this.purchaseDetailId,
    this.returnAmount,
    this.returnQty,
  });

  RetrurnDetails.fromJson(dynamic json) {
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
