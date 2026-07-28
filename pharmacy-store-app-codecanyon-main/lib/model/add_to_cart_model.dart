class AddToCartModel {
  AddToCartModel({
    required this.uuid,
    this.productId,
    this.productName,
    this.unitPrice,
    this.subTotal,
    this.quantity = 1,
    this.productDetails,
    this.itemCartIndex = -1,
    this.uniqueCheck,
    this.productBrandName,
    this.stock,
    this.productPurchasePrice,
    this.lossProfit,
  });

  num uuid;
  dynamic productId;
  String? productName;
  dynamic unitPrice;
  dynamic subTotal;
  dynamic productPurchasePrice;
  dynamic uniqueCheck;
  int quantity = 1;
  dynamic productDetails;
  dynamic productBrandName;
  int itemCartIndex;
  num? stock;
  num? lossProfit;

}
