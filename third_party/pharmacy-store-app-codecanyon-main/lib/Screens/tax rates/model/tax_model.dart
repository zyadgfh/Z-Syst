class TaxModel {
  TaxModel({
    this.id,
    this.name,
    this.businessId,
    this.rate,
    this.subTax,
    this.status,
    this.createdAt,
    this.updatedAt,
  });

  TaxModel.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    businessId = json['business_id'];
    rate = json['rate'];
    if (json['sub_tax'] != null) {
      subTax = [];
      json['sub_tax'].forEach((v) {
        subTax?.add(SubTax.fromJson(v));
      });
    }
    status = json['status'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }
  num? id;
  String? name;
  num? businessId;
  num? rate;
  List<SubTax>? subTax;
  bool? status;
  String? createdAt;
  String? updatedAt;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['business_id'] = businessId;
    map['rate'] = rate;
    if (subTax != null) {
      map['sub_tax'] = subTax?.map((v) => v.toJson()).toList();
    }
    map['status'] = status;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    return map;
  }
}

class SubTax {
  SubTax({
    this.id,
    this.name,
    this.rate,
  });

  SubTax.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    rate = json['rate'];
  }
  num? id;
  String? name;
  num? rate;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['rate'] = rate;
    return map;
  }
}
