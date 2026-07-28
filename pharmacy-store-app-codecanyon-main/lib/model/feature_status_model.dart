class FeatureStatusModel {
  FeatureStatusModel({
    this.key,
    this.label,
    this.status,
    this.details,
  });

  FeatureStatusModel.fromJson(Map<String, dynamic> json) {
    key = json['key']?.toString();
    label = json['label']?.toString();
    status = json['status']?.toString();
    details = json['details']?.toString();
  }

  String? key;
  String? label;
  String? status;
  String? details;

  Map<String, dynamic> toJson() {
    return {
      'key': key,
      'label': label,
      'status': status,
      'details': details,
    };
  }
}
