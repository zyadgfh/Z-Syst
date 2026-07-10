// class DashboardOverviewModel {
//   DashboardOverviewModel({
//       String? message,
//       Data? data,}){
//     _message = message;
//     _data = data;
// }
//
//   DashboardOverviewModel.fromJson(dynamic json) {
//     _message = json['message'];
//     _data = json['data'] != null ? Data.fromJson(json['data']) : null;
//   }
//   String? _message;
//   Data? _data;
//
//   String? get message => _message;
//   Data? get data => _data;
//
//   Map<String, dynamic> toJson() {
//     final map = <String, dynamic>{};
//     map['message'] = _message;
//     if (_data != null) {
//       map['data'] = _data?.toJson();
//     }
//     return map;
//   }
//
// }
//
// class Data {
//   Data({
//       num? totalItems,
//       num? totalCategories,
//       num? totalIncome,
//       num? totalExpense,
//       num? totalDue,
//       num? stockQty,
//       num? totalLoss,
//       num? totalProfit,
//       num? stockValue,
//       List<Sales>? sales,
//       List<Purchases>? purchases,}){
//     _totalItems = totalItems;
//     _totalCategories = totalCategories;
//     _totalIncome = totalIncome;
//     _totalExpense = totalExpense;
//     _totalDue = totalDue;
//     // _stockQty = stockQty;
//     _totalLoss = totalLoss;
//     _totalProfit = totalProfit;
//     _sales = sales;
//     _purchases = purchases;
// }
//
//   Data.fromJson(dynamic json) {
//     _totalItems = json['total_items'];
//     _totalCategories = json['total_categories'];
//     _totalIncome = json['total_income'];
//     _totalExpense = json['total_expense'];
//     _totalDue = json['total_due'];
//     // _stockQty = json['stock_qty'];
//     _totalLoss = json['total_loss'];
//     _totalProfit = json['total_profit'];
//     _stockValue = json['stock_value'];
//     if (json['sales'] != null) {
//       _sales = [];
//       json['sales'].forEach((v) {
//         _sales?.add(Sales.fromJson(v));
//       });
//     }
//     if (json['purchases'] != null) {
//       _purchases = [];
//       json['purchases'].forEach((v) {
//         _purchases?.add(Purchases.fromJson(v));
//       });
//     }
//   }
//   num? _totalItems;
//   num? _totalCategories;
//   num? _totalIncome;
//   num? _totalExpense;
//   num? _totalDue;
//   // num? _stockQty;
//   num? _totalLoss;
//   num? _totalProfit;
//   num? _stockValue;
//   List<Sales>? _sales;
//   List<Purchases>? _purchases;
//
//   num? get totalItems => _totalItems;
//   num? get totalCategories => _totalCategories;
//   num? get totalIncome => _totalIncome;
//   num? get totalExpense => _totalExpense;
//   num? get totalDue => _totalDue;
//   // num? get stockQty => _stockQty;
//   num? get totalLoss => _totalLoss;
//   num? get totalProfit => _totalProfit;
//   num? get stockValue => _stockValue;
//   List<Sales>? get sales => _sales;
//   List<Purchases>? get purchases => _purchases;
//
//   Map<String, dynamic> toJson() {
//     final map = <String, dynamic>{};
//     map['total_items'] = _totalItems;
//     map['total_categories'] = _totalCategories;
//     map['total_income'] = _totalIncome;
//     map['total_expense'] = _totalExpense;
//     map['total_due'] = _totalDue;
//     // map['stock_qty'] = _stockQty;
//     map['total_loss'] = _totalLoss;
//     map['total_profit'] = _totalProfit;
//     if (_sales != null) {
//       map['sales'] = _sales?.map((v) => v.toJson()).toList();
//     }
//     if (_purchases != null) {
//       map['purchases'] = _purchases?.map((v) => v.toJson()).toList();
//     }
//     return map;
//   }
//
// }
//
// class Purchases {
//   Purchases({
//       String? date,
//       num? amount,}){
//     _date = date;
//     _amount = amount;
// }
//
//   Purchases.fromJson(dynamic json) {
//     _date = json['date'];
//     _amount = json['amount'];
//   }
//   String? _date;
//   num? _amount;
//
//   String? get date => _date;
//   num? get amount => _amount;
//
//   Map<String, dynamic> toJson() {
//     final map = <String, dynamic>{};
//     map['date'] = _date;
//     map['amount'] = _amount;
//     return map;
//   }
//
// }
//
// class Sales {
//   Sales({
//       String? date,
//       num? amount,}){
//     _date = date;
//     _amount = amount;
// }
//
//   Sales.fromJson(dynamic json) {
//     _date = json['date'];
//     _amount = json['amount'];
//   }
//   String? _date;
//   num? _amount;
//
//   String? get date => _date;
//   num? get amount => _amount;
//
//   Map<String, dynamic> toJson() {
//     final map = <String, dynamic>{};
//     map['date'] = _date;
//     map['amount'] = _amount;
//     return map;
//   }
//
// }

class DashboardOverviewModel {
  DashboardOverviewModel({
    this.message,
    this.data,
  });

  DashboardOverviewModel.fromJson(dynamic json) {
    message = json['message'];
    data = json['data'] != null ? Data.fromJson(json['data']) : null;
  }
  String? message;
  Data? data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['message'] = message;
    if (data != null) {
      map['data'] = data?.toJson();
    }
    return map;
  }
}

class Data {
  Data({
    this.totalCustomers,
    this.totalSuppliers,
    this.totalMedicine,
    this.expiredMedicine,
    this.totalLoss,
    this.totalProfit,
    this.totalSales,
    this.totalPurchase,
    this.sales,
    this.purchases,
    this.loss,
    this.profit,
  });

  Data.fromJson(dynamic json) {
    totalCustomers = json['total_customers'];
    totalSuppliers = json['total_suppliers'];
    totalMedicine = json['total_medicine'];
    expiredMedicine = json['expired_medicine'];
    totalLoss = json['total_loss'];
    totalProfit = json['total_profit'];
    totalSales = json['total_sales'];
    totalPurchase = json['total_purchase'];
    if (json['sales'] != null) {
      sales = [];
      json['sales'].forEach((v) {
        sales?.add(Sales.fromJson(v));
      });
    }
    if (json['purchases'] != null) {
      purchases = [];
      json['purchases'].forEach((v) {
        purchases?.add(Purchases.fromJson(v));
      });
    }
    if (json['loss'] != null) {
      loss = [];
      json['loss'].forEach((v) {
        loss?.add(Loss.fromJson(v));
      });
    }
    if (json['profit'] != null) {
      profit = [];
      json['profit'].forEach((v) {
        profit?.add(Profit.fromJson(v));
      });
    }
  }
  num? totalCustomers;
  num? totalSuppliers;
  num? totalMedicine;
  num? expiredMedicine;
  num? totalLoss;
  num? totalProfit;
  num? totalSales;
  num? totalPurchase;
  List<Sales>? sales;
  List<Purchases>? purchases;
  List<Loss>? loss;
  List<Profit>? profit;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['total_customers'] = totalCustomers;
    map['total_suppliers'] = totalSuppliers;
    map['total_medicine'] = totalMedicine;
    map['expired_medicine'] = expiredMedicine;
    map['total_loss'] = totalLoss;
    map['total_profit'] = totalProfit;
    map['total_sales'] = totalSales;
    map['total_purchase'] = totalPurchase;
    if (sales != null) {
      map['sales'] = sales?.map((v) => v.toJson()).toList();
    }
    if (purchases != null) {
      map['purchases'] = purchases?.map((v) => v.toJson()).toList();
    }
    if (loss != null) {
      map['loss'] = loss?.map((v) => v.toJson()).toList();
    }
    if (profit != null) {
      map['profit'] = profit?.map((v) => v.toJson()).toList();
    }
    return map;
  }
}

class Profit {
  Profit({
    this.date,
    this.amount,
  });

  Profit.fromJson(dynamic json) {
    date = json['date'];
    amount = json['amount'];
  }
  String? date;
  num? amount;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['date'] = date;
    map['amount'] = amount;
    return map;
  }
}

class Loss {
  Loss({
    this.date,
    this.amount,
  });

  Loss.fromJson(dynamic json) {
    date = json['date'];
    amount = json['amount'];
  }
  String? date;
  num? amount;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['date'] = date;
    map['amount'] = amount;
    return map;
  }
}

class Purchases {
  Purchases({
    this.date,
    this.amount,
  });

  Purchases.fromJson(dynamic json) {
    date = json['date'];
    amount = json['amount'];
  }
  String? date;
  num? amount;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['date'] = date;
    map['amount'] = amount;
    return map;
  }
}

class Sales {
  Sales({
    this.date,
    this.amount,
  });

  Sales.fromJson(dynamic json) {
    date = json['date'];
    amount = json['amount'];
  }
  String? date;
  num? amount;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['date'] = date;
    map['amount'] = amount;
    return map;
  }
}
