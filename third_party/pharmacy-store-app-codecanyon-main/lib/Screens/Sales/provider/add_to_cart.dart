import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../Customers/Model/parties_model.dart';
import '../../tax rates/model/tax_model.dart';
import '../Model/sales_data_share_model.dart';

final salesCartNotifierProvider = ChangeNotifierProvider((ref) => SalesCartNotifierMain());

class SalesCartNotifierMain extends ChangeNotifier {
  TextEditingController discountTextControllerFlat = TextEditingController();
  TextEditingController discountTextControllerPercent = TextEditingController();
  TextEditingController vatAmountController = TextEditingController();
  TextEditingController receivedAmountController = TextEditingController();
  // TextEditingController salesDateController = TextEditingController(text: DateTime.now().toString());
  TextEditingController salesDateController = TextEditingController(text: DateFormat('yyyy-MM-dd HH:mm:ss').format(
    DateTime.parse(
      DateTime.now().toString(),
    ),
  ));

  @override
  void addListener(VoidCallback listener) {
    // TODO: implement addListener
    super.addListener(listener);
    receivedAmountController.addListener(
      () {
        receiveAmount = num.tryParse(receivedAmountController.text) ?? 0;
        calculateAmount();
      },
    );
  }

  List<ProductsOnCartSalesDataModel> salesCartItems = [];
  String paymentType = 'Cash';

  num cartTotalAmount = 0;
  num totalPayableAmount = 0;
  TaxModel? selectedTax;
  PartyModel? selectedParty;
  num vatAmount = 0;
  num receiveAmount = 0;
  num changeAmount = 0;
  num dueAmount = 0;
  bool received = false;
  bool isFullPaid = false;

  num discountAmount = 0;
  ProductsOnCartSalesDataModel? selectedProduct;

  void changeSalesDate({required DateTime givenDate}) {
    salesDateController.text = givenDate.toString();
    notifyListeners();
  }

  void changeIsReceivedValue() {
    received = !received;
    calculateAmount();
  }

  void changeSelectedParty({required PartyModel? party, bool? rebuild}) {
    selectedParty = party;
    (rebuild ?? true) ? notifyListeners() : null;
  }

  void calculateDiscount({required String value, required bool percent, required bool reCalculate}) {
    if (value == '') {
      discountAmount = 0;
      discountTextControllerPercent.clear();
      discountTextControllerFlat.clear();
    } else {
      if (percent) {
        if ((num.tryParse(value) ?? 0) <= 100) {
          discountAmount = (num.parse(value) * cartTotalAmount) / 100;
          discountTextControllerFlat.text = discountAmount.toStringAsFixed(1);
        } else {
          discountTextControllerPercent.clear();
          discountTextControllerFlat.clear();
          discountAmount = 0;
          EasyLoading.showError('Enter a valid discount');
        }
      } else {
        if ((num.tryParse(value) ?? 0) <= cartTotalAmount) {
          discountAmount = num.parse(value);
          discountTextControllerPercent.text = ((discountAmount * 100) / cartTotalAmount).toStringAsFixed(2);
        } else {
          discountTextControllerPercent.clear();
          discountTextControllerFlat.clear();
          discountAmount = 0;
          EasyLoading.showError('Enter a valid discount');
        }
      }
    }
    reCalculate ? calculateAmount(discountType: percent ? null : 'flat') : null;
  }

  void setSelectedTax(TaxModel tax) {
    selectedTax = tax;
    calculateAmount();
  }

  void calculateAmount({String? discountType}) {
    cartTotalAmount = 0;
    for (var element in salesCartItems) {
      cartTotalAmount += (num.parse(element.price.toString()) * num.parse(element.quantities.toString()));
    }
    totalPayableAmount = cartTotalAmount;
    (discountType == 'flat')
        ? calculateDiscount(value: discountTextControllerFlat.text, percent: false, reCalculate: false)
        : calculateDiscount(value: discountTextControllerPercent.text, percent: true, reCalculate: false);

    if (discountAmount >= 0) {
      totalPayableAmount -= discountAmount;
    }
    if (selectedTax?.rate != null) {
      vatAmount = (totalPayableAmount * selectedTax!.rate!) / 100;
      vatAmountController.text = vatAmount.toStringAsFixed(2);
    }

    totalPayableAmount += vatAmount;
    if (received) {
      receiveAmount = totalPayableAmount;
      receivedAmountController.text = receiveAmount.toStringAsFixed(2);
    }
    changeAmount = totalPayableAmount < receiveAmount ? receiveAmount - totalPayableAmount : 0;
    dueAmount = totalPayableAmount < receiveAmount ? 0 : totalPayableAmount - receiveAmount;
    if (dueAmount <= 0) isFullPaid = true;
    notifyListeners();
  }

  editProductInSalesCart({required ProductsOnCartSalesDataModel product}) {
    int index = salesCartItems.indexWhere(
      (element) => element.batchNo == product.batchNo && element.productId == product.productId,
    );
    salesCartItems[index] = product;

    calculateAmount();
  }

  ProductsOnCartSalesDataModel? findAndGetCartProduct({required String batchNo, required num productId}) {
    for (var element in salesCartItems) {
      if (element.batchNo == batchNo && element.productId == productId) {
        return element;
      }
    }
    return null;
  }

  addProductToSalesCart() {
    bool isNotInList = true;
    for (var element in salesCartItems) {
      if (element.batchNo == selectedProduct?.batchNo && element.productId == selectedProduct?.productId) {
        element.quantities += selectedProduct?.quantities ?? 0;
        element.price = selectedProduct!.price;
        isNotInList = false;
        break;
      } else {
        isNotInList = true;
      }
    }
    if (isNotInList) {
      salesCartItems.add(selectedProduct!);
    }

    selectedProduct = null;
    calculateAmount();
  }

  deleteToCart(int index) {
    salesCartItems.removeAt(index);
    calculateAmount();
  }
}
