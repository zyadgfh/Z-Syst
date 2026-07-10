import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Sales/provider/add_to_cart.dart';
import '../Products/add product/drop_down-function.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../widget/primary_button.dart';
import 'Model/sales_data_share_model.dart';

// In Purchase
class SalesAddToCartForm extends StatefulWidget {
  const SalesAddToCartForm({super.key, required this.batchWiseStockModel, required this.isFromEdit, required this.previousContext});
  final ProductsOnCartSalesDataModel? batchWiseStockModel;
  final bool isFromEdit;
  final BuildContext previousContext;

  @override
  ProductAddToCartFormState createState() => ProductAddToCartFormState();
}

class ProductAddToCartFormState extends State<SalesAddToCartForm> {
  GlobalKey<FormState> key = GlobalKey();

  bool isUpdating = false;
  String? selectedDate;
  TextEditingController productStockController = TextEditingController();
  TextEditingController salePriceController = TextEditingController();

  @override
  void initState() {
    if (widget.batchWiseStockModel != null) {
      if (widget.isFromEdit) {
        productStockController.text = widget.batchWiseStockModel?.quantities.toString() ?? '';
      }
      if (widget.batchWiseStockModel?.price != null) salePriceController.text = widget.batchWiseStockModel?.price.toString() ?? '';
    }
    super.initState();
  }

  bool isClicked = false;
  @override
  Widget build(BuildContext context) {
    return Consumer(builder: (context, ref, __) {
      final lang = l.S.of(context);
      final cartProviderData = ref.watch(salesCartNotifierProvider);
      return Form(
        key: key,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Stock quantity
            Row(
              spacing: 10,
              children: [
                Expanded(
                  child: TextFormField(
                    controller: productStockController,
                    validator: (value) {
                      ProductsOnCartSalesDataModel? product = cartProviderData.findAndGetCartProduct(
                          batchNo: cartProviderData.selectedProduct?.batchNo ?? '', productId: cartProviderData.selectedProduct?.productId ?? 0);

                      if (value == null || value.isEmpty || (num.tryParse(value) ?? 0) <= 0) {
                        return lang.enterStockQuantity;
                      } else if (((num.tryParse(value) ?? 0) + (product != null ? product.quantities : 0)) > (widget.batchWiseStockModel?.stock ?? 0)) {
                        return lang.outOfStock;
                      }
                      return null;
                    },
                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      floatingLabelBehavior: FloatingLabelBehavior.always,
                      label: getFieldLabelText(label: '${lang.quantity}*', context: context),
                      hintText: lang.enterStockQuantity,
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
                Expanded(
                  child: TextFormField(
                    controller: salePriceController,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return lang.pleaseEnterAValidSalePrice;
                      }
                      return null;
                    },
                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      floatingLabelBehavior: FloatingLabelBehavior.always,
                      label: getFieldLabelText(label: '${lang.salePrice}*', context: context),
                      hintText: lang.enterAmount,
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: 29),
            Padding(
              padding: widget.isFromEdit ? EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom) : EdgeInsets.zero,
              child: NewPrimaryButton(
                buttonText: lang.save,
                onPressed: () async {
                  if (isClicked) {
                    return;
                  }
                  if (!(key.currentState?.validate() ?? false)) {
                    return;
                  }

                  isClicked = true;
                  widget.batchWiseStockModel?.quantities = num.tryParse(productStockController.text) ?? 0;
                  widget.batchWiseStockModel?.price = num.tryParse(salePriceController.text) ?? 0;
                  widget.batchWiseStockModel?.lossProfit =
                      (((widget.batchWiseStockModel?.price ?? 0) - (widget.batchWiseStockModel?.purchasePrice ?? 0)) * (widget.batchWiseStockModel?.quantities ?? 0));

                  if (widget.isFromEdit) {
                    if (widget.batchWiseStockModel != null) {
                      cartProviderData.editProductInSalesCart(product: widget.batchWiseStockModel!);
                      Navigator.pop(widget.previousContext);
                    }
                  } else {
                    if (cartProviderData.selectedProduct != null) {
                      cartProviderData.addProductToSalesCart();
                      cartProviderData.selectedProduct = null;
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(lang.noProductSelectedAddToCart)),
                      );
                    }
                  }
                },
              ),
            )
          ],
        ),
      );
    });
  }
}
