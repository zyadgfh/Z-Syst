import 'dart:convert';

class Data {
  int? currentPage;
  List<Datum>? data;
  String? firstPageUrl;
  int? from;
  int? lastPage;
  String? lastPageUrl;
  List<Link>? links;
  dynamic nextPageUrl;
  String? path;
  int? perPage;
  dynamic prevPageUrl;
  int? to;
  int? total;

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

  Data copyWith({
    int? currentPage,
    List<Datum>? data,
    String? firstPageUrl,
    int? from,
    int? lastPage,
    String? lastPageUrl,
    List<Link>? links,
    dynamic nextPageUrl,
    String? path,
    int? perPage,
    dynamic prevPageUrl,
    int? to,
    int? total,
  }) =>
      Data(
        currentPage: currentPage ?? this.currentPage,
        data: data ?? this.data,
        firstPageUrl: firstPageUrl ?? this.firstPageUrl,
        from: from ?? this.from,
        lastPage: lastPage ?? this.lastPage,
        lastPageUrl: lastPageUrl ?? this.lastPageUrl,
        links: links ?? this.links,
        nextPageUrl: nextPageUrl ?? this.nextPageUrl,
        path: path ?? this.path,
        perPage: perPage ?? this.perPage,
        prevPageUrl: prevPageUrl ?? this.prevPageUrl,
        to: to ?? this.to,
        total: total ?? this.total,
      );

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        currentPage: json["current_page"],
        data: json["data"] == null ? [] : List<Datum>.from(json["data"]!.map((x) => Datum.fromJson(x))),
        firstPageUrl: json["first_page_url"],
        from: json["from"],
        lastPage: json["last_page"],
        lastPageUrl: json["last_page_url"],
        links: json["links"] == null ? [] : List<Link>.from(json["links"]!.map((x) => Link.fromJson(x))),
        nextPageUrl: json["next_page_url"],
        path: json["path"],
        perPage: json["per_page"],
        prevPageUrl: json["prev_page_url"],
        to: json["to"],
        total: json["total"],
      );

  Map<String, dynamic> toJson() => {
        "current_page": currentPage,
        "data": data == null ? [] : List<dynamic>.from(data!.map((x) => x.toJson())),
        "first_page_url": firstPageUrl,
        "from": from,
        "last_page": lastPage,
        "last_page_url": lastPageUrl,
        "links": links == null ? [] : List<dynamic>.from(links!.map((x) => x.toJson())),
        "next_page_url": nextPageUrl,
        "path": path,
        "per_page": perPage,
        "prev_page_url": prevPageUrl,
        "to": to,
        "total": total,
      };
}

// Paginated List
ProductListModel productListModelFromJson(String str) => ProductListModel.fromJson(json.decode(str));

String productListModelToJson(ProductListModel data) => json.encode(data.toJson());

class ProductListModel {
  String? message;
  Data? data;

  ProductListModel({
    this.message,
    this.data,
  });

  ProductListModel copyWith({
    String? message,
    Data? data,
  }) =>
      ProductListModel(
        message: message ?? this.message,
        data: data ?? this.data,
      );

  factory ProductListModel.fromJson(Map<String, dynamic> json) => ProductListModel(
        message: json["message"],
        data: json["data"] == null ? null : Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message,
        "data": data?.toJson(),
      };
}

class Link {
  String? url;
  String? label;
  bool? active;

  Link({
    this.url,
    this.label,
    this.active,
  });

  Link copyWith({
    String? url,
    String? label,
    bool? active,
  }) =>
      Link(
        url: url ?? this.url,
        label: label ?? this.label,
        active: active ?? this.active,
      );

  factory Link.fromJson(Map<String, dynamic> json) => Link(
        url: json["url"],
        label: json["label"],
        active: json["active"],
      );

  Map<String, dynamic> toJson() => {
        "url": url,
        "label": label,
        "active": active,
      };
}

class Datum {
  int? id;
  String? productName;
  String? productCode;
  num? purchaseWithTax;
  num? salesPrice;
  String? stocksSumProductStock;
  ExpiringItem? expiringItem;

  Datum({
    this.id,
    this.productName,
    this.productCode,
    this.purchaseWithTax,
    this.salesPrice,
    this.stocksSumProductStock,
    this.expiringItem,
  });

  Datum copyWith({
    int? id,
    String? productName,
    String? productCode,
    num? purchaseWithTax,
    num? salesPrice,
    String? stocksSumProductStock,
    ExpiringItem? expiringItem,
  }) =>
      Datum(
        id: id ?? this.id,
        productName: productName ?? this.productName,
        productCode: productCode ?? this.productCode,
        purchaseWithTax: purchaseWithTax ?? this.purchaseWithTax,
        salesPrice: salesPrice ?? this.salesPrice,
        stocksSumProductStock: stocksSumProductStock ?? this.stocksSumProductStock,
        expiringItem: expiringItem ?? this.expiringItem,
      );

  factory Datum.fromJson(Map<String, dynamic> json) => Datum(
        id: json["id"],
        productName: json["productName"],
        productCode: json["productCode"],
        purchaseWithTax: json["purchase_with_tax"],
        salesPrice: json["sales_price"]?.toDouble(),
        stocksSumProductStock: json["stocks_sum_product_stock"],
        expiringItem: json["expiring_item"] == null ? null : ExpiringItem.fromJson(json["expiring_item"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "productName": productName,
        "productCode": productCode,
        "purchase_with_tax": purchaseWithTax,
        "sales_price": salesPrice,
        "stocks_sum_product_stock": stocksSumProductStock,
        "expiring_item": expiringItem?.toJson(),
      };
}

class ExpiringItem {
  DateTime? expireDate;
  int? productId;

  ExpiringItem({
    this.expireDate,
    this.productId,
  });

  ExpiringItem copyWith({
    DateTime? expireDate,
    int? productId,
  }) =>
      ExpiringItem(
        expireDate: expireDate ?? this.expireDate,
        productId: productId ?? this.productId,
      );

  factory ExpiringItem.fromJson(Map<String, dynamic> json) => ExpiringItem(
        expireDate: json["expire_date"] == null ? null : DateTime.parse(json["expire_date"]),
        productId: json["product_id"],
      );

  Map<String, dynamic> toJson() => {
        "expire_date": "${expireDate!.year.toString().padLeft(4, '0')}-${expireDate!.month.toString().padLeft(2, '0')}-${expireDate!.day.toString().padLeft(2, '0')}",
        "product_id": productId,
      };
}
