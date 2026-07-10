class CollectedDueListModel {
  CollectedDueListModel({
    this.message,
    this.data,
  });

  CollectedDueListModel.fromJson(dynamic json) {
    message = json['message'];
    data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }
  String? message;
  Data? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
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
        data?.add(CollectedDueData.fromJson(v));
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
  List<CollectedDueData>? data;
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

class CollectedDueData {
  CollectedDueData({
    this.id,
    this.businessId,
    this.partyId,
    this.userId,
    this.saleId,
    this.purchaseId,
    this.invoiceNumber,
    this.totalDue,
    this.dueAmountAfterPay,
    this.payDueAmount,
    this.paymentType,
    this.paymentDate,
    this.createdAt,
    this.updatedAt,
    this.collectedBy,
    this.party,
  });

  CollectedDueData.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    partyId = json['party_id'];
    userId = json['user_id'];
    saleId = json['sale_id'];
    purchaseId = json['purchase_id'];
    invoiceNumber = json['invoiceNumber'];
    totalDue = json['totalDue'];
    dueAmountAfterPay = json['dueAmountAfterPay'];
    payDueAmount = json['payDueAmount'];
    paymentType = json['paymentType'];
    paymentDate = json['paymentDate'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    collectedBy = json['user'] != null ? User.fromJson(json['user']) : null;
    party = json['party'] != null ? Party.fromJson(json['party']) : null;
  }
  num? id;
  num? businessId;
  num? partyId;
  num? userId;
  num? saleId;
  dynamic purchaseId;
  String? invoiceNumber;
  num? totalDue;
  num? dueAmountAfterPay;
  num? payDueAmount;
  String? paymentType;
  String? paymentDate;
  String? createdAt;
  String? updatedAt;
  User? collectedBy;
  Party? party;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['party_id'] = partyId;
    map['user_id'] = userId;
    map['sale_id'] = saleId;
    map['purchase_id'] = purchaseId;
    map['invoiceNumber'] = invoiceNumber;
    map['totalDue'] = totalDue;
    map['dueAmountAfterPay'] = dueAmountAfterPay;
    map['payDueAmount'] = payDueAmount;
    map['paymentType'] = paymentType;
    map['paymentDate'] = paymentDate;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (collectedBy != null) {
      map['user'] = collectedBy?.toJson();
    }
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
    this.email,
    this.phone,
    this.type,
  });

  Party.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    email = json['email'];
    phone = json['phone'];
    type = json['type'];
  }
  num? id;
  String? name;
  String? email;
  String? phone;
  String? type;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['email'] = email;
    map['phone'] = phone;
    map['type'] = type;
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
