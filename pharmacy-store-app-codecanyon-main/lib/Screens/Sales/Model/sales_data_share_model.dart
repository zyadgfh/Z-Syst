class SalesDataShareModel {
  num? partyId;
  String saleDate;
  num discountAmount;
  num totalAmount;
  num dueAmount;
  num paidAmount;
  num taxAmount;
  num? taxId;
  bool isPaid;
  String paymentType;
  String customerPhone;
  String notes;
  List<ProductsOnCartSalesDataModel> products;

  SalesDataShareModel({
    this.partyId,
    required this.saleDate,
    required this.discountAmount,
    required this.totalAmount,
    required this.dueAmount,
    required this.paidAmount,
    required this.taxAmount,
    required this.taxId,
    required this.isPaid,
    required this.paymentType,
    required this.customerPhone,
    required this.notes,
    required this.products,
  });

  // To JSON method
  Map<String, dynamic> toJson() {
    return {
      "party_id": partyId,
      "saleDate": saleDate,
      "discountAmount": discountAmount,
      "totalAmount": totalAmount,
      "dueAmount": dueAmount,
      "paidAmount": paidAmount,
      "tax_amount": taxAmount,
      "tax_id": taxId,
      "isPaid": isPaid,
      "paymentType": paymentType,
      "customer_phone": customerPhone,
      "notes": notes,
      "products": products.map((product) => product.toJson()).toList(),
    };
  }
  Map<String, dynamic> toJsonForUpdate() {
    return {
      "_method": 'put',
      "party_id": partyId,
      "saleDate": saleDate,
      "discountAmount": discountAmount,
      "totalAmount": totalAmount,
      "dueAmount": dueAmount,
      "paidAmount": paidAmount,
      "tax_amount": taxAmount,
      "tax_id": taxId,
      "isPaid": isPaid,
      "paymentType": paymentType,
      "customer_phone": customerPhone,
      "notes": notes,
      "products": products.map((product) => product.toJson()).toList(),
    };
  }
}

class ProductsOnCartSalesDataModel {
  num price;
  num productId;
  num lossProfit;
  String? productName;
  String? expireDate;
  String? batchNo;
  num quantities;
  num stock;
  num purchasePrice;

  ProductsOnCartSalesDataModel({
    required this.price,
    required this.purchasePrice,
    required this.productId,
    required this.stock,
    this.productName,
    this.expireDate,
    required this.lossProfit,
    required this.batchNo,
    required this.quantities,
  });

  // To JSON method
  Map<String, dynamic> toJson() {
    return {
      "price": price,
      "product_id": productId,
      "lossProfit": lossProfit,
      "batch_no": batchNo,
      "quantities": quantities,
      "purchase_price": purchasePrice,
      "expire_date": expireDate,
    };
  }
}
