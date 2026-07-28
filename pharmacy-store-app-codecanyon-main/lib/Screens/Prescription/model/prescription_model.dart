class PrescriptionModel {
  PrescriptionModel({
    this.message,
    this.data,
  });

  PrescriptionModel.fromJson(dynamic json) {
    message = json['message'];
    if (json['data'] != null) {
      data = [];
      json['data'].forEach((v) {
        data?.add(PrescriptionData.fromJson(v));
      });
    }
  }
  String? message;
  List<PrescriptionData>? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class PrescriptionPaginatedModel {
  PrescriptionPaginatedModel({
    this.message,
    this.data,
  });

  PrescriptionPaginatedModel.fromJson(dynamic json) {
    message = json['message'];
    data = json['data'] != null ? PaginationData.fromJson(json['data']) : null;
  }
  String? message;
  PaginationData? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class PaginationData {
  PaginationData({
    this.currentPage,
    this.prescriptions,
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

  PaginationData.fromJson(dynamic json) {
    currentPage = json['current_page'];
    if (json['data'] != null) {
      prescriptions = [];
      json['data'].forEach((v) {
        prescriptions?.add(PrescriptionData.fromJson(v));
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
  List<PrescriptionData>? prescriptions;
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
    if (prescriptions != null) {
      map['data'] = prescriptions?.map((v) => v.toJson()).toList();
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

class PrescriptionData {
  PrescriptionData({
    this.id,
    this.businessId,
    this.saleId,
    this.partyId,
    this.image,
    this.notes,
    this.status,
    this.createdAt,
    this.updatedAt,
    this.party,
    this.sale,
  });

  PrescriptionData.fromJson(dynamic json) {
    id = json['id'];
    businessId = json['business_id'];
    saleId = json['sale_id'];
    partyId = json['party_id'];
    image = json['image'];
    notes = json['notes'];
    status = json['status'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    party = json['party'] != null ? PrescriptionParty.fromJson(json['party']) : null;
    sale = json['sale'] != null ? PrescriptionSale.fromJson(json['sale']) : null;
  }
  num? id;
  num? businessId;
  num? saleId;
  num? partyId;
  String? image;
  String? notes;
  String? status;
  String? createdAt;
  String? updatedAt;
  PrescriptionParty? party;
  PrescriptionSale? sale;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['business_id'] = businessId;
    map['sale_id'] = saleId;
    map['party_id'] = partyId;
    map['image'] = image;
    map['notes'] = notes;
    map['status'] = status;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    if (party != null) {
      map['party'] = party?.toJson();
    }
    if (sale != null) {
      map['sale'] = sale?.toJson();
    }
    return map;
  }
}

class PrescriptionParty {
  PrescriptionParty({
    this.id,
    this.name,
    this.phone,
  });

  PrescriptionParty.fromJson(dynamic json) {
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

class PrescriptionSale {
  PrescriptionSale({
    this.id,
    this.invoiceNumber,
  });

  PrescriptionSale.fromJson(dynamic json) {
    id = json['id'];
    invoiceNumber = json['invoiceNumber'];
  }
  num? id;
  String? invoiceNumber;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['invoiceNumber'] = invoiceNumber;
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
