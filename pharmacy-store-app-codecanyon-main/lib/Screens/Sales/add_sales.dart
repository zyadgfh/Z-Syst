import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Customers/parties_list.dart';
import 'package:mobile_pos/Screens/DrugInteraction/drug_interaction_check_screen.dart';
import 'package:mobile_pos/Screens/Sales/sales_add_to_cart_sales_widget.dart';
import 'package:mobile_pos/Screens/Sales/provider/add_to_cart.dart';
import 'package:mobile_pos/Provider/profile_provider.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import '../Products/Repo/product_repo.dart';
import '../Purchase/Model/StockBasedProductModel.dart';
import '../../Repository/API/future_invoice.dart';
import '../../constant.dart';
import '../../currency.dart';
import '../../model/business_info_model.dart' as b;
import '../Customers/Model/parties_model.dart';
import '../Customers/Provider/customer_provider.dart';
import '../Home/home.dart';
import '../Products/add product/drop_down-function.dart';
import '../Sales List/sale_details.dart';
import '../tax rates/model/tax_model.dart';
import '../tax rates/provider/text_repo.dart';
import '../widget/text_field_widget.dart';
import 'Model/sales_data_share_model.dart';
import 'Model/sales_details_model.dart';
import 'Repo/sales_repo.dart';

class AddSalesScreen extends ConsumerStatefulWidget {
  const AddSalesScreen({super.key, this.salesDetails});

  final SalesDetailsModel? salesDetails;

  @override
  AddSalesScreenState createState() => AddSalesScreenState();
}

class AddSalesScreenState extends ConsumerState<AddSalesScreen> {
  String paymentType = 'Cash';

  bool isClicked = false;

  late b.BusinessInformation personalInformationModel;

  bool hasPreselected = false; // Flag to ensure preselection happens only once

  final FocusNode _searchFocus = FocusNode();
  final TextEditingController _searchController = TextEditingController();
  final TextEditingController noteController = TextEditingController();
  final PagingController<int, BatchWiseStockModel> pageController = PagingController(firstPageKey: 1);
  final ScrollController _scrollController = ScrollController();

  // product list
  ProductRepo service = ProductRepo();

  Future<void> fetchProductListData(int pageKey) async {
    StockBasedProductModel? list;
    try {
      list = await service.getBatchBasedProductList(search: _searchController.text, nextPage: pageKey.toString(), stockFilter: true);
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

  @override
  void initState() {
    ref.refresh(salesCartNotifierProvider);
    if (widget.salesDetails != null) {
      final cartProvider = ref.read(salesCartNotifierProvider);
      paymentType = widget.salesDetails?.data?.paymentType ?? 'Cash';
      cartProvider.salesDateController.text = DateTime.parse(widget.salesDetails!.data!.saleDate!).toString();

      for (var element in widget.salesDetails!.data!.details!) {
        cartProvider.salesCartItems.add(ProductsOnCartSalesDataModel(
          productId: element.productId ?? 0,
          expireDate: element.expireDate,
          purchasePrice: element.purchasePrice ?? 0,
          lossProfit: ((element.price ?? 0) - (element.purchasePrice ?? 0)) * (element.quantities ?? 0),
          productName: element.product?.productName,
          price: element.price ?? 0,
          stock: num.tryParse(element.product?.productCurrentStock ?? '') ?? 0,
          quantities: element.quantities ?? 0,
          batchNo: element.batchNo,
        ));
      }
      cartProvider.vatAmount = widget.salesDetails?.data?.taxAmount ?? 0;
      cartProvider.vatAmountController.text = widget.salesDetails?.data?.taxAmount?.toStringAsFixed(2) ?? '0';
      cartProvider.discountTextControllerFlat.text = widget.salesDetails?.data?.discountAmount?.toStringAsFixed(2) ?? '0';
      cartProvider.receiveAmount = widget.salesDetails?.data?.paidAmount ?? 0;
      noteController.text = widget.salesDetails?.data?.meta?.notes ?? '';

      WidgetsBinding.instance.addPostFrameCallback(
        (timeStamp) {
          cartProvider.calculateAmount(discountType: 'flat');
          cartProvider.receivedAmountController.text = widget.salesDetails?.data?.paidAmount.toString() ?? "0";
        },
      );
    }

    pageController.addPageRequestListener((pageKey) => fetchProductListData(pageKey));
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final language = lang.S.of(context);
    final cartProvider = ref.watch(salesCartNotifierProvider);
    final personalData = ref.watch(businessInfoProvider);
    final partiesData = ref.watch(partiesProvider);
    final taxesData = ref.watch(taxProvider);
    return personalData.when(data: (data) {
      personalInformationModel = data;
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: Text(
            lang.S.of(context).addSales,
            style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600),
          ),
          centerTitle: true,
          iconTheme: const IconThemeData(color: Colors.white),
          elevation: 2.0,
          surfaceTintColor: Colors.transparent,
        ),
        body: Padding(
          padding: const EdgeInsets.fromLTRB(13, 20, 13, 8),
          child: SingleChildScrollView(
            child: Column(
              children: [
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        readOnly: true,
                        controller: cartProvider.salesDateController,
                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                        decoration: InputDecoration(
                            labelText: lang.S.of(context).date,
                            suffixIcon: IconButton(
                              onPressed: () async {
                                final DateTime? picked = await showDatePicker(
                                  initialDate: DateTime.parse(cartProvider.salesDateController.text),
                                  firstDate: DateTime(2015, 8),
                                  lastDate: DateTime(2101),
                                  context: context,
                                );
                                if (picked != null) {
                                  final currentTime = DateTime.now();
                                  final selectedDateTime = DateTime(
                                    picked.year,
                                    picked.month,
                                    picked.day,
                                    currentTime.hour,
                                    currentTime.minute,
                                    currentTime.second,
                                    currentTime.millisecond,
                                    currentTime.microsecond,
                                  );
                                  cartProvider.changeSalesDate(givenDate: selectedDateTime);
                                }
                              },
                              icon: const Icon(
                                IconlyLight.calendar,
                                color: kNutral700,
                              ),
                            )),
                      ),
                    ),
                    const SizedBox(width: 16),
                    FutureBuilder(
                      future: FutureInvoice().getFutureInvoice(tag: 'sales'),
                      builder: (context, snapshot) {
                        if (snapshot.hasData) {
                          return Expanded(
                            child: AppTextField(
                              textFieldType: TextFieldType.NAME,
                              initialValue: widget.salesDetails != null ? widget.salesDetails?.data?.invoiceNumber : snapshot.data.toString(),
                              textStyle: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                              readOnly: true,
                              decoration: InputDecoration(
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                labelText: lang.S.of(context).inv,
                                border: const OutlineInputBorder(),
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
                                labelText: lang.S.of(context).inv,
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

                // Party Balance
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    Text(
                      language.partyBalance,
                      style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700, fontWeight: FontWeight.w500),
                    ),
                    Text(
                      cartProvider.selectedParty?.due == null ? ' $currency 0' : ' $currency${cartProvider.selectedParty?.due}',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: kSubTitleColor,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 5),

                ///___________________Customer___________________
                partiesData.when(
                  data: (data) {
                    final List<PartyModel> dataList = data.where((element) => element.type?.toLowerCase() != 'supplier').toList();
                    if (widget.salesDetails != null && widget.salesDetails?.data?.partyId != null && !hasPreselected) {
                      PartyModel matched = dataList.firstWhere(
                        (element) => element.id == widget.salesDetails?.data?.partyId,
                        orElse: () => PartyModel(),
                      );
                      if (matched.id != null) {
                        cartProvider.changeSelectedParty(party: matched, rebuild: false);
                      }
                      if (widget.salesDetails?.data?.taxId == null) {
                        hasPreselected = true;
                      }
                    }
                    return GenericDropdown<PartyModel>(
                      dataAsyncValue: dataList,
                      label: language.customer,
                      hint: language.selectCustomer,
                      ref: ref,
                      labelProvider: (item) => item.name ?? item.phone.toString(),
                      addNewScreenBuilder: () => PartyList(),
                      onChanged: (value) {
                        cartProvider.changeSelectedParty(party: value);
                      },
                      selectedCancelFunction: () {
                        cartProvider.changeSelectedParty(party: null);
                      },
                      clearSelectedButton: cartProvider.selectedParty != null,
                      selectedValue: cartProvider.selectedParty,
                    );
                  },
                  error: (error, stackTrace) {
                    return Text(error.toString());
                  },
                  loading: () {
                    return SizedBox.shrink();
                  },
                ),
                const SizedBox(height: 20),

                ///___________________Product________________________
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
                        language.selectProduct,
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
                          validator: (value) {
                            if (cartProvider.salesCartItems.isEmpty) {
                              return language.pleaseSelectProduct;
                            }
                            return null;
                          },
                          decoration: InputDecoration(
                            contentPadding: EdgeInsets.all(10),
                            prefixIcon: Icon(
                              FeatherIcons.search,
                              color: kNutral700,
                            ),
                            hintText: language.searchProduct,
                            focusedErrorBorder: UnderlineInputBorder(
                              borderSide: BorderSide(color: kMainColor),
                            ),
                            errorBorder: UnderlineInputBorder(
                              borderSide: BorderSide(color: Colors.red),
                            ),
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
                          physics: NeverScrollableScrollPhysics(),
                          builderDelegate: PagedChildBuilderDelegate<BatchWiseStockModel>(
                            newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                            noItemsFoundIndicatorBuilder: (context) => Padding(
                              padding: const EdgeInsets.all(20.0),
                              child: Center(
                                  child: Text(
                                lang.S.of(context).noDataFound,
                                style: theme.textTheme.bodyLarge,
                              )),
                            ),
                            itemBuilder: (context, item, index) => Padding(
                              padding: const EdgeInsets.only(top: 8.0),
                              child: GestureDetector(
                                onTap: () {
                                  cartProvider.selectedProduct = ProductsOnCartSalesDataModel(
                                    productId: item.productId ?? 0,
                                    expireDate: item.expireDate,
                                    purchasePrice: item.product?.taxType == 'exclusive' ? item.product?.purchaseWithTax ?? 0 : item.product?.purchaseWithTax ?? 0,
                                    lossProfit: 0,
                                    productName: item.product?.productName,
                                    price: cartProvider.selectedParty?.type?.toLowerCase() == 'wholesaler'
                                        ? item.product?.wholesalePrice ?? (item.product?.salesPrice ?? 0)
                                        : item.product?.salesPrice ?? 0,
                                    stock: item.productStock ?? 0,
                                    quantities: 1,
                                    batchNo: item.batchNo,
                                  );
                                  _searchController.clear();
                                  _searchFocus.unfocus();
                                },
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 10.0),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      SingleChildScrollView(
                                        scrollDirection: Axis.horizontal,
                                        child: Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            RichText(
                                              text: TextSpan(
                                                text: '${item.product?.productName ?? 'n/a'} > ${language.qty} = ',
                                                style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                                children: [
                                                  TextSpan(
                                                    text: '${item.productStock ?? '0'}',
                                                    style: theme.textTheme.bodyMedium?.copyWith(
                                                      color: kNutrals900,
                                                      fontWeight: FontWeight.w500,
                                                    ),
                                                  ),
                                                  TextSpan(
                                                    text: ' >${language.mrp}= ',
                                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                                  ),
                                                  TextSpan(
                                                      text:
                                                          '${cartProvider.selectedParty?.type?.toLowerCase() == 'wholesaler' ? (item.product?.wholesalePrice ?? (item.product?.salesPrice ?? '0')) : item.product?.salesPrice ?? '0'}',
                                                      style: theme.textTheme.bodyMedium?.copyWith(
                                                        color: kNutrals900,
                                                        fontWeight: FontWeight.w500,
                                                      )),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Divider(thickness: 1.0, color: kOutlineColor)
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),

                      if (cartProvider.selectedProduct != null)
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          spacing: 14,
                          children: [
                            Text(
                              cartProvider.selectedProduct?.productName ?? '',
                              style: theme.textTheme.bodyLarge?.copyWith(
                                color: kTitleColor,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            SalesAddToCartForm(
                              batchWiseStockModel: cartProvider.selectedProduct,
                              isFromEdit: false,
                              previousContext: context,
                            ),
                          ],
                        )
                    ],
                  ),
                ),

                SizedBox(height: 8),

                ///_______Added_ItemS__________________________________________________
                if (cartProvider.salesCartItems.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      spacing: 8,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                language.productList,
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  color: kTitleColor,
                                  fontSize: 20,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                            if (cartProvider.salesCartItems.length >= 2)
                              InkWell(
                                onTap: () {
                                  final productIds = cartProvider.salesCartItems
                                      .map((e) => e.productId.toInt())
                                      .toList();
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => DrugInteractionCheckScreen(
                                        preselectedProductIds: productIds,
                                      ),
                                    ),
                                  );
                                },
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: kMainColor.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Icon(Icons.medical_services_outlined, size: 14, color: kMainColor),
                                      const SizedBox(width: 4),
                                      Text(
                                        'Check Interactions',
                                        style: theme.textTheme.bodySmall?.copyWith(
                                          color: kMainColor,
                                          fontWeight: FontWeight.w600,
                                          fontSize: 11,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                          ],
                        ),
                        Container(
                          padding: EdgeInsets.all(8),
                          decoration: BoxDecoration(color: Color(0xff00987F).withValues(alpha: 0.05)),
                          child: ListView.separated(
                            padding: EdgeInsets.zero,
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: cartProvider.salesCartItems.length,
                            itemBuilder: (context, index) {
                              final addedProduct = cartProvider.salesCartItems[index];
                              return Dismissible(
                                onDismissed: (direction) => cartProvider.deleteToCart(index),
                                key: Key(cartProvider.salesCartItems[index].productId.toString()),
                                child: InkWell(
                                  onTap: () {
                                    showModalBottomSheet(
                                      context: context,
                                      builder: (context2) {
                                        return Column(
                                          children: [
                                            Padding(
                                              padding: const EdgeInsets.symmetric(horizontal: 10.0),
                                              child: Row(
                                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                children: [
                                                  Text(
                                                    language.updateProduct,
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
                                              child: SalesAddToCartForm(
                                                batchWiseStockModel: cartProvider.salesCartItems[index],
                                                isFromEdit: true,
                                                previousContext: context2,
                                              ),
                                            ),
                                          ],
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
                                              addedProduct.productName ?? '',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kTitleColor,
                                                fontWeight: FontWeight.w600,
                                                fontSize: 15,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                              maxLines: 2,
                                            ),
                                          ),
                                        ],
                                      ),
                                      Row(
                                        children: [
                                          Text(
                                            '${language.batch}: ${addedProduct.batchNo}',
                                            style: theme.textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
                                          ),
                                          Spacer(),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.totalPrice}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kGreyTextColor,
                                              ),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${((addedProduct.price ?? 0) * (addedProduct.quantities ?? 0)).toStringAsFixed(2)}',
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
                                              // 'Expire Date: ',
                                              '${language.expiryDate}: ${(addedProduct.expireDate != null && addedProduct.expireDate!.isNotEmpty ? (DateTime.tryParse(addedProduct.expireDate!) != null ? DateFormat.yMMMd().format(DateTime.parse(addedProduct.expireDate!)) : 'Invalid expiry date') : 'N/A')}',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.mrpPrice}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: kGreyTextColor,
                                              ),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${addedProduct.price.toString()}',
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
                                          text: '${language.qty}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(
                                            color: kGreyTextColor,
                                          ),
                                          children: [
                                            TextSpan(
                                              text: addedProduct.quantities.toString(),
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
                        ),
                      ],
                    ),
                  ),

                ///_____Total______________________________
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Title Header
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
                        // 'Tax & Discount',
                        language.taxAndDiscount,
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500),
                      ),
                    ),

                    ///__________Discount__________________________________
                    Padding(
                      padding: const EdgeInsets.all(10.0),
                      child: Row(
                        children: [
                          Text(
                            lang.S.of(context).discount,
                            style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                          ),
                          Spacer(),
                          SizedBox(
                            width: context.width() / 4,
                            height: 30,
                            child: TextFormField(
                              controller: cartProvider.discountTextControllerPercent,
                              onChanged: (value) => cartProvider.calculateDiscount(value: value, percent: true, reCalculate: true),
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
                          SizedBox(
                            width: context.width() / 4,
                            height: 30,
                            child: TextFormField(
                              controller: cartProvider.discountTextControllerFlat,
                              onChanged: (value) => cartProvider.calculateDiscount(value: value, percent: false, reCalculate: true),
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

                    ///_________Vat_Dropdown_______________________________
                    Padding(
                      padding: const EdgeInsets.all(10.0),
                      child: Row(
                        children: [
                          Text(
                            language.vat,
                            style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                          ),
                          Spacer(),
                          SizedBox(
                            width: context.width() / 4,
                            height: 30,
                            child: taxesData.when(
                              data: (data) {
                                List<TaxModel> dataList = data.where((tax) => tax.status == true).toList();
                                if (widget.salesDetails != null && widget.salesDetails?.data?.taxId != null && !hasPreselected) {
                                  TaxModel matched = dataList.firstWhere(
                                    (element) => element.id == widget.salesDetails?.data?.taxId,
                                    orElse: () => TaxModel(),
                                  );
                                  if (matched.id != null) {
                                    cartProvider.selectedTax = matched;
                                  }
                                  hasPreselected = true;
                                }
                                return DropdownButtonFormField<TaxModel>(
                                  icon: cartProvider.selectedTax != null
                                      ? GestureDetector(
                                          onTap: () {
                                            setState(() {
                                              cartProvider.selectedTax = null;
                                              cartProvider.vatAmount = 0;
                                              cartProvider.vatAmountController.clear();
                                              cartProvider.calculateAmount();
                                            });
                                          },
                                          child: Icon(
                                            Icons.close,
                                            color: Colors.red,
                                          ),
                                        )
                                      : Icon(Icons.keyboard_arrow_down),
                                  decoration: InputDecoration(
                                    hintText: language.selectOne,
                                    hintStyle: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                                    border: OutlineInputBorder(borderSide: BorderSide(color: Color(0xff404040))),
                                    focusedBorder: OutlineInputBorder(borderSide: BorderSide(color: Color(0xff404040))),
                                  ),
                                  isExpanded: true,
                                  initialValue: cartProvider.selectedTax,
                                  items: dataList.map((TaxModel tax) {
                                    return DropdownMenuItem<TaxModel>(
                                      value: tax,
                                      child: Text(
                                        tax.name ?? language.noName,
                                        maxLines: 1,
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                                      ),
                                    );
                                  }).toList(),
                                  onChanged: (TaxModel? newValue) {
                                    if (newValue != null) {
                                      cartProvider.setSelectedTax(newValue);
                                    }
                                  },
                                );
                              },
                              error: (error, stackTrace) {
                                return Text(error.toString());
                              },
                              loading: () {
                                return SizedBox.shrink();
                              },
                            ),
                          ),
                          SizedBox(width: 10),
                          SizedBox(
                            width: context.width() / 4,
                            height: 30,
                            child: TextFormField(
                              inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                              textAlign: TextAlign.right,
                              readOnly: true,
                              controller: cartProvider.vatAmountController,
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
                                focusedBorder: const OutlineInputBorder(gapPadding: 0.0, borderSide: BorderSide(color: Color(0xff00987F))),
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
                          // Invoice Overview
                          Column(
                            children: [
                              ///_________Total_amount___________________________
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Text(
                                      language.totalAmount,
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
                                          cartProvider.cartTotalAmount.toStringAsFixed(2),
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

                              ///_________Discount_amount___________________________
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Text(
                                      language.discountAmount,
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
                                          cartProvider.discountAmount.toStringAsFixed(2),
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

                              ///_________Vat_amount___________________________
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Text(
                                      language.vatAmount,
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
                                          cartProvider.vatAmount.toStringAsFixed(2),
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

                              ///_________Total_payable___________________________
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Text(
                                      language.totalPayable,
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
                                          cartProvider.totalPayableAmount.toStringAsFixed(2),
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

                              // Received Amount
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
                                                value: cartProvider.received,
                                                onChanged: (value) => cartProvider.changeIsReceivedValue(),
                                              ),
                                            ),
                                          ),
                                          TextSpan(
                                            text: '  ${language.received}',
                                            recognizer: TapGestureRecognizer()..onTap = () => cartProvider.changeIsReceivedValue(),
                                            style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                  Expanded(
                                    child: TextFormField(
                                      controller: cartProvider.receivedAmountController,
                                      keyboardType: TextInputType.number,
                                      textAlign: TextAlign.end,
                                      decoration: CustomFieldStyles.kUnderlined(
                                        context,
                                        hinText: 'Ex: \$200',
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
                                      language.change,
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
                                          cartProvider.changeAmount.toStringAsFixed(2),
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
                                      language.balanceDue,
                                      style: theme.textTheme.bodyLarge?.copyWith(
                                        color: kNutral700,
                                      ),
                                    ),
                                  ),
                                  Expanded(
                                    child: Text(
                                      cartProvider.dueAmount.toStringAsFixed(2),
                                      textAlign: TextAlign.end,
                                      style: theme.textTheme.bodyMedium?.copyWith(
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          )
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Divider(color: kBorderColor, height: 5),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Text(
                          lang.S.of(context).paymentTypes,
                          style: theme.textTheme.bodyLarge?.copyWith(color: kNutrals900),
                        ),
                        const SizedBox(width: 5),
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
                // const SizedBox(height: 10),
                Divider(color: kBorderColor, height: 5),
                const SizedBox(height: 15),
                TextFormField(
                  controller: noteController,
                  maxLines: 3,
                  decoration: InputDecoration(
                      floatingLabelBehavior: FloatingLabelBehavior.always,
                      labelText: language.note,
                      hintText: language.enterNote,
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
                        onPressed: () async {
                          const Home().launch(context, isNewTask: true);
                        },
                        child: Text(
                          language.cancel,
                          style: theme.textTheme.bodyLarge?.copyWith(
                            fontWeight: FontWeight.w500,
                            // fontSize: 18,
                            color: Colors.red, // Use a color that contrasts with your gradient
                          ),
                        ),
                      )),
                      const SizedBox(width: 10),
                      Expanded(
                        child: NewPrimaryButton(
                          onPressed: () async {
                            if (isClicked) {
                              return;
                            }
                            if (cartProvider.salesCartItems.isEmpty) {
                              ScaffoldMessenger.of(context)
                                ..removeCurrentSnackBar()
                                ..showSnackBar(SnackBar(content: Text(lang.S.of(context).addProductFirst)));
                              return;
                            }
                            if (cartProvider.selectedParty == null && cartProvider.isFullPaid == false) {
                              ScaffoldMessenger.of(context)
                                ..removeCurrentSnackBar()
                                ..showSnackBar(SnackBar(content: Text('You can not sale in due for a walking customer')));
                              return;
                            }
                            try {
                              isClicked = true;
                              EasyLoading.show();

                              SalesDataShareModel data = SalesDataShareModel(
                                taxAmount: cartProvider.vatAmount,
                                taxId: cartProvider.selectedTax?.id,
                                paymentType: paymentType,
                                paidAmount: cartProvider.receiveAmount,
                                isPaid: cartProvider.isFullPaid,
                                discountAmount: cartProvider.discountAmount,
                                dueAmount: cartProvider.dueAmount,
                                products: cartProvider.salesCartItems,
                                partyId: cartProvider.selectedParty?.id,
                                customerPhone: '',
                                notes: noteController.text,
                                saleDate: cartProvider.salesDateController.text,
                                totalAmount: cartProvider.totalPayableAmount,
                              );

                              SaleRepo repo = SaleRepo();
                              SalesDetailsModel? saleData;
                              if (widget.salesDetails == null) {
                                saleData = await repo.createSale(
                                  ref: ref,
                                  context: context,
                                  data: data,
                                );
                                if (saleData != null) {
                                  ref.refresh(salesCartNotifierProvider);
                                  await Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => SalesDetailsScreen(
                                          id: saleData!.data!.id!,
                                        ),
                                      ));
                                  Navigator.pop(context);
                                } else {
                                  isClicked = false;
                                }
                              } else {
                                await repo.updateSale(
                                  ref: ref,
                                  salesId: widget.salesDetails!.data!.id!,
                                  context: context,
                                  data: data,
                                );
                                ref.refresh(salesCartNotifierProvider);
                              }

                              EasyLoading.dismiss();
                            } catch (e) {
                              EasyLoading.dismiss();
                              ScaffoldMessenger.of(context)
                                ..removeCurrentSnackBar()
                                ..showSnackBar(SnackBar(content: Text(e.toString())));
                            }
                          },
                          buttonText: lang.S.of(context).save,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
              ],
            ),
          ),
        ),
      );
    }, error: (e, stack) {
      return Center(
        child: Text(e.toString()),
      );
    }, loading: () {
      return const Center(child: CircularProgressIndicator());
    });
  }
}
