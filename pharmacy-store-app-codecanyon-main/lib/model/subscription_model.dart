class SubscriptionModel {
  SubscriptionModel({
    required this.subscriptionName,
    required this.subscriptionDate,
    required this.saleNumber,
    required this.purchaseNumber,
    required this.partiesNumber,
    required this.dueNumber,
    required this.duration,
    required this.products,
  });

  String subscriptionName, subscriptionDate;
  int saleNumber, purchaseNumber, partiesNumber, dueNumber, duration, products;

  SubscriptionModel.fromJson(Map<dynamic, dynamic> json)
      : subscriptionName = (json['subscription_name'] ?? json['subscriptionName']) as String,
        saleNumber = (json['sale_number'] ?? json['saleNumber']),
        subscriptionDate = (json['subscription_date'] ?? json['subscriptionDate']),
        purchaseNumber = (json['purchase_number'] ?? json['purchaseNumber']),
        partiesNumber = (json['parties_number'] ?? json['partiesNumber']),
        dueNumber = (json['due_number'] ?? json['dueNumber']),
        duration = json['duration'],
        products = json['products'];

  Map<dynamic, dynamic> toJson() => <dynamic, dynamic>{
        'subscriptionName': subscriptionName,
        'subscriptionDate': subscriptionDate,
        'saleNumber': saleNumber,
        'purchaseNumber': purchaseNumber,
        'partiesNumber': partiesNumber,
        'dueNumber': dueNumber,
        'duration': duration,
        'products': products,
      };
}
