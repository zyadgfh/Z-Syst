import 'package:mobile_pos/Screens/tax%20rates/model/tax_model.dart';

class GroupTaxModel {
  late String name;
  late num taxRate;
  late String id;
  List<TaxModel>? subTaxes;

  GroupTaxModel({
    required this.name,
    required this.taxRate,
    required this.id,
    required this.subTaxes,
  });

  GroupTaxModel.fromJson(Map<String, dynamic> json) {
    name = json['name'];
    taxRate = json['rate'];
    id = json['id'];
    if (json['subTax'] != null) {
      subTaxes = <TaxModel>[];
      json['subTax'].forEach((v) {
        subTaxes!.add(TaxModel.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() => <String, dynamic>{
    'name': name,
    'rate': taxRate,
    'id': id,
    'subTax': subTaxes?.map((e) => e.toJson()).toList(),
  };
}
