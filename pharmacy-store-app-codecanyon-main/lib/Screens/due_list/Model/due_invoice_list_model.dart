class DueInvoiceListModel {
  String message;
  Data data;

  DueInvoiceListModel({
    required this.message,
    required this.data,
  });

  DueInvoiceListModel copyWith({
    String? message,
    Data? data,
  }) =>
      DueInvoiceListModel(
        message: message ?? this.message,
        data: data ?? this.data,
      );

  factory DueInvoiceListModel.fromJson(Map<String, dynamic> json) => DueInvoiceListModel(
        message: json["message"],
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message,
        "data": data.toJson(),
      };
}

class Data {
  int id;
  int due;
  int openingBalance;
  String name;
  String type;
  List<SalesDues> salesDues;

  Data({
    required this.id,
    required this.due,
    required this.openingBalance,
    required this.name,
    required this.type,
    required this.salesDues,
  });

  Data copyWith({
    int? id,
    int? due,
    int? openingBalance,
    String? name,
    String? type,
    List<SalesDues>? salesDues,
  }) =>
      Data(
        id: id ?? this.id,
        due: due ?? this.due,
        openingBalance: openingBalance ?? this.openingBalance,
        name: name ?? this.name,
        type: type ?? this.type,
        salesDues: salesDues ?? this.salesDues,
      );

  factory Data.fromJson(Map<String, dynamic> json) => Data(
      id: json["id"],
      due: json["due"],
      openingBalance: json["opening_balance"],
      name: json["name"],
      type: json["type"],
      salesDues: json["sales_dues"] != null
          ? List<SalesDues>.from(json["sales_dues"].map((x) => SalesDues.fromJson(x)))
          : json["purchases_dues"] != null
              ? List<SalesDues>.from(json["purchases_dues"].map((x) => SalesDues.fromJson(x)))
              : []);

  Map<String, dynamic> toJson() => {
        "id": id,
        "due": due,
        "opening_balance": openingBalance,
        "name": name,
        "type": type,
        "sales_dues": List<dynamic>.from(salesDues.map((x) => x.toJson())),
      };
}

class SalesDues {
  int id;
  int partyId;
  int dueAmount;
  int paidAmount;
  int totalAmount;
  String invoiceNumber;

  SalesDues({
    required this.id,
    required this.partyId,
    required this.dueAmount,
    required this.paidAmount,
    required this.totalAmount,
    required this.invoiceNumber,
  });

  SalesDues copyWith({
    int? id,
    int? partyId,
    int? dueAmount,
    int? paidAmount,
    int? totalAmount,
    String? invoiceNumber,
  }) =>
      SalesDues(
        id: id ?? this.id,
        partyId: partyId ?? this.partyId,
        dueAmount: dueAmount ?? this.dueAmount,
        paidAmount: paidAmount ?? this.paidAmount,
        totalAmount: totalAmount ?? this.totalAmount,
        invoiceNumber: invoiceNumber ?? this.invoiceNumber,
      );

  factory SalesDues.fromJson(Map<String, dynamic> json) => SalesDues(
        id: json["id"],
        partyId: json["party_id"],
        dueAmount: json["dueAmount"],
        paidAmount: json["paidAmount"],
        totalAmount: json["totalAmount"],
        invoiceNumber: json["invoiceNumber"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "party_id": partyId,
        "dueAmount": dueAmount,
        "paidAmount": paidAmount,
        "totalAmount": totalAmount,
        "invoiceNumber": invoiceNumber,
      };
}
