import 'package:mobile_pos/model/sale_transaction_model.dart';

import '../../Screens/Purchase List/model/purchase_details_model.dart';
import '../../Screens/Purchase/Model/purchase_transaction_model.dart';
import '../../Screens/Sales/Model/sales_details_model.dart';
import '../../Screens/due_list/Model/collected_due_list_model.dart';
import '../../Screens/due_list/Model/due_collection_model.dart';
import '../../model/business_info_model.dart';

class PrintThermalSalesInvoiceModel {
  PrintThermalSalesInvoiceModel({required this.salesDetails, required this.personalInformationModel});

  BusinessInformation personalInformationModel;
  SalesDetailsModel? salesDetails;
}

class PrintThermalPurchaseInvoiceModel {
  PrintThermalPurchaseInvoiceModel({required this.purchaseTransitionModel, required this.personalInformationModel});

  BusinessInformation personalInformationModel;
  PurchaseDetailsModel? purchaseTransitionModel;
}

class PrintDueTransactionModel {
  PrintDueTransactionModel({required this.dueTransactionModel, required this.personalInformationModel});

  CollectedDueData? dueTransactionModel;
  BusinessInformation personalInformationModel;
}
