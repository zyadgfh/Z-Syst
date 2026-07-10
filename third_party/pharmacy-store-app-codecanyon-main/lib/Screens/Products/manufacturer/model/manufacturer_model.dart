class ManufacturerModel {
  ManufacturerModel({
    this.id,
    this.name,
    this.businessId,
    this.status,
    this.createdAt,
    this.updatedAt,});

  ManufacturerModel.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    businessId = json['business_id'];
    status = json['status'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }
  num? id;
  String? name;
  num? businessId;
  num? status;
  String? createdAt;
  String? updatedAt;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['business_id'] = businessId;
    map['status'] = status;
    map['created_at'] = createdAt;
    map['updated_at'] = updatedAt;
    return map;
  }

}