import 'dart:core';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Customers/Provider/customer_provider.dart';
import 'package:mobile_pos/Screens/Customers/parties_list.dart';
import 'package:mobile_pos/Screens/Purchase%20List/provider/purchase_list_provider.dart';
import 'package:mobile_pos/Screens/Purchase%20List/purchase_list_screen.dart';
import 'package:mobile_pos/Screens/Purchase/product_add_to_cart_widget.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:nb_utils/nb_utils.dart';
import '../Products/Repo/product_repo.dart';
import '../Purchase List/model/purchase_details_model.dart';
import '../tax rates/model/tax_model.dart';
import '../tax rates/provider/text_repo.dart';
import 'Model/StockBasedProductModel.dart';
import 'Model/purchase_data_and_cart _model.dart';
import 'provider/purchase_cart_provider.dart';
import '../../Repository/API/future_invoice.dart';
import '../../constant.dart';
import '../../currency.dart';
import '../Customers/Model/parties_model.dart';
import '../Products/add product/drop_down-function.dart';
import '../widget/text_field_widget.dart';
import 'Repo/purchase_repo.dart';

class AddPurchaseScreen extends ConsumerStatefulWidget {
  const AddPurchaseScreen({super.key, this.purchaseDetails});
  final PurchaseDetailsModel? purchaseDetails;
  @override
  AddPurchaseScreenState createState() => AddPurchaseScreenState();
}

class AddPurchaseScreenState extends ConsumerState<AddPurchaseScreen> {
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, BatchWiseStockModel> pageController = PagingController(firstPageKey: 1);
  final ProductRepo service = ProductRepo();
  final TextEditingController _searchController = TextEditingController();
  final TextEditingController dateController = TextEditingController(
      text: DateFormat('yyyy-MM-dd HH:mm:ss').format(
    DateTime.parse(
      DateTime.now().toString(),
    ),
  ));
  final TextEditingController paidAmountController = TextEditingController();

  final TextEditingController noteController = TextEditingController();
  final GlobalKey<FormState> key = GlobalKey();
  List<ProductsOnCartPurchaseDataModel> purchaseCartItems = [];

  double returnAmount = 0;
  double dueAmount = 0;
  double subTotal = 0;
  String? paymentType = 'Cash';

  PartyModel? selectedParty;

  DateTime selectedDate = DateTime.now();
  bool isReceived = false;
  bool isClicked = false;
  bool hasPreselected = false;

  @override
  void initState() {
    super.initState();
    ref.refresh(purchaseCartProviderMain);
    if (widget.purchaseDetails != null) {
      final prevoiusPurchase = widget.purchaseDetails?.data;
      final cartProvider = ref.read(purchaseCartProviderMain);
      paymentType = prevoiusPurchase?.paymentType ?? 'Cash';
      if (prevoiusPurchase?.note != null) noteController.text = prevoiusPurchase?.note;
      if (prevoiusPurchase != null) {
        dateController.text = DateFormat('yyyy-MM-dd HH:mm:ss').format(DateTime.parse(prevoiusPurchase.purchaseDate.toString() ?? ''));
      }
      for (var element in prevoiusPurchase!.details!) {
        cartProvider.purchaseCartItems.add(
          ProductsOnCartPurchaseDataModel(
            productId: element.productId ?? 0,
            vatType: element.product?.taxType ?? '',
            purchaseWithoutTax: element.purchaseWithoutTax ?? 0,
            purchaseWithTax: element.purchaseWithTax ?? 0,
            profitPercent: element.profitPercent ?? 0,
            productName: element.product?.productName ?? '',
            salesPrice: element.salesPrice ?? 0,
            wholesalePrice: element.wholesalePrice ?? 0,
            batchNo: element.batchNo.toString(),
            quantities: element.quantities ?? 0,
          ),
        );
      }
      paidAmountController.text = prevoiusPurchase.paidAmount.toString();
      if (prevoiusPurchase.discountAmount != null) {
        ref.read(purchaseCartProviderMain).discountAmountController.text = prevoiusPurchase.discountAmount.toString();
        ref.read(purchaseCartProviderMain).calculateDiscount(value: prevoiusPurchase.discountAmount.toString(), percent: false, rebuild: false);
      }
      if (prevoiusPurchase.taxAmount != null) {
        ref.read(purchaseCartProviderMain).vatAmountEditingController.text = prevoiusPurchase.taxAmount.toString();
        ref.read(purchaseCartProviderMain).vatAmount = prevoiusPurchase.taxAmount ?? 0;
      }
    }
    // if (widget.purchaseDetails != null) {
    //   // ref.read(purchaseCartProviderMain).purchaseCartItems.add(value);
    //   if (widget.purchaseDetails?.data != null) {
    //     dateController.text = DateFormat.yMd().format(DateTime.parse(widget.purchaseDetails?.data?.purchaseDate.toString() ?? ''));
    //   }
    // }
    pageController.addPageRequestListener((pageKey) => fetchProductListData(pageKey));
  }

  Future<void> fetchProductListData(int pageKey) async {
    try {
      final list = await service.getBatchBasedProductList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        stockFilter: false,
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
        final isLastPage = list.data?.lastPage == list.data?.currentPage;

        if (isLastPage) {
          pageController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          pageController.appendPage(newItems, nextPageKey);
        }
      }
    } catch (error) {
      pageController.error = error;
    }
  }

  double calculateSubtotal({required double total}) {
    subTotal = total - ref.watch(purchaseCartProviderMain).discountAmount + ref.watch(purchaseCartProviderMain).vatAmount;
    return total - ref.watch(purchaseCartProviderMain).discountAmount + ref.watch(purchaseCartProviderMain).vatAmount;
  }

  double calculateReturnAmount({required double total}) {
    return (num.tryParse(paidAmountController.text) ?? 0) <= 0 || (num.tryParse(paidAmountController.text) ?? 0) <= subTotal
        ? 0
        : total - (num.tryParse(paidAmountController.text) ?? 0);
  }

  double calculateDueAmount({required double total}) {
    dueAmount = subTotal - (num.tryParse(paidAmountController.text) ?? 0) < 0 ? 0 : subTotal - (num.tryParse(paidAmountController.text) ?? 0);
    return subTotal - (num.tryParse(paidAmountController.text) ?? 0) < 0 ? 0 : subTotal - (num.tryParse(paidAmountController.text) ?? 0);
  }

  final FocusNode _searchFocus = FocusNode();
  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    final theme = Theme.of(context);
    return Consumer(builder: (context, consumerRef, __) {
      final cartProviderData = consumerRef.watch(purchaseCartProviderMain);
      final partiesData = consumerRef.watch(partiesProvider);
      final taxesData = consumerRef.watch(taxProvider);
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: Text(
            lang.purchaseOrder,
            style: theme.textTheme.titleLarge?.copyWith(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 20),
          ),
          centerTitle: true,
          iconTheme: const IconThemeData(color: Colors.white),
          elevation: 0.0,
        ),
        body: Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
          child: SingleChildScrollView(
            child: Form(
              key: key,
              child: Column(
                children: [
                  ///_______Date_&_Invoice________________________________________________
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          readOnly: true,
                          controller: dateController,
                          style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                          decoration: InputDecoration(
                            labelText: l.S.of(context).date,
                            suffixIcon: IconButton(
                              onPressed: () async {
                                final currentTime = DateTime.now();
                                final DateTime? picked = await showDatePicker(
                                  initialDate: selectedDate ?? currentTime,
                                  firstDate: DateTime(2015, 8),
                                  lastDate: DateTime(2101),
                                  context: context,
                                );
                                if (picked != null && picked != selectedDate) {
                                  setState(() {
                                    selectedDate = picked;
                                    selectedDate = DateTime(
                                      picked.year,
                                      picked.month,
                                      picked.day,
                                      currentTime.hour,
                                      currentTime.minute,
                                      currentTime.second,
                                    );
                                    dateController.text = DateFormat('yyyy-MM-dd HH:mm:ss').format(selectedDate!);
                                  });
                                }
                              },
                              icon: Icon(
                                IconlyLight.calendar,
                                color: kNutral700,
                              ),
                            ),
                          ),
                        )
                        ,
                      ),
                      const SizedBox(width: 10),
                      FutureBuilder(
                        future: FutureInvoice().getFutureInvoice(tag: 'purchases'),
                        builder: (context, snapshot) {
                          if (snapshot.hasData) {
                            return Expanded(
                              child: AppTextField(
                                textFieldType: TextFieldType.NAME,
                                initialValue: widget.purchaseDetails != null ? widget.purchaseDetails?.data?.invoiceNumber : snapshot.data.toString(),
                                textStyle: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                                readOnly: true,
                                decoration: InputDecoration(
                                  labelText: l.S.of(context).inv,
                                ),
                              ),
                            );
                          } else {
                            // return const CircularProgressIndicator();
                            return Expanded(
                              child: TextFormField(
                                readOnly: true,
                                decoration: InputDecoration(
                                  floatingLabelBehavior: FloatingLabelBehavior.always,
                                  labelText: l.S.of(context).inv,
                                  border: const OutlineInputBorder(),
                                ),
                              ),
                            );
                          }
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Text(
                        l.S.of(context).dueAmount,
                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700, fontWeight: FontWeight.w500),
                      ),
                      Text(
                        selectedParty?.due == null ? '$currency 0' : '$currency${selectedParty?.due}',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: kSubTitleColor,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 5),

                  // Supplier
                  partiesData.when(
                    data: (data) {
                      final List<PartyModel> supplierList = data.where((element) => element.type?.toLowerCase() == 'supplier').toList();

                      if (widget.purchaseDetails?.data?.party?.id != null && !hasPreselected) {
                        final matchedData = supplierList.firstWhere(
                          (element) => element.id == widget.purchaseDetails!.data!.party!.id,
                          orElse: () => PartyModel(),
                        );
                        if (matchedData.id != null) {
                          selectedParty = matchedData;
                        }
                      }

                      return GenericDropdown<PartyModel>(
                        dataAsyncValue: supplierList,
                        label: '${lang.supplier}*',
                        hint: lang.selectOne,
                        ref: consumerRef,
                        labelProvider: (item) => item.name ?? item.phone.toString(),
                        addNewScreenBuilder: () => PartyList(),
                        onChanged: (value) {
                          setState(() {
                            selectedParty = value;
                          });
                        },
                        validator: (value) {
                          if (value == null) {
                            return lang.pleaseEnterAValidSupplier;
                          }
                          return null;
                        },
                        selectedCancelFunction: () {
                          setState(() {
                            selectedParty = null;
                          });
                        },
                        clearSelectedButton: selectedParty != null,
                        selectedValue: selectedParty,
                      );
                    },
                    error: (error, stackTrace) {
                      return Text(error.toString());
                    },
                    loading: () {
                      return const DropdownSkeletonWidget();
                    },
                  ),
                  SizedBox(height: 20),
                  Container(
                    padding: EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 12,
                    ),
                    decoration: BoxDecoration(
                      color: Color(0xffF2FAF9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Column(
                      spacing: 12,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          lang.selectProduct,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: kTitleColor,
                            fontSize: 20,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        SizedBox(
                          height: 48,
                          child: TextFormField(
                            focusNode: _searchFocus,
                            controller: _searchController,
                            onChanged: (value) {
                              setState(() {
                                if (_searchController.text.isNotEmpty) {
                                  _searchController.text = value;
                                  pageController.refresh();
                                }
                              });
                            },
                            decoration: InputDecoration(
                              contentPadding: EdgeInsets.all(10),
                              prefixIcon: Icon(
                                FeatherIcons.search,
                                color: kNutral700,
                              ),
                              hintText: lang.searchProduct,
                              enabledBorder: UnderlineInputBorder(
                                borderSide: BorderSide(color: kOutlineColor),
                              ),
                              focusedBorder: UnderlineInputBorder(
                                borderSide: BorderSide(color: kMainColor),
                              ),
                            ),
                          ),
                        ),

                        // Searched_product_list
                        if (_searchController.text.trim().isNotEmpty)
                          PagedListView(
                            shrinkWrap: true,
                            padding: EdgeInsets.zero,
                            pagingController: pageController,
                            scrollController: _scrollController,
                            physics: const AlwaysScrollableScrollPhysics(),
                            builderDelegate: PagedChildBuilderDelegate<BatchWiseStockModel>(
                              newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                              noItemsFoundIndicatorBuilder: (context) => Padding(
                                padding: const EdgeInsets.all(20.0),
                                child: Center(
                                    child: Text(
                                  l.S.of(context).noDataFound,
                                  style: theme.textTheme.bodyLarge,
                                )),
                              ),
                              itemBuilder: (context, item, index) => Padding(
                                padding: const EdgeInsets.only(top: 8.0),
                                child: GestureDetector(
                                  onTap: () {
                                    cartProviderData.selectedProduct = ProductsOnCartPurchaseDataModel(
                                      productId: item.productId!,
                                      vatType: item.product?.taxType ?? '',
                                      salesPrice: item.product!.salesPrice!,
                                      profitPercent: item.product!.profitPercent!,
                                      productName: item.product!.productName!,
                                      wholesalePrice: item.product!.wholesalePrice!,
                                      quantities: 1,
                                      purchaseWithTax: item.product!.purchaseWithTax!,
                                      purchaseWithoutTax: item.product!.purchaseWithoutTax!,
                                      batchNo: item.batchNo ?? '',
                                      expireDate: item.expireDate,
                                    );
                                    // data(context: context, ref: consumerRef);
                                    _searchController.clear();
                                    _searchFocus.unfocus();
                                    // pageController.refresh();
                                  },
                                  child: Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 10.0),
                                    child: Column(
                                      children: [
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Flexible(
                                              child: Text(
                                                item.product?.productName ?? 'n/a',
                                                maxLines: 2,
                                                overflow: TextOverflow.ellipsis,
                                                style: theme.textTheme.titleSmall?.copyWith(
                                                  fontWeight: FontWeight.w500,
                                                  fontSize: 15,
                                                ),
                                              ),
                                            ),
                                            SizedBox(width: 10),
                                            RichText(
                                              text: TextSpan(
                                                text: '${lang.sale}: ',
                                                style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                                children: [
                                                  TextSpan(
                                                    text: '$currency${item.product?.salesPrice ?? 'N/A'}',
                                                    style: theme.textTheme.bodyMedium?.copyWith(
                                                      color: kNutrals900,
                                                      fontWeight: FontWeight.w500,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                        Row(
                                          children: [
                                            RichText(
                                              text: TextSpan(
                                                text: '${lang.stock}: ',
                                                style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                                children: [
                                                  TextSpan(
                                                    text: item.productStock.toString() ?? 'N/A',
                                                  )
                                                ],
                                              ),
                                            ),
                                            Spacer(),
                                            RichText(
                                              text: TextSpan(
                                                text: '${lang.purchase}: ',
                                                style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                                children: [
                                                  TextSpan(
                                                    text: '$currency${item.product?.purchaseWithTax ?? 'N/A'}',
                                                    style: theme.textTheme.bodyMedium?.copyWith(
                                                      color: kNutrals900,
                                                      fontWeight: FontWeight.w500,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                        // Row(
                                        //   children: [
                                        //     RichText(
                                        //       text: TextSpan(
                                        //         text: 'Stock: ',
                                        //         style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        //         children: [
                                        //           TextSpan(
                                        //             text: '${item.stocksSumProductStock.toString()}',
                                        //             style: theme.textTheme.bodyMedium?.copyWith(
                                        //               color: widget.isFromExpired ? kTitleColor : kMainColor,
                                        //               fontWeight: FontWeight.w500,
                                        //             ),
                                        //           )
                                        //         ],
                                        //       ),
                                        //     ),
                                        //     Spacer(),
                                        //     RichText(
                                        //       text: TextSpan(
                                        //         text: (item.expiringItem != null)
                                        //             ? _getExpirationStatus(
                                        //           item.expiringItem?.expireDate.toString() ?? '',
                                        //         )
                                        //             : '',
                                        //         style: theme.textTheme.bodyMedium?.copyWith(
                                        //           color: widget.isFromExpired ? Color(0xffF44236) : kNutral600,
                                        //         ),
                                        //         children: [
                                        //           TextSpan(
                                        //             text: (item.expiringItem != null)
                                        //                 ? DateFormat.yMMMd().format(
                                        //               DateTime.parse(item.expiringItem?.expireDate.toString() ?? ''),
                                        //             )
                                        //                 : 'N/A',
                                        //           ),
                                        //         ],
                                        //       ),
                                        //     )
                                        //   ],
                                        // ),
                                        Divider(
                                          thickness: 1.0,
                                          color: kOutlineColor,
                                        )
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),

                        if (cartProviderData.selectedProduct != null)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            spacing: 14,
                            children: [
                              Text(
                                cartProviderData.selectedProduct?.productName ?? '',
                                style: theme.textTheme.bodyLarge?.copyWith(
                                  color: kTitleColor,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              ProductAddToCartForm(
                                batchWiseStockModel: cartProviderData.selectedProduct,
                                isFromEdit: false,
                              ),
                            ],
                          )
                      ],
                    ),
                  ),

                  SizedBox(height: 8),

                  ///_______Added_ItemS__________________________________________________
                  if (cartProviderData.purchaseCartItems.isNotEmpty)
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      spacing: 8,
                      children: [
                        Text(
                          lang.productList,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: kTitleColor,
                            fontSize: 20,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Container(
                          padding: EdgeInsets.all(8),
                          decoration: BoxDecoration(color: Color(0xff00987F).withOpacity(0.05)),
                          child: ListView.separated(
                            padding: EdgeInsets.zero,
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: cartProviderData.purchaseCartItems.length,
                            itemBuilder: (context, index) {
                              final addedList = cartProviderData.purchaseCartItems[index];

                              return Dismissible(
                                onDismissed: (direction) {
                                  cartProviderData.deleteToCart(index);
                                },
                                key: Key(cartProviderData.purchaseCartItems[index].productId.toString()),
                                child: InkWell(
                                  onTap: () {
                                    cartProviderData.selectedProduct = ProductsOnCartPurchaseDataModel(
                                      productId: addedList.productId,
                                      salesPrice: addedList.salesPrice,
                                      vatType: addedList.vatType,
                                      profitPercent: addedList.profitPercent,
                                      productName: addedList.productName,
                                      wholesalePrice: addedList.wholesalePrice,
                                      quantities: 1,
                                      purchaseWithTax: addedList.purchaseWithTax,
                                      purchaseWithoutTax: addedList.purchaseWithoutTax,
                                      batchNo: addedList.batchNo,
                                      expireDate: addedList.expireDate,
                                    );
                                    showModalBottomSheet(
                                      isScrollControlled: true,
                                      context: context,
                                      builder: (context2) {
                                        return SingleChildScrollView(
                                          child: Column(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Padding(
                                                padding: const EdgeInsets.symmetric(horizontal: 16.0),
                                                child: Row(
                                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                  children: [
                                                    Text(
                                                      lang.updateProduct,
                                                      style: theme.textTheme.bodyMedium?.copyWith(
                                                        color: kTitleColor,
                                                        fontWeight: FontWeight.w600,
                                                        fontSize: 18,
                                                      ),
                                                    ),
                                                    CloseButton(
                                                      onPressed: () => Navigator.pop(context2),
                                                    )
                                                  ],
                                                ),
                                              ),
                                              Divider(
                                                thickness: 1,
                                                color: kBorderColorTextField,
                                              ),
                                              Padding(
                                                padding: const EdgeInsets.all(16.0),
                                                child: ProductAddToCartForm(
                                                  batchWiseStockModel: cartProviderData.purchaseCartItems[index],
                                                  isFromEdit: true,
                                                ),
                                              ),
                                            ],
                                          ),
                                        );
                                      },
                                    );
                                  },
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Expanded(
                                            child: Text(
                                              addedList.productName.toString() ?? '',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kTitleColor,
                                                fontWeight: FontWeight.w600,
                                                fontSize: 15,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                              maxLines: 2,
                                            ),
                                          ),
                                          SizedBox(width: 20),
                                          Text.rich(
                                            TextSpan(
                                              text: '${lang.purchasePrice}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kGreyTextColor,
                                              ),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${addedList.vatType.toLowerCase() == 'exclusive' ? addedList.purchaseWithoutTax : addedList.purchaseWithTax}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: kTitleColor,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              ],
                                            ),
                                            maxLines: 2,
                                            overflow: TextOverflow.ellipsis,
                                          )
                                        ],
                                      ),
                                      Row(
                                        children: [
                                          Text(
                                            '${lang.batch}: ${addedList.batchNo.toString() ?? ''}',
                                            style: theme.textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
                                          ),
                                          Spacer(),
                                          RichText(
                                            text: TextSpan(
                                              text: '${lang.totalPrice}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kGreyTextColor,
                                              ),
                                              children: [
                                                TextSpan(
                                                  text:
                                                      '$currency${((addedList.vatType.toLowerCase() == 'exclusive' ? addedList.purchaseWithoutTax : addedList.purchaseWithTax) * (addedList.quantities)).toStringAsFixed(2)}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: kTitleColor,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                )
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Flexible(
                                            child: Text(
                                              '${lang.expiryDate}: ${(addedList.expireDate != null && addedList.expireDate!.isNotEmpty ? (DateTime.tryParse(addedList.expireDate!) != null ? DateFormat.yMMMd().format(DateTime.parse(addedList.expireDate!)) : lang.invalidExpiryDate) : 'N/A')}',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          RichText(
                                            text: TextSpan(
                                              text: '${lang.mrpPrice}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kGreyTextColor,
                                              ),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${addedList.salesPrice.toString()}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: kTitleColor,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                      RichText(
                                        text: TextSpan(
                                          text: '${lang.qty}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(
                                            color: kGreyTextColor,
                                          ),
                                          children: [
                                            TextSpan(
                                              text: addedList.quantities.toString(),
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kTitleColor,
                                                fontWeight: FontWeight.w500,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            },
                            separatorBuilder: (context, index) {
                              // Divider between each item
                              return Divider(
                                color: kBorderColor, // Customize the color of the divider
                                thickness: 1.0, // Thickness of the divider
                              );
                            },
                          ),
                        )
                      ],
                    ),

                  ///_____TAX_&_____________________________
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Title Header
                      SizedBox(height: 8),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(10),
                        decoration: const BoxDecoration(
                          color: Color(0xffF2FAF9),
                          borderRadius: BorderRadius.only(
                            topRight: Radius.circular(5),
                            topLeft: Radius.circular(5),
                          ),
                        ),
                        child: Text(
                          lang.taxAndDiscount,
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500),
                        ),
                      ),

                      ///_______Discount_____________________________________
                      Padding(
                        padding: const EdgeInsets.all(10.0),
                        child: Row(
                          children: [
                            Text(
                              l.S.of(context).discount,
                              style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                            ),
                            Spacer(),
                            // Percent Input Field
                            SizedBox(
                              width: context.width() / 4,
                              height: 30,
                              child: TextFormField(
                                controller: cartProviderData.discountPercentController,
                                onChanged: (value) => cartProviderData.calculateDiscount(value: value, percent: true, rebuild: true),
                                textAlign: TextAlign.right,
                                cursorColor: Color(0xffFF8C34),
                                decoration: InputDecoration(
                                  contentPadding: const EdgeInsets.only(right: 6.0),
                                  hintText: '0',
                                  border: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xffFF8C34),
                                    ),
                                  ),
                                  enabledBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xffFF8C34),
                                    ),
                                  ),
                                  disabledBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xffFF8C34),
                                    ),
                                  ),
                                  focusedBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xffFF8C34),
                                    ),
                                  ),
                                  prefixIconConstraints: const BoxConstraints(maxWidth: 30.0, minWidth: 30.0),
                                  prefixIcon: Container(
                                    alignment: Alignment.center,
                                    height: 40,
                                    decoration: const BoxDecoration(
                                      color: Color(0xffFF8C34),
                                      borderRadius: BorderRadius.only(
                                        topLeft: Radius.circular(4.0),
                                        bottomLeft: Radius.circular(4.0),
                                      ),
                                    ),
                                    child: Icon(
                                      Icons.percent_outlined,
                                      color: Colors.white,
                                      size: 16,
                                    ),
                                  ),
                                ),
                                keyboardType: TextInputType.number,
                              ),
                            ),
                            SizedBox(width: 10),
                            // Amount Input Field
                            SizedBox(
                              width: context.width() / 4,
                              height: 30,
                              child: TextFormField(
                                controller: cartProviderData.discountAmountController,
                                onChanged: (value) => cartProviderData.calculateDiscount(value: value, percent: false, rebuild: true),
                                textAlign: TextAlign.right,
                                cursorColor: Color(0xff00987F),
                                decoration: InputDecoration(
                                  contentPadding: const EdgeInsets.only(right: 6.0),
                                  hintText: '0',
                                  border: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  enabledBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  disabledBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  focusedBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  prefixIconConstraints: const BoxConstraints(maxWidth: 30.0, minWidth: 30.0),
                                  prefixIcon: Container(
                                    alignment: Alignment.center,
                                    height: 40,
                                    decoration: const BoxDecoration(
                                      color: Color(0xff00987F),
                                      borderRadius: BorderRadius.only(
                                        topLeft: Radius.circular(4.0),
                                        bottomLeft: Radius.circular(4.0),
                                      ),
                                    ),
                                    child: Icon(
                                      LineIcons.dollar_sign,
                                      size: 16,
                                      color: Colors.white,
                                    ),
                                  ),
                                ),
                                keyboardType: TextInputType.number,
                              ),
                            ),
                          ],
                        ),
                      ),

                      ///________Vat__________________________________________
                      Padding(
                        padding: const EdgeInsets.all(10.0),
                        child: Row(
                          children: [
                            Text(
                              lang.vat,
                              style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                            ),
                            Spacer(),
                            // Dropdown for selecting tax
                            SizedBox(
                              width: context.width() / 4,
                              height: 30,
                              child: taxesData.when(
                                data: (data) {
                                  List<TaxModel> dataList = data.where((tax) => tax.status == true).toList();
                                  if (widget.purchaseDetails?.data?.taxId != null && !hasPreselected) {
                                    final matchedData = dataList.firstWhere(
                                      (element) => element.id == widget.purchaseDetails!.data!.taxId,
                                      orElse: () => TaxModel(),
                                    );
                                    if (matchedData.id != null) {
                                      cartProviderData.selectedTax = matchedData;
                                    }
                                    hasPreselected = true;
                                  }
                                  return DropdownButtonFormField<TaxModel>(
                                    icon: cartProviderData.selectedTax != null
                                        ? GestureDetector(
                                            onTap: () {
                                              setState(() {
                                                cartProviderData.selectedTax = null;
                                                cartProviderData.vatAmount = 0;
                                                cartProviderData.vatAmountEditingController.clear();
                                                // cartProviderData.();
                                              });
                                            },
                                            child: Icon(
                                              Icons.close,
                                              color: Colors.red,
                                            ),
                                          )
                                        : Icon(Icons.keyboard_arrow_down),
                                    decoration: InputDecoration(
                                      hintText: lang.selectOne,
                                      hintStyle: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                                      border: OutlineInputBorder(borderSide: BorderSide(color: Color(0xff404040))),
                                      focusedBorder: OutlineInputBorder(borderSide: BorderSide(color: Color(0xff404040))),
                                    ),
                                    isExpanded: true,
                                    value: cartProviderData.selectedTax,
                                    items: dataList.map((TaxModel tax) {
                                      return DropdownMenuItem<TaxModel>(
                                        value: tax,
                                        child: Text(
                                          tax.name ?? lang.noName,
                                          maxLines: 1,
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                                        ),
                                      );
                                    }).toList(),
                                    onChanged: (TaxModel? newValue) {
                                      setState(() {
                                        cartProviderData.selectedTax = newValue;
                                        if (cartProviderData.selectedTax != null) {
                                          cartProviderData.calculateVat(true);
                                        }
                                      });
                                    },
                                    onSaved: (TaxModel? value) {},
                                  );
                                },
                                error: (error, stackTrace) {
                                  return Text(error.toString());
                                },
                                loading: () {
                                  return Container();
                                },
                              ),
                            ),
                            SizedBox(width: 10),
                            // Vat Amount Input Field
                            SizedBox(
                              width: context.width() / 4,
                              height: 30,
                              child: TextFormField(
                                readOnly: true,
                                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                                controller: cartProviderData.vatAmountEditingController,
                                textAlign: TextAlign.right,
                                cursorColor: Color(0xff00987F),
                                decoration: InputDecoration(
                                  contentPadding: const EdgeInsets.only(right: 6.0),
                                  hintText: '0',
                                  border: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  enabledBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  disabledBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  focusedBorder: const OutlineInputBorder(
                                    gapPadding: 0.0,
                                    borderSide: BorderSide(
                                      color: Color(0xff00987F),
                                    ),
                                  ),
                                  prefixIconConstraints: const BoxConstraints(maxWidth: 30.0, minWidth: 30.0),
                                  prefixIcon: Container(
                                    alignment: Alignment.center,
                                    height: 40,
                                    decoration: const BoxDecoration(
                                      color: Color(0xff00987F),
                                      borderRadius: BorderRadius.only(
                                        topLeft: Radius.circular(4.0),
                                        bottomLeft: Radius.circular(4.0),
                                      ),
                                    ),
                                    child: Icon(
                                      LineIcons.dollar_sign,
                                      size: 16,
                                      color: Colors.white,
                                    ),
                                  ),
                                ),
                                keyboardType: TextInputType.number,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  ///________Total_price_section_________________________________
                  Column(
                    children: [
                      SizedBox(height: 16),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(10),
                        decoration: const BoxDecoration(
                          color: Color(0xffF7F7F7),
                          borderRadius: BorderRadius.only(
                            bottomRight: Radius.circular(5),
                            bottomLeft: Radius.circular(5),
                          ),
                        ),
                        child: Column(
                          children: [
                            ///_____Total_amount_______________________________
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Text(
                                    lang.totalAmount,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: InputDecorator(
                                    decoration: CustomFieldStyles.kUnderlined(
                                      context,
                                    ),
                                    child: Align(
                                      alignment: AlignmentDirectional.centerEnd,
                                      child: Text(
                                        cartProviderData.getTotalAmount().toStringAsFixed(2),
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox.square(dimension: 16),

                            ///_______Discount___________________________________
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Text(
                                    lang.discount,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: InputDecorator(
                                    decoration: CustomFieldStyles.kUnderlined(
                                      context,
                                    ),
                                    child: Align(
                                      alignment: AlignmentDirectional.centerEnd,
                                      child: Text(
                                        cartProviderData.discountAmount.toStringAsFixed(2),
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox.square(dimension: 16),

                            ///_______Vat_______________________________________
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Text(
                                    lang.vat,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: InputDecorator(
                                    decoration: CustomFieldStyles.kUnderlined(
                                      context,
                                    ),
                                    child: Align(
                                      alignment: AlignmentDirectional.centerEnd,
                                      child: Text(
                                        cartProviderData.vatAmount.toStringAsFixed(2),
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox.square(dimension: 16),

                            ///______Total_Payable________________________________
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Text(
                                    lang.totalPayable,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: InputDecorator(
                                    decoration: CustomFieldStyles.kUnderlined(
                                      context,
                                    ),
                                    child: Align(
                                      alignment: AlignmentDirectional.centerEnd,
                                      child: Text(
                                        calculateSubtotal(total: cartProviderData.getTotalAmount()).toStringAsFixed(2),
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox.square(dimension: 16),

                            ///______Paid____________________________________
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Text.rich(
                                    TextSpan(
                                      children: [
                                        WidgetSpan(
                                          alignment: PlaceholderAlignment.middle,
                                          child: SizedBox.square(
                                            dimension: 16,
                                            child: Checkbox(
                                              side: BorderSide(
                                                color: theme.colorScheme.primary,
                                              ),
                                              visualDensity: const VisualDensity(
                                                horizontal: -4,
                                                vertical: -4,
                                              ),
                                              value: isReceived,
                                              onChanged: (bool? value) {
                                                if (value != null) {
                                                  setState(() {
                                                    isReceived = value;
                                                    if (isReceived) {
                                                      paidAmountController.text = calculateSubtotal(total: cartProviderData.getTotalAmount()).toStringAsFixed(2);
                                                    } else {
                                                      paidAmountController.clear();
                                                    }
                                                  });
                                                }
                                              },
                                            ),
                                          ),
                                        ),
                                        TextSpan(
                                          text: ' ${lang.paid}',
                                          recognizer: TapGestureRecognizer()
                                            ..onTap = () {
                                              setState(() {
                                                isReceived = !isReceived;

                                                if (isReceived) {
                                                  paidAmountController.text = calculateSubtotal(total: cartProviderData.getTotalAmount()).toStringAsFixed(2);
                                                } else {
                                                  paidAmountController.clear();
                                                }
                                              });
                                            },
                                          style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: TextFormField(
                                    controller: paidAmountController,
                                    onChanged: (value) {
                                      setState(() {});
                                    },
                                    keyboardType: TextInputType.number,
                                    textAlign: TextAlign.end,
                                    decoration: CustomFieldStyles.kUnderlined(
                                      context,
                                      hinText: lang.enterAmount,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            // Change
                            SizedBox(height: 8),
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Text(
                                    lang.change,
                                    style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                                  ),
                                ),
                                Expanded(
                                  child: InputDecorator(
                                    decoration: CustomFieldStyles.kUnderlined(
                                      context,
                                    ),
                                    child: Align(
                                      alignment: AlignmentDirectional.centerEnd,
                                      child: Text(
                                        calculateReturnAmount(total: subTotal).abs().toStringAsFixed(2),
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),

                            // Balance Due
                            SizedBox(height: 8),
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Expanded(
                                  child: Text(
                                    lang.balanceDue,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      color: kNutral700,
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: Text(
                                    calculateDueAmount(total: subTotal).toStringAsFixed(2),
                                    textAlign: TextAlign.end,
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  ///_________Payment_methode____________________________________
                  Divider(color: kBorderColor, height: 5),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Text(
                            l.S.of(context).paymentTypes,
                            style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                          ),
                          const SizedBox(
                            width: 5,
                          ),
                          const Icon(
                            Icons.wallet,
                            color: Colors.green,
                          )
                        ],
                      ),
                      DropdownButtonHideUnderline(
                        child: DropdownButton(
                          value: paymentType,
                          icon: const Icon(Icons.keyboard_arrow_down),
                          items: paymentsTypeList.map((String items) {
                            return DropdownMenuItem(
                              value: items,
                              child: Text(items),
                            );
                          }).toList(),
                          onChanged: (newValue) {
                            setState(() {
                              paymentType = newValue.toString();
                            });
                          },
                        ),
                      ),
                    ],
                  ),
                  Divider(color: kBorderColor, height: 5),

                  ///_________Note________________________________________
                  const SizedBox(height: 30),
                  TextFormField(
                    controller: noteController,
                    maxLines: 3,
                    decoration: InputDecoration(
                        floatingLabelBehavior: FloatingLabelBehavior.always,
                        labelText: lang.note,
                        hintText: lang.enterNote,
                        contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8)),
                  ),
                  const SizedBox(height: 16),
                  Padding(
                    padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            style: OutlinedButton.styleFrom(backgroundColor: Colors.red.withValues(alpha: 0.1), side: BorderSide.none),
                            onPressed: () => Navigator.pop(context),
                            child: Text(
                              l.S.of(context).cancel,
                              style: theme.textTheme.titleMedium?.copyWith(color: Colors.red),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: NewPrimaryButton(
                            onPressed: () async {
                              if (isClicked) {
                                return;
                              }
                              if (!(key.currentState?.validate() ?? false)) {
                                return;
                              }

                              isClicked = true;
                              EasyLoading.show();

                              PurchaseDataShareModel dataModelForPost = PurchaseDataShareModel(
                                partyId: selectedParty?.id ?? 0,
                                products: cartProviderData.purchaseCartItems,
                                totalAmount: subTotal,
                                taxId: cartProviderData.selectedTax?.id,
                                taxAmount: cartProviderData.vatAmount,
                                discountAmount: cartProviderData.discountAmount,
                                paidAmount: (num.tryParse(paidAmountController.text) ?? 0),
                                dueAmount: dueAmount,
                                isPaid: isReceived,
                                paymentType: paymentType.toString(),
                                purchaseDate: selectedDate.toString(),
                                note: noteController.text,
                              );
                              PurchaseRepo repo = PurchaseRepo();

                              if (widget.purchaseDetails == null) {
                                bool? success = await repo.createPurchase(
                                  data: dataModelForPost,
                                  context: context,
                                );
                                if (success) {
                                  // consumerRef.refresh(purchaseCartProviderMain);
                                  // cartProviderData.purchaseCartItems.clear();
                                  // cartProviderData.selectedTax = null;
                                  isClicked = true;

                                  Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => PurchaseListScreen()));
                                } else {
                                  // Reset flag
                                  isClicked = false;
                                }
                              } else {
                                print(dataModelForPost.toJsonForUpdate());
                                final bool? success = await repo.updatePurchase(
                                  ref: ref,
                                  context: context,
                                  id: widget.purchaseDetails?.data?.id ?? 0,
                                  data: dataModelForPost,
                                );
                                if (success ?? false) {
                                  ref.refresh(purchaseDetailsProvider(widget.purchaseDetails?.data?.id ?? 0));
                                  Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => PurchaseListScreen()));
                                } else {
                                  // Reset flag
                                  isClicked = false;
                                }
                              }

                              EasyLoading.dismiss();
                            },
                            buttonText: l.S.of(context).save,
                          ),
                        )
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            ),
          ),
        ),
      );
    });
  }
}
