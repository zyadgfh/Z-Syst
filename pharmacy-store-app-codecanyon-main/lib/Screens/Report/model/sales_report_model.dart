class SaleReportModel {
  SaleReportModel({
    this.message,
    this.totalPaid,
    this.totalDue,
    this.data,
  });

  SaleReportModel.fromJson(dynamic json) {
    message = json['message'];
    totalPaid = json['total_paid'];
    totalDue = json['total_due'];
    data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }
  String? message;
  num? totalPaid;
  num? totalDue;
  Data? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    map['total_paid'] = totalPaid;
    map['total_due'] = totalDue;
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
        data?.add(SaleData.fromJson(v));
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
  List<SaleData>? data;
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

class SaleData {
  SaleData({
    this.id,
    this.partyId,
    this.invoiceNumber,
    this.saleDate,
    this.saleReturnCounter,
    this.totalAmount,
    this.dueAmount,
    this.paidAmount,
    this.paymentType,
    this.party,
  });

  SaleData.fromJson(dynamic json) {
    id = json['id'];
    partyId = json['party_id'];
    invoiceNumber = json['invoiceNumber'];
    saleDate = json['saleDate'];
    saleReturnCounter = json['sale_returns_count'];
    totalAmount = json['totalAmount'];
    dueAmount = json['dueAmount'];
    paidAmount = json['paidAmount'];
    paymentType = json['paymentType'];
    party = json['party'] != null ? Party.fromJson(json['party']) : null;
  }
  num? id;
  num? partyId;
  String? invoiceNumber;
  String? saleDate;
  num? saleReturnCounter;
  num? totalAmount;
  num? dueAmount;
  num? paidAmount;
  String? paymentType;
  Party? party;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['party_id'] = partyId;
    map['invoiceNumber'] = invoiceNumber;
    map['saleDate'] = saleDate;
    map['totalAmount'] = totalAmount;
    map['dueAmount'] = dueAmount;
    map['paidAmount'] = paidAmount;
    map['paymentType'] = paymentType;
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
