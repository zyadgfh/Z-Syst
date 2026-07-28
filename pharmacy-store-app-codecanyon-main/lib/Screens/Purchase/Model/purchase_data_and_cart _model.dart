class PurchaseDataShareModel {
  num partyId;
  String purchaseDate;
  num discountAmount;
  num? taxId;
  num taxAmount;
  num totalAmount;
  num dueAmount;
  num paidAmount;
  bool isPaid;
  String paymentType;
  String? note;
  List<ProductsOnCartPurchaseDataModel> products;

  PurchaseDataShareModel({
    required this.partyId,
    required this.purchaseDate,
    required this.discountAmount,
    required this.taxAmount,
    this.taxId,
    required this.totalAmount,
    required this.dueAmount,
    required this.paidAmount,
    required this.isPaid,
    required this.paymentType,
    this.note,
    required this.products,
  });

  // Convert the PurchaseModel instance to JSON
  Map<String, dynamic> toJson() {
    return {
      "party_id": partyId,
      "purchaseDate": purchaseDate,
      "discountAmount": discountAmount,
      "tax_id": taxId,
      "tax_amount": taxAmount,
      "totalAmount": totalAmount,
      "dueAmount": dueAmount,
      "paidAmount": paidAmount,
      "isPaid": isPaid,
      "paymentType": paymentType,
      "note": note,
      "products": products.map((product) => product.toJson()).toList(),
    };
  }

  Map<String, dynamic> toJsonForUpdate() {
    return {
      "_method": 'put',
      "party_id": partyId,
      "purchaseDate": purchaseDate,
      "discountAmount": discountAmount,
      "tax_id": taxId,
      "tax_amount": taxAmount,
      "totalAmount": totalAmount,
      "dueAmount": dueAmount,
      "paidAmount": paidAmount,
      "isPaid": isPaid,
      "paymentType": paymentType,
      "note": note,
      "products": products.map((product) => product.toJson()).toList(),
    };
  }
}

class ProductsOnCartPurchaseDataModel {
  num productId;
  num purchaseWithoutTax;
  num purchaseWithTax;
  num profitPercent;
  num salesPrice;
  num wholesalePrice;
  String vatType;
  String batchNo;
  String productName;
  String? expireDate; // Nullable
  num quantities;

  ProductsOnCartPurchaseDataModel({
    required this.productId,
    required this.vatType,
    required this.purchaseWithoutTax,
    required this.purchaseWithTax,
    required this.profitPercent,
    required this.productName,
    required this.salesPrice,
    required this.wholesalePrice,
    required this.batchNo,
    this.expireDate,
    required this.quantities,
  });

  // Convert the Product instance to JSON
  Map<String, dynamic> toJson() {
    return {
      "product_id": productId,
      "purchase_without_tax": purchaseWithoutTax,
      "purchase_with_tax": purchaseWithTax,
      "profit_percent": profitPercent,
      "sales_price": salesPrice,
      "wholesale_price": wholesalePrice,
      "batch_no": batchNo,
      "expire_date": expireDate,
      "quantities": quantities,
    };
  }
}
