import 'dart:io';

class AddProductModel {
  AddProductModel({
    this.productCode,
    required this.productName,
    required this.categoryId,
    this.typeId,
    this.strength,
    this.genericName,
    this.manufacturerId,
    this.boxSizeId,
    this.unitId,
    required this.productStock,
    required this.alertStock,
    this.shelf,
    this.batchNo,
    this.expireDate,
    required this.purchaseWithoutTax,
    required this.purchaseWithTax,
    required this.profitPercent,
    required this.salePrice,
    this.wholeSalePrice,
    required this.taxType,
    required this.images,
    this.medicineDetails,
    this.taxId,
    this.removedImageList,
  });
  String? productCode;
  late String productName;
  late num categoryId;
  num? typeId;
  String? strength;
  String? genericName;
  num? manufacturerId;
  num? boxSizeId;
  num? unitId;
  String? productStock;
  String? alertStock;
  String? shelf;
  String? batchNo;
  String? expireDate;
  num purchaseWithoutTax;
  num purchaseWithTax;
  num profitPercent;
  late num salePrice;
  late String taxType;
  String? wholeSalePrice;
  List<File>? images;
  String? medicineDetails;
  num? taxId;
  List<String>? removedImageList;
}
