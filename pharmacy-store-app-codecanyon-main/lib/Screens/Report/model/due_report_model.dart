class DueReportModel {
  DueReportModel({
    this.message,
    this.totalPaid,
    this.totalDue,
    this.data,
  });

  DueReportModel.fromJson(dynamic json) {
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
        data?.add(DueReportData.fromJson(v));
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
  List<DueReportData>? data;
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

class DueReportData {
  DueReportData({
    this.id,
    this.partyId,
    this.invoiceNumber,
    this.totalDue,
    this.dueAmountAfterPay,
    this.payDueAmount,
    this.paymentType,
    this.paymentDate,
    this.party,
  });

  DueReportData.fromJson(dynamic json) {
    id = json['id'];
    partyId = json['party_id'];
    invoiceNumber = json['invoiceNumber'];
    totalDue = json['totalDue'];
    dueAmountAfterPay = json['dueAmountAfterPay'];
    payDueAmount = json['payDueAmount'];
    paymentType = json['paymentType'];
    paymentDate = json['paymentDate'];
    party = json['party'] != null ? Party.fromJson(json['party']) : null;
  }
  num? id;
  num? partyId;
  String? invoiceNumber;
  num? totalDue;
  num? dueAmountAfterPay;
  num? payDueAmount;
  String? paymentType;
  String? paymentDate;
  Party? party;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['party_id'] = partyId;
    map['invoiceNumber'] = invoiceNumber;
    map['totalDue'] = totalDue;
    map['dueAmountAfterPay'] = dueAmountAfterPay;
    map['payDueAmount'] = payDueAmount;
    map['paymentType'] = paymentType;
    map['paymentDate'] = paymentDate;
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
