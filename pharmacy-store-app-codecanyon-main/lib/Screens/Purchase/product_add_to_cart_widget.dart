import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Purchase/provider/purchase_cart_provider.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../constant.dart';
import '../Products/add product/drop_down-function.dart';
import '../widget/primary_button.dart';
import 'Model/purchase_data_and_cart _model.dart';

// In Purchase
class ProductAddToCartForm extends StatefulWidget {
  const ProductAddToCartForm({super.key, required this.batchWiseStockModel, required this.isFromEdit});
  final ProductsOnCartPurchaseDataModel? batchWiseStockModel;
  final bool isFromEdit;

  @override
  ProductAddToCartFormState createState() => ProductAddToCartFormState();
}

class ProductAddToCartFormState extends State<ProductAddToCartForm> {
  GlobalKey<FormState> key = GlobalKey();

  bool isUpdating = false;
  String? selectedDate;
  TextEditingController productStockController = TextEditingController();
  TextEditingController purchaseWithoutTaxController = TextEditingController();
  TextEditingController purchaseWithTaxController = TextEditingController();
  TextEditingController profitPercentController = TextEditingController();
  TextEditingController salePriceController = TextEditingController();
  TextEditingController wholeSalePriceController = TextEditingController();
  TextEditingController batchController = TextEditingController();
  TextEditingController fromDateTextEditingController = TextEditingController();

  void updatePrices() {
    if (isUpdating) return; // Prevent recursion
    isUpdating = true;

    num purchaseExPrice = num.tryParse(purchaseWithoutTaxController.text) ?? 0;
    num profitPercent = num.tryParse(profitPercentController.text) ?? 0;

    if (purchaseExPrice <= 0 || profitPercent < 0) {
      isUpdating = false;
      return;
    }

    num salePrice = purchaseExPrice;
    if (profitPercent > 0) {
      num profitAmount = (purchaseExPrice * profitPercent) / 100;
      salePrice = purchaseExPrice + profitAmount;
    }

    purchaseWithTaxController.text = purchaseExPrice.toStringAsFixed(2);
    salePriceController.text = salePrice.toStringAsFixed(2);
    wholeSalePriceController.text = salePrice.toStringAsFixed(2);

    isUpdating = false;
  }

  void updatePurchaseExPriceFromPurchaseInPrice() {
    if (isUpdating) return;
    isUpdating = true;

    num purchaseInPrice = num.tryParse(purchaseWithTaxController.text) ?? 0;
    num profitPercent = num.tryParse(profitPercentController.text) ?? 0;

    if (purchaseInPrice <= 0 || profitPercent < 0) {
      isUpdating = false;
      return;
    }

    num purchaseExPrice = purchaseInPrice;

    purchaseWithoutTaxController.text = purchaseExPrice.toStringAsFixed(2);

    updatePrices();

    isUpdating = false;
  }

  @override
  void initState() {
    if (widget.batchWiseStockModel != null) {
      if (widget.isFromEdit) {
        productStockController.text = widget.batchWiseStockModel?.quantities.toString() ?? '';
      }
      if (widget.batchWiseStockModel?.wholesalePrice != null) wholeSalePriceController.text = widget.batchWiseStockModel?.wholesalePrice.toString() ?? '';
      if (widget.batchWiseStockModel?.purchaseWithoutTax != null) {
        purchaseWithoutTaxController.text = widget.batchWiseStockModel?.purchaseWithoutTax.toString() ?? '';
      }
      if (widget.batchWiseStockModel?.purchaseWithTax != null) purchaseWithTaxController.text = widget.batchWiseStockModel?.purchaseWithTax.toString() ?? '';
      if (widget.batchWiseStockModel?.profitPercent != null) profitPercentController.text = widget.batchWiseStockModel?.profitPercent.toString() ?? '';
      if (widget.batchWiseStockModel?.salesPrice != null) salePriceController.text = widget.batchWiseStockModel?.salesPrice.toString() ?? '';
      if (widget.batchWiseStockModel?.batchNo != null) batchController.text = widget.batchWiseStockModel?.batchNo.toString() ?? '';
      if (widget.batchWiseStockModel?.expireDate != null) {
        fromDateTextEditingController.text = DateFormat.yMd().format(DateTime.parse(widget.batchWiseStockModel?.expireDate.toString() ?? ''));
      }
    }
    super.initState();
  }

  bool isClicked = false;
  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    return Consumer(builder: (context, ref, __) {
      final cartProviderData = ref.watch(purchaseCartProviderMain);
      return Form(
        key: key,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Stock quantity
            TextFormField(
              controller: productStockController,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return lang.enterStockQuantity;
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
            SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: purchaseWithoutTaxController,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return lang.pleaseEnterAValidPurchasePrice;
                      }
                      return null;
                    },
                    onChanged: (value) {
                      setState(() {
                        updatePrices();
                      });
                    },
                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      floatingLabelBehavior: FloatingLabelBehavior.always,
                      label: getFieldLabelText(label: '${lang.purchaseExcPrice}*', context: context),
                      hintText: lang.enterAmount,
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
                SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    controller: purchaseWithTaxController,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return lang.enterAmount;
                      }
                      return null;
                    },
                    onChanged: (value) {
                      setState(() {
                        updatePurchaseExPriceFromPurchaseInPrice();
                      });
                    },
                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      floatingLabelBehavior: FloatingLabelBehavior.always,
                      label: getFieldLabelText(label: '${lang.purchaseIncTax}*', context: context),
                      hintText: lang.enterAmount,
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: 20),

            ///_________Purchase_price__&&__MRP_____________________
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: profitPercentController,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return lang.pleaseEnterAValidPercent;
                      }
                      return null;
                    },
                    onChanged: (value) {
                      setState(() {
                        updatePrices();
                      });
                    },
                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      floatingLabelBehavior: FloatingLabelBehavior.always,
                      label: getFieldLabelText(label: '${lang.profitPercent}*', context: context),
                      hintText: lang.enterPercent,
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
                SizedBox(width: 10),
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

            ///_______wholesalePrice_dealer_price_________________
            TextFormField(
              controller: wholeSalePriceController,
              inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                floatingLabelBehavior: FloatingLabelBehavior.always,
                labelText: lang.wholeSalePrice,
                hintText: lang.enterWholesalePrice,
                border: const OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 29),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: batchController,
                    decoration: InputDecoration(
                      labelText: lang.batch,
                      hintText: lang.enterBatchNumber,
                    ),
                  ),
                ),
                SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    keyboardType: TextInputType.name,
                    readOnly: true,
                    controller: fromDateTextEditingController,
                    decoration: InputDecoration(
                      labelText: lang.expireDate,
                      hintText: lang.enterDate,
                      border: const OutlineInputBorder(),
                      suffixIcon: IconButton(
                        onPressed: () async {
                          final DateTime? picked = await showDatePicker(
                            initialDate: DateTime.now(),
                            firstDate: DateTime(2015, 8),
                            lastDate: DateTime(2101),
                            context: context,
                          );
                          setState(() {
                            fromDateTextEditingController.text = DateFormat.yMd().format(picked ?? DateTime.now());
                            selectedDate = picked.toString();
                          });
                        },
                        icon: Icon(
                          IconlyLight.calendar,
                          color: kNutral600,
                          size: 22,
                        ),
                      ),
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

                  if (widget.isFromEdit) {
                    if (cartProviderData.selectedProduct != null) {
                      cartProviderData.selectedProduct?.quantities = num.tryParse(productStockController.text) ?? 0;
                      cartProviderData.selectedProduct?.purchaseWithoutTax = num.tryParse(purchaseWithoutTaxController.text) ?? 0;
                      cartProviderData.selectedProduct?.purchaseWithTax = num.tryParse(purchaseWithTaxController.text) ?? 0;
                      cartProviderData.selectedProduct?.profitPercent = num.tryParse(profitPercentController.text) ?? 0;
                      cartProviderData.selectedProduct?.salesPrice = num.tryParse(salePriceController.text) ?? 0;
                      cartProviderData.selectedProduct?.wholesalePrice = num.tryParse(wholeSalePriceController.text) ?? 0;
                      cartProviderData.selectedProduct?.batchNo = batchController.text;
                      cartProviderData.selectedProduct?.expireDate = selectedDate;
                      cartProviderData.editToPurchaseCart();
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(lang.productEditedSuccessfully)),
                      );
                      cartProviderData.selectedProduct = null;
                      Navigator.pop(context);
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(lang.noProductSelectedToEdit)),
                      );
                    }
                  } else {
                    if (cartProviderData.selectedProduct != null) {
                      cartProviderData.selectedProduct?.quantities = num.tryParse(productStockController.text) ?? 0;
                      cartProviderData.selectedProduct?.purchaseWithoutTax = num.tryParse(purchaseWithoutTaxController.text) ?? 0;
                      cartProviderData.selectedProduct?.purchaseWithTax = num.tryParse(purchaseWithTaxController.text) ?? 0;
                      cartProviderData.selectedProduct?.profitPercent = num.tryParse(profitPercentController.text) ?? 0;
                      cartProviderData.selectedProduct?.salesPrice = num.tryParse(salePriceController.text) ?? 0;
                      cartProviderData.selectedProduct?.wholesalePrice = num.tryParse(wholeSalePriceController.text) ?? 0;
                      cartProviderData.selectedProduct?.batchNo = batchController.text;
                      cartProviderData.selectedProduct?.expireDate = selectedDate;

                      cartProviderData.addToPurchaseCart();
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(lang.productAddedSuccessfully)),
                      );
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
