import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Products/category/category_list_screen.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/manufacturers_list_screen.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/medicine_type_list_screen.dart';
import 'package:mobile_pos/Screens/Products/product_details.dart';
import 'package:mobile_pos/Screens/Products/unit/unit_list.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../constant.dart';
import '../../currency.dart';
import '../barcode/gererate_barcode.dart';
import 'Model/datum_product_model.dart';
import 'Repo/product_repo.dart';
import 'add product/add_product.dart';
import 'box/box_size_list.dart';
import 'bulk product upload/bulk_product_upload_screen.dart';

class ProductList extends StatefulWidget {
  const ProductList({
    super.key,
    required this.isFromExpired,
  });
  final bool isFromExpired;

  @override
  State<ProductList> createState() => _ProductListState();
}

class _ProductListState extends State<ProductList> {
  ///_______Floating button_____________________________________
  bool _isFabVisible = true;
  double _previousScrollOffset = 0;
  //__________________________________controllers_________________________________
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, Datum> pageController = PagingController(firstPageKey: 1);

  ProductRepo service = ProductRepo();
  final TextEditingController _searchController = TextEditingController();
  String? productCode;
  //__________________________________pagination_List_________________________________
  Future<void> fetchProductListData(int pageKey) async {
    ProductListModel? list;
    try {
      list = await service.getProductList(
        search: _searchController.text,
        expireDate: widget.isFromExpired && selectedDays != -1 ? (DateTime.now().add(Duration(days: selectedDays)).toString()) : null,
        expired: widget.isFromExpired && selectedDays == -1 ? true : null,
        nextPage: pageKey.toString(),
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
        final isLastPage = list.data?.lastPage == list.data?.currentPage;
        print(newItems.length);
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
    // TODO: implement initState
    pageController.addPageRequestListener((pageKey) => fetchProductListData(pageKey));
    _scrollController.addListener(() {
      double currentOffset = _scrollController.offset;
      if (currentOffset > _previousScrollOffset && _isFabVisible) {
        setState(() {
          _isFabVisible = false;
        });
      } else if (currentOffset < _previousScrollOffset && !_isFabVisible) {
        setState(() {
          _isFabVisible = true;
        });
      }
      _previousScrollOffset = currentOffset;
    });
    super.initState();
  }

  int selectedDays = -1;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final language = lang.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        // surfaceTintColor: kWhite,
        elevation: 0,
        iconTheme: const IconThemeData(color: kWhite),
        title: Text(
          widget.isFromExpired ? language.expiredList : lang.S.of(context).productList,
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          if (!widget.isFromExpired)
            PopupMenuButton<int>(
              itemBuilder: (context) => [
                PopupMenuItem(
                  onTap: () {
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => const CategoryList(
                                  isFromProductList: true,
                                )));
                  },
                  child: Row(
                    children: [
                      const Icon(
                        IconlyBold.category,
                        color: kGreyTextColor,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        lang.S.of(context).productCategory,
                        //"Product Category",
                        style: gTextStyle.copyWith(color: kGreyTextColor),
                      )
                    ],
                  ),
                ),
                PopupMenuItem(
                  onTap: () {
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => const UnitList(
                                  isFromProductList: true,
                                )));
                  },
                  child: Row(
                    children: [
                      const Icon(
                        Icons.scale,
                        color: kGreyTextColor,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        lang.S.of(context).productUnit,
                        // "Product Unit",
                        style: gTextStyle.copyWith(color: kGreyTextColor),
                      )
                    ],
                  ),
                ),

                ///__________Medicine Type___________________________________
                PopupMenuItem(
                  onTap: () {
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => const MedicineTypeListScreen(
                                  isFromProductList: true,
                                )));
                  },
                  child: Row(
                    children: [
                      const Icon(
                        Icons.medical_information_outlined,
                        color: kGreyTextColor,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        language.medicineType,
                        // "Product Unit",
                        style: gTextStyle.copyWith(color: kGreyTextColor),
                      )
                    ],
                  ),
                ),

                ///__________Box_Size___________________________________
                PopupMenuItem(
                  onTap: () {
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => const BoxList(
                                  isFromProductList: true,
                                )));
                  },
                  child: Row(
                    children: [
                      const Icon(
                        Icons.energy_savings_leaf,
                        color: kGreyTextColor,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        language.leafOrBoxSize,
                        // "Product Unit",
                        style: gTextStyle.copyWith(color: kGreyTextColor),
                      )
                    ],
                  ),
                ),

                ///__________Manufacture___________________________________
                PopupMenuItem(
                  onTap: () {
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => const ManufacturersListScreen(
                                  isFromProductList: true,
                                )));
                  },
                  child: Row(
                    children: [
                      const Icon(
                        Icons.precision_manufacturing,
                        color: kGreyTextColor,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        language.manufacturer,
                        style: gTextStyle.copyWith(color: kGreyTextColor),
                      )
                    ],
                  ),
                ),

                ///__________Bulk_Upload__________________________________
                PopupMenuItem(
                  onTap: () {
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => const BulkUploader(
                                  previousProductCode: [],
                                  previousProductName: [],
                                )));
                  },
                  child: Row(
                    children: [
                      const Icon(
                        Icons.list_alt,
                        color: kGreyTextColor,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        language.bulkUpload,
                        // "Product Unit",
                        style: gTextStyle.copyWith(color: kGreyTextColor),
                      )
                    ],
                  ),
                ),
                PopupMenuItem(
                  onTap: () {
                    Navigator.push(context, MaterialPageRoute(builder: (context) => const BarcodeGeneratorScreen()));
                  },
                  child: Row(
                    children: [
                      const Icon(
                        Icons.barcode_reader,
                        color: kGreyTextColor,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        language.barcodeGenerator,
                        style: gTextStyle.copyWith(color: kGreyTextColor),
                      )
                    ],
                  ),
                ),
              ],
              offset: const Offset(0, 40),
              color: kWhite,
              padding: EdgeInsets.zero,
              elevation: 2,
            ),

          // Expired dropdown
          if (widget.isFromExpired)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: DropdownButton<int>(
                style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white),
                dropdownColor: theme.colorScheme.primary,
                value: selectedDays,
                items: [
                  -1, 0, 7, 15, 30, 60, // Added -1 for "Expired" option
                ].map((int value) {
                  String label;
                  if (value == -1) {
                    label = 'Expired';
                  } else {
                    label = value == 0 ? 'Today' : '$value ${value == 1 ? 'day' : 'days'}';
                  }

                  return DropdownMenuItem<int>(
                    value: value,
                    child: Text(label),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() {
                    selectedDays = value!;
                    pageController.refresh();
                  });
                },
                icon: Icon(
                  Icons.keyboard_arrow_down_rounded,
                  color: kWhite,
                ),
                underline: SizedBox.shrink(),
                isExpanded: false,
              ),
            )
        ],
        centerTitle: true,
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, top: 16),
            child: Row(
              children: [
                Expanded(
                  flex: 4,
                  child: SizedBox(
                    height: 48,
                    child: TextFormField(
                      controller: _searchController,
                      onChanged: (value) {
                        if (value.isEmpty) {
                          pageController.refresh();
                        } else {
                          _searchController.text = value;
                          pageController.refresh();
                        }
                      },
                      decoration: InputDecoration(
                        contentPadding: EdgeInsets.all(10),
                        prefixIcon: Icon(
                          FeatherIcons.search,
                          color: kNutral700,
                        ),
                        hintText: language.searchH,
                        enabledBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kOutlineColor),
                          borderRadius: BorderRadius.circular(30),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kMainColor),
                          borderRadius: BorderRadius.circular(30),
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                    flex: 1,
                    child: GestureDetector(
                      onTap: () async {
                        await showDialog(
                          context: context,
                          useSafeArea: true,
                          builder: (context1) {
                            MobileScannerController controller = MobileScannerController(
                              torchEnabled: false,
                              returnImage: false,
                            );
                            return Container(
                              decoration: BoxDecoration(
                                borderRadius: BorderRadiusDirectional.circular(6.0),
                              ),
                              child: Column(
                                children: [
                                  AppBar(
                                    backgroundColor: Colors.transparent,
                                    iconTheme: const IconThemeData(color: Colors.white),
                                    leading: IconButton(
                                      icon: const Icon(Icons.arrow_back),
                                      onPressed: () {
                                        Navigator.pop(context1);
                                      },
                                    ),
                                  ),
                                  Expanded(
                                    child: MobileScanner(
                                      fit: BoxFit.contain,
                                      controller: controller,
                                      onDetect: (capture) {
                                        final List<Barcode> barcodes = capture.barcodes;

                                        if (barcodes.isNotEmpty) {
                                          final Barcode barcode = barcodes.first;
                                          debugPrint('Barcode found! ${barcode.rawValue}');

                                          productCode = barcode.rawValue!;
                                          _searchController.text = productCode!;
                                          pageController.refresh();

                                          Navigator.pop(context1);
                                        }
                                      },
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        );
                      },
                      child: Container(
                        height: 48,
                        width: 48,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(color: kOutlineBorder),
                        ),
                        child: SvgPicture.asset(
                          'assets/sbarcode.svg',
                          height: 24,
                          width: 24,
                        ),
                      ),
                    ))
              ],
            ),
          ),
          SizedBox(height: 8),
          Divider(
            color: kOutlineBorder,
            thickness: 1.0,
          ),
          Expanded(
            child: RefreshIndicator.adaptive(
              onRefresh: () async => await Future.sync(() => pageController.refresh()),
              child: PagedListView(
                shrinkWrap: true,
                padding: EdgeInsets.zero,
                pagingController: pageController,
                scrollController: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                builderDelegate: PagedChildBuilderDelegate<Datum>(
                  newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                  noItemsFoundIndicatorBuilder: (context) => Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Center(
                      child: EmptyListWidget(
                        title: language.noProductFound,
                      ),
                    ),
                  ),
                  itemBuilder: (context, item, index) => Padding(
                    padding: const EdgeInsets.only(bottom: 4.0),
                    child: InkWell(
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => ProductDetails(
                              id: item.id ?? 0,
                              pagingController: pageController,
                            ),
                          ),
                        );
                      },
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16.0),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Flexible(
                                  child: Text(
                                    item.productName ?? 'n/a',
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
                                    text: '${language.sale}: ',
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    children: [
                                      TextSpan(
                                        text: '$currency${item.salesPrice ?? 'N/A'}',
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
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                RichText(
                                  text: TextSpan(
                                    text: '${language.code}: ',
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    children: [
                                      TextSpan(
                                        text: item.productCode ?? 'N/A',
                                      )
                                    ],
                                  ),
                                ),
                                Flexible(
                                  child: RichText(
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    text: TextSpan(
                                      text: '${language.purchase}: ',
                                      style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                      children: [
                                        TextSpan(
                                          text: '$currency${item.purchaseWithTax ?? 'N/A'}',
                                          style: theme.textTheme.bodyMedium?.copyWith(
                                            color: kNutrals900,
                                            fontWeight: FontWeight.w500,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                RichText(
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  text: TextSpan(
                                    text: '${language.stock}: ',
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    children: [
                                      TextSpan(
                                        text: item.stocksSumProductStock.toString(),
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          color: widget.isFromExpired ? kTitleColor : kMainColor,
                                          fontWeight: FontWeight.w500,
                                        ),
                                      )
                                    ],
                                  ),
                                ),
                                // Spacer(),
                                Flexible(
                                  child: RichText(
                                    maxLines: 1,
                                    textAlign: TextAlign.end,
                                    overflow: TextOverflow.visible,
                                    text: TextSpan(
                                      text: (item.expiringItem != null)
                                          ? _getExpirationStatus(
                                              item.expiringItem?.expireDate.toString() ?? '',
                                            )
                                          : '',
                                      style: theme.textTheme.bodyMedium?.copyWith(
                                        color: widget.isFromExpired ? Color(0xffF44236) : kNutral600,
                                      ),
                                      children: [
                                        TextSpan(
                                          text: (item.expiringItem?.expireDate != null)
                                              ? DateFormat.yMMMd().format(
                                                  DateTime.parse(item.expiringItem?.expireDate.toString() ?? ''),
                                                )
                                              : 'N/A',
                                        ),
                                      ],
                                    ),
                                  ),
                                )
                              ],
                            ),
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
            ),
          ),
        ],
      ),
      flotingActionButton: AnimatedOpacity(
        opacity: _isFabVisible ? 1.0 : 0.0,
        duration: Duration(milliseconds: 300),
        child: Visibility(
          visible: !widget.isFromExpired,
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: FloatingActionButton.extended(
              elevation: 1,
              extendedIconLabelSpacing: 10.0,
              backgroundColor: kMainColor,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(30.0),
              ),
              onPressed: () {
                Navigator.push(
                    context,
                    MaterialPageRoute(
                        builder: (context) => AddProduct(
                              pagingController: pageController,
                              isFromHome: false,
                            ),
                        settings: RouteSettings(name: '/AddProducts')));
                // Navigator.pushNamed(context, '/AddProducts',arguments: {'exampleArgument': pageController},);
              },
              label: Row(
                children: [
                  const Icon(Icons.add, color: Colors.white),
                  const SizedBox(width: 10.0),
                  Text(
                    language.addProduct,
                    style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  String _getExpirationStatus(String expireDate) {
    DateTime parsedExpireDate = DateTime.parse(expireDate);
    DateTime now = DateTime.now();

    if (parsedExpireDate.isBefore(now)) {
      return '${lang.S.of(context).expire}, ';
    } else {
      Duration difference = parsedExpireDate.difference(now);
      if (difference.inDays == 0) {
        return '${lang.S.of(context).expireToday}, ';
      } else {
        return '${lang.S.of(context).expireIn} ${difference.inDays} day\'s, ';
      }
    }
  }
}
