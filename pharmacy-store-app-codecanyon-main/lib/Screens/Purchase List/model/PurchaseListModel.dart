import 'dart:convert';

class PurchaseListModel {
  String? message;
  PurchaseData? data;

  PurchaseListModel({
    this.message,
    this.data,
  });

  factory PurchaseListModel.fromRawJson(String str) => PurchaseListModel.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory PurchaseListModel.fromJson(Map<String, dynamic> json) => PurchaseListModel(
        message: json["message"],
        data: json["data"] == null ? null : PurchaseData.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message,
        "data": data?.toJson(),
      };
}

class PurchaseData {
  int? currentPage;
  List<PurchaseDataView>? data;
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

  PurchaseData({
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

  factory PurchaseData.fromRawJson(String str) => PurchaseData.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory PurchaseData.fromJson(Map<String, dynamic> json) => PurchaseData(
        currentPage: json["current_page"],
        data: json["data"] == null ? [] : List<PurchaseDataView>.from(json["data"]!.map((x) => PurchaseDataView.fromJson(x))),
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

class PurchaseDataView {
  num? id;
  num? partyId;
  String? invoiceNumber;
  DateTime? purchaseDate;
  num? totalAmount;
  num? purchaseReturnCount;
  num? dueAmount;
  num? paidAmount;
  String? paymentType;
  String? note;
  Party? party;

  PurchaseDataView({
    this.id,
    this.partyId,
    this.invoiceNumber,
    this.purchaseDate,
    this.totalAmount,
    this.dueAmount,
    this.purchaseReturnCount,
    this.paidAmount,
    this.paymentType,
    this.note,
    this.party,
  });

  factory PurchaseDataView.fromRawJson(String str) => PurchaseDataView.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory PurchaseDataView.fromJson(Map<String, dynamic> json) => PurchaseDataView(
        id: json["id"],
        partyId: json["party_id"],
        invoiceNumber: json["invoiceNumber"],
        purchaseReturnCount: json["purchase_returns_count"],
        purchaseDate: json["purchaseDate"] == null ? null : DateTime.parse(json["purchaseDate"]),
        totalAmount: json["totalAmount"],
        dueAmount: json["dueAmount"],
        paidAmount: json["paidAmount"],
        paymentType: json["paymentType"],
        note: json["note"],
        party: json["party"] == null ? null : Party.fromJson(json["party"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "party_id": partyId,
        "invoiceNumber": invoiceNumber,
        "purchaseDate": purchaseDate?.toIso8601String(),
        "totalAmount": totalAmount,
        "dueAmount": dueAmount,
        "paidAmount": paidAmount,
        "paymentType": paymentType,
        "note": note,
        "party": party?.toJson(),
      };
}

class Party {
  num? id;
  String? name;
  String? phone;

  Party({
    this.id,
    this.name,
    this.phone,
  });

  factory Party.fromRawJson(String str) => Party.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory Party.fromJson(Map<String, dynamic> json) => Party(
        id: json["id"],
        name: json["name"],
        phone: json["phone"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "phone": phone,
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

  factory Link.fromRawJson(String str) => Link.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

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
