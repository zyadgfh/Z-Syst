class StockBasedProductModel {
  StockBasedProductModel({
    this.message,
    this.data,
  });

  StockBasedProductModel.fromJson(dynamic json) {
    message = json['message'];
    data = json['data'] != null ? DataModel.fromJson(json['data']) : null;
  }
  String? message;
  DataModel? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class DataModel {
  DataModel({
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

  DataModel.fromJson(dynamic json) {
    currentPage = json['current_page'];
    if (json['data'] != null) {
      data = [];
      json['data'].forEach((v) {
        data?.add(BatchWiseStockModel.fromJson(v));
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
  List<BatchWiseStockModel>? data;
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

class BatchWiseStockModel {
  BatchWiseStockModel({
    this.id,
    this.expireDate,
    this.productId,
    this.batchNo,
    this.productStock,
    this.product,
  });

  BatchWiseStockModel.fromJson(dynamic json) {
    id = json['id'];
    expireDate = json['expire_date'];
    productId = json['product_id'];
    batchNo = json['batch_no'];
    productStock = json['productStock'];
    product = json['product'] != null ? BatchWiseProductModel.fromJson(json['product']) : null;
  }
  num? id;
  dynamic expireDate;
  num? productId;
  dynamic batchNo;
  num? productStock;
  BatchWiseProductModel? product;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['expire_date'] = expireDate;
    map['product_id'] = productId;
    map['batch_no'] = batchNo;
    map['productStock'] = productStock;
    if (product != null) {
      map['product'] = product?.toJson();
    }
    return map;
  }
}

class BatchWiseProductModel {
  BatchWiseProductModel({
    this.id,
    this.productName,
    this.purchaseWithoutTax,
    this.purchaseWithTax,
    this.profitPercent,
    this.salesPrice,
    this.wholesalePrice,
    this.taxId,
    this.taxType,
    this.productCode,
    this.tax,
  });

  BatchWiseProductModel.fromJson(dynamic json) {
    id = json['id'];
    productName = json['productName'];
    purchaseWithoutTax = json['purchase_without_tax'];
    purchaseWithTax = json['purchase_with_tax'];
    profitPercent = json['profit_percent'];
    salesPrice = json['sales_price'];
    wholesalePrice = json['wholesale_price'];
    taxId = json['tax_id'];
    taxType = json['tax_type'];
    productCode = json['productCode'];
    tax = json['tax'] != null ? Tax.fromJson(json['tax']) : null;
  }
  num? id;
  String? productName;
  num? purchaseWithoutTax;
  num? purchaseWithTax;
  num? profitPercent;
  num? salesPrice;
  num? wholesalePrice;
  num? taxId;
  String? taxType;
  String? productCode;
  Tax? tax;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['productName'] = productName;
    map['purchase_without_tax'] = purchaseWithoutTax;
    map['purchase_with_tax'] = purchaseWithTax;
    map['profit_percent'] = profitPercent;
    map['sales_price'] = salesPrice;
    map['wholesale_price'] = wholesalePrice;
    map['tax_id'] = taxId;
    map['tax_type'] = taxType;
    map['productCode'] = productCode;
    if (tax != null) {
      map['tax'] = tax?.toJson();
    }
    return map;
  }
}

class Tax {
  Tax({
    this.id,
    this.rate,
    this.name,
  });

  Tax.fromJson(dynamic json) {
    id = json['id'];
    rate = json['rate'];
    name = json['name'];
  }
  num? id;
  num? rate;
  String? name;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['rate'] = rate;
    map['name'] = name;
    return map;
  }
}
