class BusinessCategoryModel {
  final int id;
  final String name;
  final String description;

  BusinessCategoryModel({
    required this.id,
    required this.name,
    required this.description,
  });

  factory BusinessCategoryModel.fromJson(Map<String, dynamic> json) {
    return BusinessCategoryModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      description: json['description'] ?? '',
    );
  }
}
