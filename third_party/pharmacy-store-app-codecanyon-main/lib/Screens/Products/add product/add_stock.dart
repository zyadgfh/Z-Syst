import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Products/add%20product/model/add_stock_model.dart';
import '../../../constant.dart';
import '../../tax rates/create_single_tax.dart';
import '../../tax rates/model/tax_model.dart';
import '../../tax rates/provider/text_repo.dart';
import '../../widget/acnoo_scafold.dart';
import '../../widget/primary_button.dart';
import '../Model/product_model.dart';
import '../Providers/product_provider.dart';
import '../Repo/product_repo.dart';
import '../box/model/box_model.dart';
import '../unit/model/unit_model.dart';
import 'drop_down-function.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;

class AddStock extends StatefulWidget {
  const AddStock({super.key, this.productModel});
  final ProductModel? productModel;
  @override
  State<AddStock> createState() => _AddStockState();
}

class _AddStockState extends State<AddStock> {
  UnitModel? selectedUnit;
  TaxModel? selectedTax;
  String? selectedTaxType;
  bool isUpdating = false;
  String? selectedDate;
  bool isClicked = false;
  BoxSizeModel? selectedBoxSize;
  TextEditingController productStockController = TextEditingController();
  TextEditingController salePriceController = TextEditingController();
  TextEditingController purchaseWithoutTaxController = TextEditingController();
  TextEditingController purchaseWithTaxController = TextEditingController();
  TextEditingController profitPercentController = TextEditingController();
  TextEditingController wholeSalePriceController = TextEditingController();
  TextEditingController batchController = TextEditingController();
  TextEditingController fromDateTextEditingController = TextEditingController();
  @override
  void initState() {
    super.initState();

    if (widget.productModel != null) {
      salePriceController.text = widget.productModel?.productSalePrice.toString() ?? '0';
      purchaseWithoutTaxController.text = widget.productModel?.productPurchasePriceWithoutTax.toString() ?? '0';
      purchaseWithTaxController.text = widget.productModel?.productPurchasePriceWithTax.toString() ?? '0';
      profitPercentController.text = widget.productModel?.profitPercent.toString() ?? '0';
      selectedTaxType = widget.productModel?.taxType ?? '';
      if (widget.productModel?.productWholeSalePrice != null) wholeSalePriceController.text = widget.productModel!.productWholeSalePrice.toString();
      purchaseWithoutTaxController.addListener(updatePricesFromPurchaseEx);
      purchaseWithTaxController.addListener(updatePricesFromPurchaseEx);
      profitPercentController.addListener(updatePricesFromPurchaseEx);
      wholeSalePriceController.addListener(updatePricesFromPurchaseEx);
    }
  }

  void updatePricesFromPurchaseEx() {
    setState(() {
      updatePrices();
    });
  }

  GlobalKey<FormState> key = GlobalKey();
  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    final theme = Theme.of(context);
    return Consumer(builder: (context, ref, __) {
      final taxesData = ref.watch(taxProvider);
      return AcnooScafoldWidget(
        appBar: AppBar(
          title: Text(
            lang.addStock,
            style: theme.textTheme.titleLarge?.copyWith(color: Colors.white),
          ),
          iconTheme: const IconThemeData(color: Colors.white),
          centerTitle: true,
          backgroundColor: Colors.transparent,
          elevation: 0.0,
        ),
        bottomNavigationBar: Padding(
          padding: const EdgeInsets.symmetric(
            horizontal: 16.0,
            vertical: 16,
          ),
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
              EasyLoading.show();
              AddStockModel data = AddStockModel(
                  productStock: productStockController.text,
                  purchaseWithoutTax: num.tryParse(purchaseWithoutTaxController.text.toString()) ?? 0,
                  purchaseWithTax: num.tryParse(purchaseWithTaxController.text.toString()) ?? 0,
                  profitPercent: num.tryParse(profitPercentController.text) ?? 0,
                  salePrice: num.tryParse(salePriceController.text) ?? 0,
                  wholeSalePrice: wholeSalePriceController.text,
                  taxType: selectedTaxType.toString(),
                  taxId: selectedTax?.id.toString(),
                  expireDate: selectedDate,
                  batchNo: batchController.text);
              ProductRepo repo = ProductRepo();
              bool success = await repo.addStock(
                data: data,
                id: widget.productModel?.id.toString() ?? '',
              );

              EasyLoading.dismiss();
              if (success) {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(lang.stockUpdateSuccessFully)));
                ref.refresh(productDetailsProvider(widget.productModel?.id.toString() ?? ''));
                Navigator.pop(context);
              } else {
                isClicked = false;
              }
            },
          ),
        ),
        body: Padding(
          padding: const EdgeInsets.symmetric(
            horizontal: 24,
            vertical: 24,
          ),
          child: SingleChildScrollView(
            child: Form(
              key: key,
              child: Column(
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
                  SizedBox(height: 29),

                  // Applicable Tax
                  Row(
                    children: [
                      Expanded(
                        child: taxesData.when(
                          data: (data) {
                            List<TaxModel> dataList = data.where((tax) => tax.status == true).toList();
                            if (widget.productModel?.taxId != null) {
                              try {
                                selectedTax = dataList.firstWhere(
                                  (element) => element.id == widget.productModel!.taxId,
                                );
                              } catch (e) {
                                selectedTax = null;
                              }
                            } else {
                              selectedTax = null;
                            }
                            return GenericDropdown<TaxModel>(
                              showAddButton: false,
                              dataAsyncValue: dataList,
                              label: lang.applicableTax,
                              hint: lang.selectOne,
                              ref: ref,
                              labelProvider: (item) => item.name.toString(),
                              addNewScreenBuilder: () => CreateSingleTax(),
                              onChanged: (value) {
                                setState(() {
                                  selectedTax = value;
                                  updatePrices();
                                });
                              },
                              onNewItemAdded: (value) async {
                                setState(() {
                                  dataList.insert(0, value!);
                                  selectedTax = value;
                                });
                              },
                              selectedValue: selectedTax,
                            );
                          },
                          error: (error, stackTrace) {
                            return Text(error.toString());
                          },
                          loading: () {
                            return const DropdownSkeletonWidget();
                          },
                        ),
                      ),
                      SizedBox(width: 10),

                      // Tax type
                      Expanded(
                        child: DropdownButtonFormField<String?>(
                          hint: Text('Select Type'),
                          decoration: InputDecoration(labelText: lang.taxType),
                          value: selectedTaxType,
                          items: [
                            "Inclusive",
                            "Exclusive",
                          ]
                              .map((type) => DropdownMenuItem<String?>(
                                    value: type,
                                    child: Text(
                                      type,
                                      style: theme.textTheme.bodyMedium,
                                    ),
                                  ))
                              .toList(),
                          onChanged: (value) {
                            setState(() {
                              selectedTaxType = value;
                              updatePrices();
                            });
                          },
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return lang.pleaseSelectTaxType;
                            }
                            return null;
                          },
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 29),

                  //  Purchase Include & Exclude
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
                            hintText: lang.enterPurchasePrice,
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
                              return lang.pleaseEnterAValidSalePrice;
                            }
                            // You can add more validation logic as needed
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
                            hintText: lang.enterSalePrice,
                            border: const OutlineInputBorder(),
                          ),
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 29),

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
                      SizedBox(
                        width: 10,
                      ),
                      Expanded(
                        child: TextFormField(
                          controller: salePriceController,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return lang.pleaseEnterAValidSalePrice;
                            }
                            // You can add more validation logic as needed
                            return null;
                          },
                          inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            floatingLabelBehavior: FloatingLabelBehavior.always,
                            label: getFieldLabelText(label: '${lang.salePrice}*', context: context),
                            hintText: lang.enterSalePrice,
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
                              // constraints: BoxConstraints(),
                              padding: EdgeInsets.zero,
                              visualDensity: VisualDensity(horizontal: -4, vertical: -4),
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
                                  print(selectedDate);
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
                  )
                ],
              ),
            ),
          ),
        ),
      );
    });
  }

  // update price function
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

    if (selectedTax == null || selectedTax!.rate == null) {
      purchaseWithTaxController.text = purchaseExPrice.toStringAsFixed(2);
      salePriceController.text = salePrice.toStringAsFixed(2);
      wholeSalePriceController.text = salePrice.toStringAsFixed(2);
    } else {
      if (selectedTaxType == 'Exclusive') {
        num taxAmount = (purchaseExPrice * (selectedTax!.rate ?? 0)) / 100;
        purchaseWithTaxController.text = (purchaseExPrice + taxAmount).toStringAsFixed(2);
        salePriceController.text = salePrice.toStringAsFixed(2);
        wholeSalePriceController.text = salePrice.toStringAsFixed(2);
      } else if (selectedTaxType == 'Inclusive') {
        num inTaxAmount = (purchaseExPrice * (selectedTax!.rate ?? 0)) / 100;
        purchaseWithTaxController.text = (purchaseExPrice + inTaxAmount).toStringAsFixed(2);
        salePriceController.text = (purchaseExPrice + inTaxAmount).toStringAsFixed(2);
        wholeSalePriceController.text = (purchaseExPrice + inTaxAmount).toStringAsFixed(2);

        if (profitPercent > 0) {
          num profitAmount = ((purchaseExPrice + inTaxAmount) * profitPercent) / 100;
          salePriceController.text = (purchaseExPrice + inTaxAmount + profitAmount).toStringAsFixed(2);
          wholeSalePriceController.text = (purchaseExPrice + inTaxAmount + profitAmount).toStringAsFixed(2);
        }
      }
    }

    isUpdating = false;
  }

  void updatePurchaseExPriceFromPurchaseInPrice() {
    if (isUpdating) return; // Prevent recursion
    isUpdating = true;

    num purchaseInPrice = num.tryParse(purchaseWithTaxController.text) ?? 0;
    num profitPercent = num.tryParse(profitPercentController.text) ?? 0;

    if (purchaseInPrice <= 0 || profitPercent < 0) {
      isUpdating = false;
      return;
    }

    num purchaseExPrice = purchaseInPrice;

    if (selectedTax != null && selectedTax!.rate != null) {
      if (selectedTaxType == 'Exclusive') {
        num taxAmount = (purchaseExPrice * (selectedTax!.rate ?? 0)) / 100;
        purchaseExPrice = purchaseInPrice - taxAmount;
      } else if (selectedTaxType == 'Inclusive') {
        num inTaxAmount = (purchaseExPrice * (selectedTax!.rate ?? 0)) / 100;
        purchaseExPrice = purchaseInPrice - inTaxAmount;
      }
    }

    purchaseWithoutTaxController.text = purchaseExPrice.toStringAsFixed(2);

    updatePrices();

    isUpdating = false; // Reset the flag after updating
  }
}
