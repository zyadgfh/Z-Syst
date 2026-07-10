import 'dart:convert';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Purchase%20List/model/purchase_details_model.dart';
import 'package:mobile_pos/Screens/Sales/Model/sales_details_model.dart';
import 'package:mobile_pos/Screens/invoice%20return/repo/invoice_return_repo.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../../constant.dart';
import '../../currency.dart';
import '../../model/add_to_cart_model.dart';
import '../widget/acnoo_scafold.dart';

class InvoiceReturnScreen extends StatefulWidget {
  const InvoiceReturnScreen({super.key, this.sales, this.purchase});

  final SalesDetailsModel? sales;
  final PurchaseDetailsModel? purchase;

  @override
  State<InvoiceReturnScreen> createState() => _InvoiceReturnScreenState();
}

class _InvoiceReturnScreenState extends State<InvoiceReturnScreen> {
  num calculateDiscountForEachProduct({
    required num totalDiscount,
    required num productPrice,
    required num totalPrice,
    required num quantity,
  }) {
    num thisProductDiscount = (totalDiscount * (productPrice * quantity)) / totalPrice;
    // Calculate the total price for this product based on quantity
    num productTotalPrice = productPrice * quantity;

    // Calculate the proportional discount for the entire quantity of this product
    num productWiseTotalDiscount = (productTotalPrice / totalPrice) * totalDiscount;

    // Return the discount per unit of the product
    return productPrice - (thisProductDiscount / quantity);
  }

  double calculateAmountFromPercentage(double percentage, double price) {
    return (percentage * price) / 100;
  }

  num getTotalReturnAmount() {
    num returnAmount = 0;
    for (var element in returnList) {
      if (element.quantity > 0) {
        returnAmount += element.quantity * (num.tryParse(element.subTotal.toString()) ?? 0);
      }
    }
    return returnAmount;
  }

  num getOriginalSalesQuantity({required num detailsId}) {
    return widget.sales?.data?.details?.where((element) => element.id == detailsId).first.quantities ?? 0;
  }

  final TextEditingController _dateController = TextEditingController();
  List<AddToCartModel> returnList = [];
  List<TextEditingController> controllers = [];
  List<FocusNode> focus = [];
  DateTime? _returnDate = DateTime.now();
  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    _dateController.text = DateFormat.yMMMd().format(_returnDate ?? DateTime.now());
    if (widget.sales != null && widget.sales?.data?.details != null) {
      for (var element in widget.sales!.data!.details!) {
        AddToCartModel cartItem = AddToCartModel(
          productName: element.product?.productName,
          subTotal: calculateDiscountForEachProduct(
            productPrice: (element.price ?? 0),
            quantity: (element.quantities ?? 0),
            totalDiscount: (widget.sales?.data?.discountAmount ?? 0),
            totalPrice: (widget.sales?.data?.totalAmount ?? 0) + (widget.sales?.data?.discountAmount ?? 0) - (widget.sales?.data?.taxAmount ?? 0),
          ),
          uuid: element.id ?? 0,
          quantity: 0,
          productId: element.product?.id,
          stock: element.quantities?.round() ?? 0,
          lossProfit: ((element.price ?? 0) - (element.purchasePrice ?? 0)) * (element.quantities ?? 0),
        );

        returnList.add(cartItem);
        controllers.add(TextEditingController());
        focus.add(FocusNode());
      }
    }
    if (widget.purchase != null && widget.purchase?.data?.details != null) {
      for (var element in widget.purchase!.data!.details!) {
        AddToCartModel cartItem = AddToCartModel(
          productName: element.product?.productName,
          subTotal: calculateDiscountForEachProduct(
            productPrice: (element.product?.taxType?.toLowerCase() == 'inclusive' ? (element.purchaseWithTax ?? 0) : (element.purchaseWithoutTax ?? 0)),
            quantity: (element.quantities ?? 0),
            totalDiscount: (widget.purchase?.data?.discountAmount ?? 0),
            totalPrice: (widget.purchase?.data?.totalAmount ?? 0) + (widget.purchase?.data?.discountAmount ?? 0),
          ),
          uuid: element.id ?? 0,
          quantity: 0,
          productId: element.product?.id,
          stock: element.quantities?.round() ?? 0,
        );

        returnList.add(cartItem);
        controllers.add(TextEditingController());
        focus.add(FocusNode());
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentDate = _dateController.text;
    print(_returnDate);
    print('inital date ${_dateController.text}---------');
    final theme = Theme.of(context);
    return Consumer(builder: (context, consumerRef, __) {
      return AcnooScafoldWidget(
        appBar: AppBar(
          iconTheme: const IconThemeData(color: Colors.white),
          backgroundColor: Colors.transparent,
          title: Text(
            widget.sales != null ? 'Sales Return' : "Purchase Return",
            style: theme.textTheme.titleLarge?.copyWith(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 20),
          ),
          centerTitle: true,
          elevation: 0.0,
        ),
        body: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        readOnly: true,
                        initialValue: widget.sales != null ? widget.sales?.data?.invoiceNumber : widget.purchase?.data?.invoiceNumber,
                        decoration: const InputDecoration(
                          floatingLabelBehavior: FloatingLabelBehavior.always,
                          labelText: 'Invoice No',
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                    const SizedBox(width: 20),
                    Expanded(
                      child: TextFormField(
                        readOnly: true,
                        // initialValue: DateFormat.yMMMd().format(DateTime.parse(
                        //   widget.sales != null ? widget.sales!.data!.saleDate! : widget.purchase!.data!.purchaseDate!,
                        // )),
                        controller: _dateController,
                        decoration: InputDecoration(
                          suffixIconConstraints: BoxConstraints(
                            minHeight: 22,
                            minWidth: 22,
                          ),
                          floatingLabelBehavior: FloatingLabelBehavior.always,
                          labelText: lang.S.of(context).date,
                          suffixIcon: IconButton(
                            visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                            padding: EdgeInsets.zero,
                            onPressed: () async {
                              DateTime? picked = await showDatePicker(
                                context: context,
                                firstDate: DateTime(2015, 8),
                                lastDate: DateTime(2101),
                                initialDate: _returnDate,
                              );
                              if (picked != null && picked != _returnDate) {
                                setState(() {
                                  _returnDate = picked;
                                  _dateController.text = DateFormat.yMMMd().format(_returnDate ?? DateTime.now());
                                });
                              }
                              // final DateTime? picked = await showDatePicker(
                              //   initialDate: DateTime.parse(cartProvider.salesDateController.text),
                              //   firstDate: DateTime(2015, 8),
                              //   lastDate: DateTime(2101),
                              //   context: context,
                              // );
                              // if (picked != null) {
                              //   cartProvider.changeSalesDate(givenDate: picked);
                              // }
                            },
                            icon: const Icon(
                              IconlyLight.calendar,
                              color: kNutral700,
                            ),
                          ),
                          border: const OutlineInputBorder(),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 30),
                TextFormField(
                  readOnly: true,
                  initialValue: widget.sales != null ? widget.sales?.data?.party?.name : widget.purchase?.data?.purchaseBy?.name,
                  decoration: InputDecoration(
                    floatingLabelBehavior: FloatingLabelBehavior.always,
                    labelText: lang.S.of(context).customerName,
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 20),

                ///_______Added_ItemS__________________________________________________
                Container(
                  decoration: BoxDecoration(
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(10),
                      topRight: Radius.circular(10),
                    ),
                    border: Border.all(width: 1, color: const Color(0xffEAEFFA)),
                  ),
                  child: Visibility(
                    visible: returnList.isNotEmpty,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: double.infinity,
                          decoration: const BoxDecoration(
                            color: Color(0xffEAEFFA),
                            borderRadius: BorderRadius.only(
                              topLeft: Radius.circular(10),
                              topRight: Radius.circular(10),
                            ),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(10),
                            child: SizedBox(
                              width: MediaQuery.of(context).size.width / 1.35,
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    lang.S.of(context).itemAdded,
                                    style: const TextStyle(fontSize: 16),
                                  ),
                                  Text(
                                    lang.S.of(context).quantity,
                                    style: const TextStyle(fontSize: 16),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: returnList.length,
                            itemBuilder: (context, index) {
                              focus[index].addListener(() {
                                if (!focus[index].hasFocus) {
                                  setState(() {});
                                }
                              });
                              num currentQuantity = returnList[index].quantity;
                              return Padding(
                                padding: const EdgeInsets.only(left: 10, right: 10),
                                child: ListTile(
                                  visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
                                  contentPadding: const EdgeInsets.all(0),
                                  title: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Flexible(
                                        child: Text(
                                          returnList[index].productName.toString(),
                                          overflow: TextOverflow.ellipsis,
                                          maxLines: 1,
                                        ),
                                      ),
                                      const SizedBox(width: 5.0),
                                      const Text('Return QTY'),
                                    ],
                                  ),
                                  subtitle: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        '${(returnList[index].stock ?? 0) - (returnList[index].quantity)} X ${returnList[index].subTotal.toStringAsFixed(2)} = ${double.tryParse((double.parse(returnList[index].subTotal.toString()) * ((returnList[index].stock ?? 0) - currentQuantity)).toStringAsFixed(2)) ?? 0}',
                                      ),
                                      Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          SizedBox(
                                            width: 100,
                                            child: Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                              children: [
                                                GestureDetector(
                                                  onTap: () {
                                                    setState(() {
                                                      returnList[index].quantity > 0 ? returnList[index].quantity-- : returnList[index].quantity = 0;
                                                      controllers[index].text = returnList[index].quantity.toString();
                                                    });
                                                  },
                                                  child: Container(
                                                    height: 20,
                                                    width: 20,
                                                    decoration: const BoxDecoration(
                                                      color: kMainColor,
                                                      borderRadius: BorderRadius.all(Radius.circular(10)),
                                                    ),
                                                    child: const Center(
                                                      child: Text(
                                                        '-',
                                                        style: TextStyle(fontSize: 14, color: Colors.white),
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                                const SizedBox(width: 5),
                                                SizedBox(
                                                  width: 50,
                                                  child: TextFormField(
                                                    onTap: () {
                                                      controllers[index].clear();
                                                    },
                                                    focusNode: focus[index],
                                                    controller: controllers[index],
                                                    textAlign: TextAlign.center,
                                                    keyboardType: TextInputType.number,
                                                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                                                    onChanged: (value) {
                                                      num stock = returnList[index].stock ?? 1;
                                                      if (value.isEmpty || value == '0') {
                                                        value = '1';
                                                      } else if (num.tryParse(value) == null) {
                                                        return;
                                                      } else {
                                                        final newQuantity = num.parse(value);
                                                        if (newQuantity <= stock) {
                                                          returnList[index].quantity = newQuantity.round();
                                                        } else {
                                                          controllers[index].text = '1';
                                                          EasyLoading.showError(
                                                            lang.S.of(context).outOfStock,
                                                            // 'Out Of Stock'
                                                          );
                                                        }
                                                      }
                                                    },
                                                    decoration:
                                                        InputDecoration(border: InputBorder.none, hintText: focus[index].hasFocus ? null : returnList[index].quantity.toString()),
                                                  ),
                                                ),
                                                const SizedBox(width: 5),
                                                GestureDetector(
                                                  onTap: () {
                                                    if (returnList[index].quantity < (returnList[index].stock ?? 0)) {
                                                      setState(() {
                                                        returnList[index].quantity += 1;
                                                        controllers[index].text = returnList[index].quantity.toString();
                                                      });
                                                    } else {
                                                      EasyLoading.showError('Out of Stock');
                                                    }
                                                  },
                                                  child: Container(
                                                    height: 20,
                                                    width: 20,
                                                    decoration: const BoxDecoration(
                                                      color: kMainColor,
                                                      borderRadius: BorderRadius.all(Radius.circular(10)),
                                                    ),
                                                    child: const Center(
                                                        child: Text(
                                                      '+',
                                                      style: TextStyle(fontSize: 14, color: Colors.white),
                                                    )),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            }),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                ///______________________Total_Return____________________________________
                Container(
                  decoration: BoxDecoration(
                    borderRadius: const BorderRadius.all(Radius.circular(10)),
                    border: Border.all(color: Colors.grey.shade300, width: 1),
                  ),
                  child: Column(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: const BoxDecoration(
                          color: Color(0xffEAEFFA),
                          borderRadius: BorderRadius.all(
                            Radius.circular(10.0),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Total return amount:',
                              style: TextStyle(fontSize: 16),
                            ),
                            Text(
                              '$currency ${getTotalReturnAmount().toStringAsFixed(2)}',
                              style: const TextStyle(fontSize: 16),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        bottomNavigationBar: Padding(
          padding: const EdgeInsets.all(10.0),
          child: Row(
            children: [
              Expanded(
                  child: GestureDetector(
                onTap: () {
                  Navigator.pop(context);
                },
                child: Container(
                  height: 60,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: const BorderRadius.all(Radius.circular(30)),
                  ),
                  child: const Center(
                    child: Text(
                      'Cancel',
                      style: TextStyle(fontSize: 18),
                    ),
                  ),
                ),
              )),
              const SizedBox(width: 10),
              Expanded(
                child: GestureDetector(
                  onTap: widget.sales != null
                      ? () async {
                          EasyLoading.show();
                          returnList.removeWhere(
                            (element) => element.quantity < 1,
                          );
                          if (returnList.isNotEmpty) {
                            print('------_-sales date $_returnDate----');
                            num totalDiscountReturn = 0;
                            ReturnDataModel? data = ReturnDataModel(
                              saleId: widget.sales!.data!.id!,
                              returnDate: _returnDate.toString(),
                              saleDetailId: [],
                              returnAmount: [],
                              returnQty: [],
                              lossProfit: [],
                              dueAmount: (widget.sales?.data?.dueAmount ?? 0) < getTotalReturnAmount() ? 0 : (widget.sales?.data?.dueAmount ?? 0) - getTotalReturnAmount(),
                              paidAmount: widget.sales!.data?.paidAmount ?? 0,
                              totalAmount: (widget.sales!.data?.totalAmount ?? 0) - getTotalReturnAmount(),
                              discountAmount: widget.sales!.data?.discountAmount ?? 0,
                            );
                            for (var items in returnList) {
                              final SalesDetails salesProduct = widget.sales!.data!.details![widget.sales!.data!.details!.indexWhere((element) => element.id == items.uuid)];
                              totalDiscountReturn += ((salesProduct.price ?? 0) - items.subTotal) * items.quantity;
                              data.saleDetailId.add(items.uuid);
                              data.returnAmount.add(items.quantity * items.subTotal);
                              data.returnQty.add(items.quantity);
                              data.lossProfit.add((items.lossProfit! / items.stock!) * ((getOriginalSalesQuantity(detailsId: items.uuid)) - items.quantity));
                            }
                            data.discountAmount = data.discountAmount - totalDiscountReturn;
                            print('Return Data ${jsonEncode(data.toJson())}');
                            InvoiceReturnRepo repo = InvoiceReturnRepo();
                            final bool? result = await repo.createSalesReturn(ref: consumerRef, context: context, salesReturn: data);
                            if (result ?? false) {
                              Navigator.pop(context);
                            }
                          } else {
                            EasyLoading.showError('Please select product for return');
                          }
                        }
                      : () async {
                          EasyLoading.show();
                          returnList.removeWhere((element) => element.quantity < 1);
                          if (returnList.isNotEmpty) {
                            num totalDiscountReturn = 0;
                            ReturnDataModel? data = ReturnDataModel(
                              saleId: widget.purchase!.data!.id!,
                              returnDate: _returnDate.toString(),
                              saleDetailId: [],
                              returnAmount: [],
                              returnQty: [],
                              lossProfit: [],
                              dueAmount: (widget.purchase?.data?.dueAmount ?? 0) < getTotalReturnAmount() ? 0 : (widget.purchase?.data?.dueAmount ?? 0) - getTotalReturnAmount(),
                              paidAmount: widget.purchase?.data?.paidAmount ?? 0,
                              totalAmount: (widget.purchase?.data?.totalAmount ?? 0) - getTotalReturnAmount(),
                              discountAmount: widget.purchase?.data?.discountAmount ?? 0,
                            );
                            for (var items in returnList) {
                              final Details purchaseProduct = widget.purchase!.data!.details![widget.purchase!.data!.details!.indexWhere((element) => element.id == items.uuid)];
                              totalDiscountReturn += ((purchaseProduct.product?.taxType?.toLowerCase() == 'inclusive'
                                          ? (purchaseProduct.purchaseWithTax ?? 0)
                                          : (purchaseProduct.purchaseWithoutTax ?? 0)) -
                                      items.subTotal) *
                                  items.quantity;
                              data.saleDetailId.add(items.uuid);
                              data.returnAmount.add(items.quantity * items.subTotal);
                              data.returnQty.add(items.quantity);
                            }
                            data.discountAmount = data.discountAmount - totalDiscountReturn;
                            InvoiceReturnRepo repo = InvoiceReturnRepo();

                            final bool? result = await repo.createPurchaseReturn(ref: consumerRef, context: context, returnData: data);
                            if (result ?? false) {
                              Navigator.pop(context);
                            }
                          } else {
                            EasyLoading.showError('Please select product for return');
                          }
                        },
                  child: Container(
                    height: 60,
                    decoration: const BoxDecoration(
                      color: kMainColor,
                      borderRadius: BorderRadius.all(Radius.circular(30)),
                    ),
                    child: const Center(
                      child: Text(
                        'Confirm return',
                        style: TextStyle(fontSize: 18, color: Colors.white),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    });
  }
}
