class PurchaseReturnReportModel {
  PurchaseReturnReportModel({
    this.message,
    this.totalReturn,
    this.totalQty,
    this.data,
  });

  PurchaseReturnReportModel.fromJson(dynamic json) {
    message = json['message'];
    totalReturn = json['total_return'];
    totalQty = json['total_qty'];
    data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }
  String? message;
  num? totalReturn;
  num? totalQty;
  Data? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    map['total_return'] = totalReturn;
    map['total_qty'] = totalQty;
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
        data?.add(PurchaseReturnData.fromJson(v));
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
  List<PurchaseReturnData>? data;
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

class PurchaseReturnData {
  PurchaseReturnData({
    this.id,
    this.purchaseId,
    this.returnDate,
    this.invoiceNo,
    this.details,
    this.purchase,
  });

  PurchaseReturnData.fromJson(dynamic json) {
    id = json['id'];
    purchaseId = json['purchase_id'];
    returnDate = json['return_date'];
    invoiceNo = json['invoice_no'];
    if (json['details'] != null) {
      details = [];
      json['details'].forEach((v) {
        details?.add(Details.fromJson(v));
      });
    }
    purchase = json['purchase'] != null ? Purchase.fromJson(json['purchase']) : null;
  }
  num? id;
  num? purchaseId;
  String? returnDate;
  String? invoiceNo;
  List<Details>? details;
  Purchase? purchase;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['purchase_id'] = purchaseId;
    map['return_date'] = returnDate;
    map['invoice_no'] = invoiceNo;
    if (details != null) {
      map['details'] = details?.map((v) => v.toJson()).toList();
    }
    if (purchase != null) {
      map['purchase'] = purchase?.toJson();
    }
    return map;
  }
}

class Purchase {
  Purchase({
    this.id,
    this.partyId,
    this.invoiceNumber,
    this.totalAmount,
    this.party,
  });

  Purchase.fromJson(dynamic json) {
    id = json['id'];
    partyId = json['party_id'];
    invoiceNumber = json['invoiceNumber'];
    totalAmount = json['totalAmount'];
    party = json['party'] != null ? Party.fromJson(json['party']) : null;
  }
  num? id;
  num? partyId;
  String? invoiceNumber;
  num? totalAmount;
  Party? party;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['party_id'] = partyId;
    map['invoiceNumber'] = invoiceNumber;
    map['totalAmount'] = totalAmount;
    if (party != null) {
      map['party'] = party?.toJson();
    }
    return map;
  }
}

class Party {
  Party({
    this.id,
    this.name,
  });

  Party.fromJson(dynamic json) {
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

class Details {
  Details({
    this.id,
    this.purchaseReturnId,
    this.returnQty,
    this.returnAmount,
  });

  Details.fromJson(dynamic json) {
    id = json['id'];
    purchaseReturnId = json['purchase_return_id'];
    returnQty = json['return_qty'];
    returnAmount = json['return_amount'];
  }
  num? id;
  num? purchaseReturnId;
  num? returnQty;
  num? returnAmount;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['purchase_return_id'] = purchaseReturnId;
    map['return_qty'] = returnQty;
    map['return_amount'] = returnAmount;
    return map;
  }
}
