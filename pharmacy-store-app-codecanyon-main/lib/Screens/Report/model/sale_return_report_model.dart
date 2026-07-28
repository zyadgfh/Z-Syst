class SaleReturnReportModel {
  SaleReturnReportModel({
    this.message,
    this.totalReturn,
    this.totalQty,
    this.data,
  });

  SaleReturnReportModel.fromJson(dynamic json) {
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
        data?.add(SaleReturnData.fromJson(v));
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
  List<SaleReturnData>? data;
  String? firstPageUrl;
  num? from;
  num? lastPage;
  String? lastPageUrl;
  List<Links>? links;
  String? nextPageUrl;
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

class SaleReturnData {
  SaleReturnData({
    this.id,
    this.saleId,
    this.returnDate,
    this.invoiceNo,
    this.details,
    this.sale,
  });

  SaleReturnData.fromJson(dynamic json) {
    id = json['id'];
    saleId = json['sale_id'];
    returnDate = json['return_date'];
    invoiceNo = json['invoice_no'];
    if (json['details'] != null) {
      details = [];
      json['details'].forEach((v) {
        details?.add(Details.fromJson(v));
      });
    }
    sale = json['sale'] != null ? Sale.fromJson(json['sale']) : null;
  }
  num? id;
  num? saleId;
  String? returnDate;
  String? invoiceNo;
  List<Details>? details;
  Sale? sale;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['sale_id'] = saleId;
    map['return_date'] = returnDate;
    map['invoice_no'] = invoiceNo;
    if (details != null) {
      map['details'] = details?.map((v) => v.toJson()).toList();
    }
    if (sale != null) {
      map['sale'] = sale?.toJson();
    }
    return map;
  }
}

class Sale {
  Sale({
    this.id,
    this.partyId,
    this.invoiceNumber,
    this.totalAmount,
    this.party,
  });

  Sale.fromJson(dynamic json) {
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
    this.saleReturnId,
    this.returnQty,
    this.returnAmount,
  });

  Details.fromJson(dynamic json) {
    id = json['id'];
    saleReturnId = json['sale_return_id'];
    returnQty = json['return_qty'];
    returnAmount = json['return_amount'];
  }
  num? id;
  num? saleReturnId;
  num? returnQty;
  num? returnAmount;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['sale_return_id'] = saleReturnId;
    map['return_qty'] = returnQty;
    map['return_amount'] = returnAmount;
    return map;
  }
}
