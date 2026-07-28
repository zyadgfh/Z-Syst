class LossProfitModel {
  LossProfitModel({
    this.message,
    this.totalProfit,
    this.totalLoss,
    this.data,
  });

  LossProfitModel.fromJson(dynamic json) {
    message = json['message'];
    totalProfit = json['total_profit'];
    totalLoss = json['total_loss'];
    data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }
  String? message;
  num? totalProfit;
  num? totalLoss;
  Data? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    map['total_profit'] = totalProfit;
    map['total_loss'] = totalLoss;
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
        data?.add(LossProfitData.fromJson(v));
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
  List<LossProfitData>? data;
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

class LossProfitData {
  LossProfitData({
    this.id,
    this.partyId,
    this.invoiceNumber,
    this.saleDate,
    this.dueAmount,
    this.lossProfit,
    this.totalAmount,
    this.party,
  });

  LossProfitData.fromJson(dynamic json) {
    id = json['id'];
    partyId = json['party_id'];
    invoiceNumber = json['invoiceNumber'];
    saleDate = json['saleDate'];
    dueAmount = json['dueAmount'];
    lossProfit = json['lossProfit'];
    totalAmount = json['totalAmount'];
    party = json['party'] != null ? Party.fromJson(json['party']) : null;
  }
  num? id;
  num? partyId;
  String? invoiceNumber;
  String? saleDate;
  num? dueAmount;
  num? lossProfit;
  num? totalAmount;
  Party? party;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['party_id'] = partyId;
    map['invoiceNumber'] = invoiceNumber;
    map['saleDate'] = saleDate;
    map['dueAmount'] = dueAmount;
    map['lossProfit'] = lossProfit;
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
