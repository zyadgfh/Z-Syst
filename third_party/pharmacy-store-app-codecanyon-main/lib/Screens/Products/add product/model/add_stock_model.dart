class AddStockModel {
  AddStockModel({
    required this.productStock,
    this.batchNo,
    this.expireDate,
    required this.purchaseWithoutTax,
    required this.purchaseWithTax,
    required this.profitPercent,
    required this.salePrice,
    this.wholeSalePrice,
    required this.taxType,
    this.taxId,
  });

  String productStock;
  String? batchNo;
  String? expireDate;
  num purchaseWithoutTax;
  num purchaseWithTax;
  num profitPercent;
  late num salePrice;
  late String taxType;
  String? wholeSalePrice;
  String? taxId;
}
