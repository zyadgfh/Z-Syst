import 'package:mobile_pos/Screens/Customers/Model/parties_model.dart';
import 'package:mobile_pos/Screens/tax%20rates/model/tax_model.dart';

class SalesDetailsModel {
  SalesDetailsModel({
    this.message,
    this.data,
  });

  SalesDetailsModel.fromJson(dynamic json) {
    message = json['message'];
    data = json['data'] != null ? SalesDetailsData.fromJson(json['data']) : null;
  }
  String? message;
  SalesDetailsData? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class SalesDetailsData {
  SalesDetailsData({
    this.id,
    this.businessId,
    this.partyId,
    this.userId,
    this.taxId,
    this.discountAmount,
    this.dueAmount,
    this.isPaid,
    this.taxAmount,
    this.salesData,
    this.paidAmount,
    this.totalAmount,
    this.lossProfit,
    this.paymentType,
    this.invoiceNumber,
    this.returns,
    this.saleDate,
    this.meta,
    this.createdAt,
    this.updatedAt,
    this.tax,
    this.party,
    this.details,
  });

  SalesDetailsData.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    partyId = json['party_id'];
    userId = json['user_id'];
    taxId = json['tax_id'];
    discountAmount = json['discountAmount'];
    dueAmount = json['dueAmount'];
    isPaid = json['isPaid'];
    taxAmount = json['tax_amount'];
    paidAmount = json['paidAmount'];
    totalAmount = json['totalAmount'];
    lossProfit = json['lossProfit'];
    paymentType = json['paymentType'];
    invoiceNumber = json['invoiceNumber'];
    saleDate = json['saleDate'];
    meta = json['meta'] != null ? SalesDetailsMeta.fromJson(json['meta']) : null;
    salesData = json['sale_data'] != null ? SalesDetailsData.fromJson(json['sale_data']) : null;
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    salesBy = json['user'] != null ? User.fromJson(json['user']) : null;
    tax = json['tax'] != null ? TaxModel.fromJson(json['tax']) : null;
    party = json['party'] != null ? PartyModel.fromJson(json['party']) : null;
    if (json['details'] != null) {
      details = [];
      json['details'].forEach((v) {
        details?.add(SalesDetails.fromJson(v));
      });
    }
    if (json['sale_returns'] != null) {
      returns = [];
      json['sale_returns'].forEach((v) {
        returns?.add(SalesReturnsInSalesDetails.fromJson(v));
      });
    }
  }
  num? id;
  num? businessId;
  num? partyId;
  num? userId;
  num? taxId;
  num? discountAmount;
  num? dueAmount;
  bool? isPaid;
  SalesDetailsData? salesData;
  num? taxAmount;
  num? paidAmount;
  num? totalAmount;
  num? lossProfit;
  String? paymentType;
  String? invoiceNumber;
  String? saleDate;
  SalesDetailsMeta? meta;
  String? createdAt;
  String? updatedAt;
  User? salesBy;
  TaxModel? tax;
  PartyModel? party;
  List<SalesDetails>? details;
  List<SalesReturnsInSalesDetails>? returns;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['party_id'] = partyId;
    map['user_id'] = userId;
    map['tax_id'] = taxId;
    map['discountAmount'] = discountAmount;
    map['dueAmount'] = dueAmount;
    map['isPaid'] = isPaid;
    map['tax_amount'] = taxAmount;
    map['paidAmount'] = paidAmount;
    map['totalAmount'] = totalAmount;
    map['lossProfit'] = lossProfit;
    map['paymentType'] = paymentType;
    map['invoiceNumber'] = invoiceNumber;
    map['saleDate'] = saleDate;
    if (meta != null) {
      map['meta'] = meta?.toJson();
    }
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (salesBy != null) {
      map['user'] = salesBy?.toJson();
    }
    if (tax != null) {
      map['tax'] = tax?.toJson();
    }
    if (party != null) {
      map['party'] = party?.toJson();
    }
    if (details != null) {
      map['details'] = details?.map((v) => v.toJson()).toList();
    }
    if (returns != null) {
      map['sale_returns'] = returns?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class SalesDetails {
  SalesDetails({
    this.id,
    this.saleId,
    this.productId,
    this.price,
    this.purchasePrice,
    this.quantities,
    this.batchNo,
    this.expireDate,
    this.product,
  });

  SalesDetails.fromJson(dynamic json) {
    id = json['id'];
    saleId = json['sale_id'];
    productId = json['product_id'];
    price = json['price'];
    purchasePrice = json['purchase_price'];
    quantities = json['quantities'];
    batchNo = json['batch_no'];
    expireDate = json['expire_date'];
    product = json['product'] != null ? SalesDetailsProduct.fromJson(json['product']) : null;
  }
  num? id;
  num? saleId;
  num? productId;
  num? price;
  num? quantities;
  num? purchasePrice;
  String? batchNo;
  String? expireDate;
  SalesDetailsProduct? product;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['sale_id'] = saleId;
    map['product_id'] = productId;
    map['price'] = price;
    map['purchase_price'] = purchasePrice;
    map['quantities'] = quantities;
    map['batch_no'] = batchNo;
    map['expire_date'] = expireDate;
    if (product != null) {
      map['product'] = product?.toJson();
    }
    return map;
  }
}

class SalesDetailsProduct {
  SalesDetailsProduct({
    this.id,
    this.productName,
    this.productCurrentStock,
  });

  SalesDetailsProduct.fromJson(dynamic json) {
    id = json['id'];
    productName = json['productName'];
    productCurrentStock = json['stocks_sum_product_stock'];
  }
  num? id;
  String? productName;
  String? productCurrentStock;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['productName'] = productName;
    map['stocks_sum_product_stock'] = productCurrentStock;
    return map;
  }
}

class SalesDetailsMeta {
  SalesDetailsMeta({
    this.notes,
    this.customerPhone,
  });

  SalesDetailsMeta.fromJson(dynamic json) {
    notes = json['notes'];
    customerPhone = json['customer_phone'];
  }
  String? notes;
  String? customerPhone;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['notes'] = notes;
    map['customer_phone'] = customerPhone;
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

class SalesReturnsInSalesDetails {
  SalesReturnsInSalesDetails({
    this.id,
    this.businessId,
    this.saleId,
    this.invoiceNo,
    this.returnDate,
    this.createdAt,
    this.updatedAt,
    this.details,
  });

  SalesReturnsInSalesDetails.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    saleId = json['sale_id'];
    invoiceNo = json['invoice_no'];
    returnDate = json['return_date'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    if (json['details'] != null) {
      details = [];
      json['details'].forEach((v) {
        details?.add(ReturnDetails.fromJson(v));
      });
    }
  }
  num? id;
  num? businessId;
  num? saleId;
  String? invoiceNo;
  String? returnDate;
  String? createdAt;
  String? updatedAt;
  List<ReturnDetails>? details;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['sale_id'] = saleId;
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

class ReturnDetails {
  ReturnDetails({
    this.id,
    this.businessId,
    this.saleReturnId,
    this.saleDetailId,
    this.returnAmount,
    this.returnQty,
  });

  ReturnDetails.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    saleReturnId = json['sale_return_id'];
    saleDetailId = json['sale_detail_id'];
    returnAmount = json['return_amount'];
    returnQty = json['return_qty'];
  }
  num? id;
  num? businessId;
  num? saleReturnId;
  num? saleDetailId;
  num? returnAmount;
  num? returnQty;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['sale_return_id'] = saleReturnId;
    map['sale_detail_id'] = saleDetailId;
    map['return_amount'] = returnAmount;
    map['return_qty'] = returnQty;
    return map;
  }
}
