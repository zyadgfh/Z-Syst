class StockListModel {
  StockListModel({
    this.message,
    this.totalProducts,
    this.lowStockCount,
    this.totalStockValue,
    this.stocks,});

  StockListModel.fromJson(dynamic json) {
    message = json['message'];
    totalProducts = json['total_products'];
    lowStockCount = json['low_stock_count'];
    totalStockValue = json['total_stock_value'];
    stocks = json['stocks'] != null ? StockList.fromJson(json['stocks']) : null;
  }
  String? message;
  num? totalProducts;
  num? lowStockCount;
  num? totalStockValue;
  StockList? stocks;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    map['total_products'] = totalProducts;
    map['low_stock_count'] = lowStockCount;
    map['total_stock_value'] = totalStockValue;
    if (stocks != null) {
      map['stocks'] = stocks?.toJson();
    }
    return map;
  }

}

class StockList {
  StockList({
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
    this.total,});

  StockList.fromJson(dynamic json) {
    currentPage = json['current_page'];
    if (json['data'] != null) {
      data = [];
      json['data'].forEach((v) {
        data?.add(SingleStockData.fromJson(v));
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
  List<SingleStockData>? data;
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
    this.active,});

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

class SingleStockData {
  SingleStockData({
    this.id,
    this.productName,
    this.purchaseWithTax,
    this.salesPrice,
    this.stocksSumProductStock,
    this.stocks,});

  SingleStockData.fromJson(dynamic json) {
    id = json['id'];
    productName = json['productName'];
    purchaseWithTax = json['purchase_with_tax'];
    salesPrice = json['sales_price'];
    stocksSumProductStock = json['stocks_sum_product_stock'];
    if (json['stocks'] != null) {
      stocks = [];
      json['stocks'].forEach((v) {
        stocks?.add(Stock.fromJson(v));
      });
    }
  }
  num? id;
  String? productName;
  num? purchaseWithTax;
  num? salesPrice;
  String? stocksSumProductStock;
  List<Stock>? stocks;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['productName'] = productName;
    map['purchase_with_tax'] = purchaseWithTax;
    map['sales_price'] = salesPrice;
    map['stocks_sum_product_stock'] = stocksSumProductStock;
    if (stocks != null) {
      map['stocks'] = stocks?.map((v) => v.toJson()).toList();
    }
    return map;
  }

}

class Stock {
  Stock({
    this.id,
    this.batchNo,
    this.expireDate,
    this.productId,
    this.productStock,});

  Stock.fromJson(dynamic json) {
    id = json['id'];
    batchNo = json['batch_no'];
    expireDate = json['expire_date'];
    productId = json['product_id'];
    productStock = json['productStock'];
  }
  num? id;
  String? batchNo;
  String? expireDate;
  num? productId;
  num? productStock;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['batch_no'] = batchNo;
    map['expire_date'] = expireDate;
    map['product_id'] = productId;
    map['productStock'] = productStock;
    return map;
  }

}