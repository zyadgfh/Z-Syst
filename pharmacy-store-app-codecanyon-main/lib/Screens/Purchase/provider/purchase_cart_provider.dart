import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../tax rates/model/tax_model.dart';
import '../Model/purchase_data_and_cart _model.dart';

final purchaseCartProviderMain = ChangeNotifierProvider((ref) => PurchaseCartNotifierMain());

class PurchaseCartNotifierMain extends ChangeNotifier {
  List<ProductsOnCartPurchaseDataModel> purchaseCartItems = [];
  ProductsOnCartPurchaseDataModel? selectedProduct;
  num discountAmount = 0;
  final TextEditingController discountPercentController = TextEditingController();
  final TextEditingController discountAmountController = TextEditingController();
  TaxModel? selectedTax;
  num vatAmount = 0;
  final TextEditingController vatAmountEditingController = TextEditingController();

  ///__________Calculate_vat___________________________________________________
  void calculateVat(bool rebuild) {
    vatAmount = ((getTotalAmount() - discountAmount) * (selectedTax?.rate ?? 0)) / 100;
    vatAmountEditingController.text = vatAmount.toStringAsFixed(2);
    if (rebuild) notifyListeners();
  }


  void calculateDiscount({required String value, required bool percent, required bool rebuild}) {
    if (value == '') {
      discountAmount = 0;
      discountPercentController.clear();
      discountAmountController.clear();
    } else {
      if (percent) {
        if ((num.tryParse(value) ?? 0) <= 100) {
          discountAmount = (num.parse(value) * getTotalAmount()) / 100;
          discountAmountController.text = discountAmount.toStringAsFixed(1);
        } else {
          discountPercentController.clear();
          discountAmountController.clear();
          discountAmount = 0;
          EasyLoading.showError('Enter a valid discount');
        }
      } else {
        if ((num.tryParse(value) ?? 0) <= getTotalAmount()) {
          discountAmount = num.parse(value);

          discountPercentController.text = ((discountAmount * 100) / getTotalAmount()).toStringAsFixed(2);
        } else {
          discountPercentController.clear();
          discountAmountController.clear();
          discountAmount = 0;
          EasyLoading.showError('Enter a valid discount');
        }
      }
    }
    calculateVat(false);
    if (rebuild) notifyListeners();
  }

  double getTotalAmount() {
    double totalAmountOfCart = 0;
    for (var element in purchaseCartItems) {
      totalAmountOfCart += (element.vatType.toLowerCase() == 'exclusive' ? element.purchaseWithoutTax : element.purchaseWithTax) * element.quantities;
    }
    return totalAmountOfCart;
  }

  addToPurchaseCart() {
    bool isNotInList = true;
    for (var element in purchaseCartItems) {
      if (element.productId == selectedProduct?.productId) {
        element.quantities = ((element.quantities ?? 0) + (selectedProduct?.quantities ?? 0));
        element.salesPrice = selectedProduct?.salesPrice ?? 0;
        element.purchaseWithoutTax = selectedProduct!.purchaseWithoutTax;
        element.wholesalePrice = selectedProduct!.wholesalePrice;

        isNotInList = false;
        break;
      }
    }
    if (isNotInList) {
      purchaseCartItems.add(selectedProduct!);
    }

    selectedProduct = null;
    calculateDiscount(value: discountPercentController.text, percent: true,rebuild: false);

    notifyListeners();
  }

  editToPurchaseCart() {
    int index = purchaseCartItems.indexWhere(
      (element) => element.productId == selectedProduct?.productId,
    );
    purchaseCartItems[index] = selectedProduct!;
    selectedProduct = null;
    calculateDiscount(value: discountPercentController.text, percent: true,rebuild: false);
    notifyListeners();
  }

  addToCartRiverPodForEdit(List<ProductsOnCartPurchaseDataModel> cartItem) {
    // cartItemPurchaseList.addAll(iterable)
    purchaseCartItems = cartItem;
    notifyListeners();
  }

  quantityDecrease(int index) {
    if ((purchaseCartItems[index].quantities ?? 0) > 1) {
      int quantity = (purchaseCartItems[index].quantities ?? 0).round();
      quantity--;
      purchaseCartItems[index].quantities = quantity;
    }
    notifyListeners();
  }

  quantityIncrease(int index) {
    int quantity = (purchaseCartItems[index].quantities ?? 0).round();
    quantity++;
    purchaseCartItems[index].quantities = quantity;
    notifyListeners();
  }

  deleteToCart(int index) {
    purchaseCartItems.removeAt(index);
    calculateDiscount(value: discountPercentController.text, percent: true,rebuild: false);

    notifyListeners();
  }
}
