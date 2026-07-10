import 'dart:convert';

class ProductModel {
  ProductModel({
    this.id,
    this.productName,
    this.businessId,
    this.productCode,
    this.images,
    this.taxType,
    this.categoryId,
    this.taxId,
    this.typeId,
    this.manufacturerId,
    this.boxSizeId,
    this.unitId,
    this.stockSum,
    this.alertQty,
    this.productPurchasePriceWithoutTax,
    this.productPurchasePriceWithTax,
    this.profitPercent,
    this.productDealerPrice,
    this.productSalePrice,
    this.productWholeSalePrice,
    this.meta,
    this.createdAt,
    this.updatedAt,
    this.unit,
    this.medicineType,
    this.manufacterer,
    this.boxSize,
    this.category,
  });

  ProductModel.fromJson(dynamic json) {
    id = json['id'];
    productName = json['productName'];
    businessId = json['business_id'];
    productCode = json['productCode'];
    images = json['images'] != null ? json['images'].cast<String>() : [];
    categoryId = json['category_id'];
    typeId = json['type_id'];
    taxId = json['tax_id'];
    stockSum = num.tryParse(json['stocks_sum_product_stock']);
    alertQty = json['alert_qty'];
    taxType = json['tax_type'];
    manufacturerId = json['manufacturer_id'];
    boxSizeId = json['box_size_id'];
    unitId = json['unit_id'];
    productPurchasePriceWithoutTax = json['purchase_without_tax'];
    productPurchasePriceWithTax = json['purchase_with_tax'];
    profitPercent = json['profit_percent'];
    productDealerPrice = json['productDealerPrice'];
    productSalePrice = json['sales_price'];
    productWholeSalePrice = json['wholesale_price'];
    meta = json['meta'] != null ? Meta.fromJson(json['meta']) : null;
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    if (json['stocks'] != null) {
      stocks = [];
      json['stocks'].forEach((v) {
        stocks?.add(Stocks.fromJson(v));
      });
    }
    unit = json['unit'] != null ? ProductUnit.fromJson(json['unit']) : null;
    medicineType = json['medicine_type'] != null ? ProductMedicineType.fromJson(json['medicine_type']) : null;
    manufacterer = json['manufacterer'] != null ? ProductManufacterer.fromJson(json['manufacterer']) : null;
    boxSize = json['box_size'] != null ? ProductBoxSize.fromJson(json['box_size']) : null;
    category = json['category'] != null ? ProductCategory.fromJson(json['category']) : null;
    tax = json['tax'] != null ? Tax.fromJson(json['tax']) : null;
  }
  num? id;
  String? productName;
  num? businessId;
  String? productCode;
  List<String>? images;
  String? taxType;
  num? categoryId;
  num? taxId;
  num? typeId;
  num? manufacturerId;
  num? boxSizeId;
  num? unitId;
  num? stockSum;
  num? alertQty;
  num? productPurchasePriceWithoutTax;
  num? productPurchasePriceWithTax;
  num? profitPercent;
  num? productDealerPrice;
  num? productSalePrice;
  num? productWholeSalePrice;
  List<Stocks>? stocks;
  Meta? meta;
  String? createdAt;
  String? updatedAt;
  ProductUnit? unit;
  ProductMedicineType? medicineType;
  ProductManufacterer? manufacterer;
  ProductBoxSize? boxSize;
  ProductCategory? category;
  Tax? tax;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['productName'] = productName;
    map['business_id'] = businessId;
    map['productCode'] = productCode;
    map['images'] = images;
    map['stocks_sum_product_stock'] = stockSum;
    map['alert_qty'] = alertQty;
    map['category_id'] = categoryId;
    map['type_id'] = typeId;
    map['manufacturer_id'] = manufacturerId;
    map['box_size_id'] = boxSizeId;
    map['unit_id'] = unitId;
    map['tax_type'] = taxType;
    map['purchase_without_tax'] = productPurchasePriceWithoutTax;
    map['purchase_with_tax'] = productPurchasePriceWithTax;
    map['profit_percent'] = profitPercent;
    map['productDealerPrice'] = productDealerPrice;
    map['sales_price'] = productSalePrice;
    map['wholesale_price'] = productWholeSalePrice;
    if (meta != null) {
      map['meta'] = meta?.toJson();
    }
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (unit != null) {
      map['unit'] = unit?.toJson();
    }
    if (medicineType != null) {
      map['medicine_type'] = medicineType?.toJson();
    }
    if (stocks != null) {
      map['stocks'] = stocks?.map((v) => v.toJson()).toList();
    }
    if (manufacterer != null) {
      map['manufacterer'] = manufacterer?.toJson();
    }
    if (boxSize != null) {
      map['box_size'] = boxSize?.toJson();
    }
    if (category != null) {
      map['category'] = category?.toJson();
    }
    return map;
  }
}

class ProductCategory {
  ProductCategory({
    this.id,
    this.categoryName,
  });

  ProductCategory.fromJson(dynamic json) {
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
}class Tax {
  Tax({
    this.id,
    this.rate,
  });

  Tax.fromJson(dynamic json) {
    id = json['id'];
    rate = json['rate'];
  }
  num? id;
  num? rate;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['rate'] = rate;
    return map;
  }
}

class ProductBoxSize {
  ProductBoxSize({
    this.id,
    this.name,
  });

  ProductBoxSize.fromJson(dynamic json) {
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

class ProductManufacterer {
  ProductManufacterer({
    this.id,
    this.name,
  });

  ProductManufacterer.fromJson(dynamic json) {
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

class ProductMedicineType {
  ProductMedicineType({
    this.id,
    this.name,
  });

  ProductMedicineType.fromJson(dynamic json) {
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

class ProductUnit {
  ProductUnit({
    this.id,
    this.unitName,
  });

  ProductUnit.fromJson(dynamic json) {
    id = json['id'];
    unitName = json['unitName'];
  }
  num? id;
  String? unitName;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['unitName'] = unitName;
    return map;
  }
}

class Meta {
  Meta({
    this.strength,
    this.genericName,
    this.shelf,
    this.medicineDetails,
  });

  Meta.fromJson(dynamic json) {
    strength = json['strength'];
    genericName = json['generic_name'];
    shelf = json['shelf'];
    medicineDetails = json['medicine_details'];
  }
  String? strength;
  String? genericName;
  String? shelf;
  String? medicineDetails;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['strength'] = strength;
    map['generic_name'] = genericName;
    map['shelf'] = shelf;
    map['medicine_details'] = medicineDetails;
    return map;
  }
}

class Stocks {
  Stocks({
    this.id,
    this.expireDate,
    this.productStock,

    this.productId,
    this.batchNo,
  });

  Stocks.fromJson(dynamic json) {
    id = json['id'];
    productId = json['product_id'];
    productStock = json['productStock'];
    expireDate = json['expire_date'];
    batchNo = json['batch_no'];
  }
  num? id;
  num? productId;
  num? productStock;
  String? expireDate;
  String? batchNo;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['product_id'] = productId;
    map['productStock'] = productStock;
    map['expire_date'] = expireDate;
    map['batch_no'] = batchNo;
    return map;
  }
}
