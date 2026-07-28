import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/svg.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:mobile_pos/Screens/stock_list/repo/stock_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../constant.dart';
import '../../currency.dart';
import '../widget/empty_widgets.dart';
import '../widget/key_value_widget.dart';
import 'model/stock_list.dart';

class StockList extends StatefulWidget {
  const StockList({super.key, required this.isFromReport});
  final bool isFromReport;

  @override
  StockListState createState() => StockListState();
}

class StockListState extends State<StockList> {
  String productSearch = '';
  String? productCode;
  //__________________________________controllers_________________________________
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, SingleStockData> pageController = PagingController(firstPageKey: 1);

  ProductStockRepo stock = ProductStockRepo();
  final TextEditingController _searchController = TextEditingController();

  // pagination_List
  Future<void> fetchStockListData(int pageKey) async {
    StockListModel? list;
    try {
      list = await stock.getStockList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
      );
      if (list != null) {
        final newItems = list.stocks?.data ?? [];

        product = list.totalProducts ?? 0;
        lowStock = list.lowStockCount ?? 0;
        stockValue = list.totalStockValue ?? 0;
        final isLastPage = list.stocks?.lastPage == list.stocks?.currentPage;

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
    setState(() {});
  }

  @override
  void initState() {
    pageController.addPageRequestListener((pageKey) => fetchStockListData(pageKey));
    super.initState();
  }

  num product = 0;
  num lowStock = 0;
  num stockValue = 0;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(builder: (context, ref, __) {
      return AcnooScafoldWidget(
        appBar: AppBar(
          title: Text(
            widget.isFromReport ? l.S.of(context).stockList : l.S.of(context).stockList,
            style: GoogleFonts.poppins(color: Colors.white, fontSize: 20.0, fontWeight: FontWeight.w500),
          ),
          iconTheme: const IconThemeData(color: Colors.white),
          centerTitle: true,
          backgroundColor: Colors.transparent,
          elevation: 0.0,
        ),
        body: Column(
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  ContainerWithLabel(
                    color: Color(0xffE1FFD8),
                    label: product.toString(),
                    description: lang.totalItems,
                  ),
                  SizedBox(width: 8),
                  ContainerWithLabel(
                    color: Color(0xffFFE2E2),
                    label: lowStock.toString(),
                    description: lang.lowStock,
                  ),
                  SizedBox(width: 8),
                  ContainerWithLabel(
                    color: Color(0xffF0E2FF),
                    label: '$currency ${stockValue.toString()}',
                    description: lang.stockValue,
                  ),
                ],
              ),
            ),
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
                          }
                        },
                        onFieldSubmitted: (value) async {
                          if (_searchController.text.isNotEmpty) {
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
                          hintText: lang.searchH,
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
                  padding: EdgeInsets.symmetric(horizontal: 16),
                  pagingController: pageController,
                  scrollController: _scrollController,
                  physics: const AlwaysScrollableScrollPhysics(),
                  builderDelegate: PagedChildBuilderDelegate<SingleStockData>(
                    newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                    noItemsFoundIndicatorBuilder: (context) => Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Center(
                        child: EmptyListWidget(
                          title: lang.stockIsEmpty,
                        ),
                      ),
                    ),
                    itemBuilder: (context, item, index) => Padding(
                      padding: const EdgeInsets.only(bottom: 8.0),
                      child: InkWell(
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
                                    text: '${lang.sale}: ',
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    children: [
                                      TextSpan(
                                        text: '$currency${item.salesPrice ?? '0.0'}',
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
                                        text: item.stocksSumProductStock ?? '0.0',
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          color: kMainColor,
                                          fontWeight: FontWeight.w500,
                                        ),
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
                                        text: '$currency${item.purchaseWithTax ?? '0.0'}',
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
                                    text: '${lang.batchNo}: ',
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    children: [
                                      TextSpan(
                                        text: item.stocks?.length == 1
                                            ? item.stocks?.first.batchNo ?? '0.0'
                                            : item.stocks?.where((stock) => stock.batchNo != null).map((stock) => stock.batchNo.toString()).join(', ') ?? '',
                                      ),
                                    ],
                                  ),
                                ),
                                // if (item.stocks?.length == 1) Spacer(),
                                if (item.stocks?.length == 1)
                                  Flexible(
                                    child: RichText(
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      text: TextSpan(
                                        text: item.stocks?.first.expireDate != null ? _getExpirationStatus(item.stocks?.first.expireDate) : '',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        children: [
                                          TextSpan(
                                            text: item.stocks?.first.expireDate?.toString() ?? 'n/a',
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                            Divider(
                              thickness: 1.0,
                              color: kOutlineColor,
                            )
                          ],
                        ),
                        onTap: () {
                          showModalBottomSheet(
                            showDragHandle: true,
                            context: context,
                            builder: (context) {
                              return Padding(
                                padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    ...{
                                      lang.name: item.productName ?? 'n/a',
                                      lang.totalStock: item.stocksSumProductStock ?? 'n/a',
                                      lang.salePrice: '$currency${item.salesPrice}' ?? '0',
                                      lang.purchasePrice: '$currency${item.purchaseWithTax}' ?? '0',
                                    }.entries.map(
                                      (entry) {
                                        return KeyValueRow(
                                          title: entry.key,
                                          titleFlex: 6,
                                          description: entry.value.toString(),
                                          descriptionFlex: 8,
                                        );
                                      },
                                    ),
                                    SizedBox(height: 16),
                                    SizedBox(
                                      width: double.maxFinite,
                                      child: Theme(
                                        data: ThemeData(
                                          dividerColor: Color(0xffE6E6E6),
                                        ),
                                        child: DataTable(
                                          headingRowColor: WidgetStateProperty.all(Color(0xffF2F2F2)),
                                          columnSpacing: 20,
                                          dividerThickness: 0,
                                          decoration: BoxDecoration(
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: Color(0xffE6E6E6), width: 1),
                                          ),
                                          columns: [
                                            DataColumn(
                                              label: Text(
                                                lang.batchNo,
                                                style: TextStyle(fontWeight: FontWeight.bold),
                                              ),
                                            ),
                                            DataColumn(
                                              label: Text(
                                                lang.stock,
                                                style: TextStyle(fontWeight: FontWeight.bold),
                                              ),
                                            ),
                                            DataColumn(
                                              label: Text(
                                                lang.expireDate,
                                                style: TextStyle(fontWeight: FontWeight.bold),
                                              ),
                                            ),
                                          ],
                                          rows: List.generate(
                                            item.stocks?.length ?? 0,
                                            (index) {
                                              var stockItem = item.stocks?[index];

                                              return DataRow(
                                                cells: [
                                                  DataCell(Text(stockItem?.batchNo?.toString() ?? lang.notMentioned)),
                                                  DataCell(Text(stockItem?.productStock?.toString() ?? '0')),
                                                  DataCell(Text(stockItem?.expireDate?.toString() ?? 'n/a')),
                                                ],
                                              );
                                            },
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          );
                        },
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      );
    });
  }

  String _getExpirationStatus(String? expireDate) {
    if (expireDate == null || expireDate.isEmpty) {
      return 'n/a';
    }
    try {
      DateTime parsedExpireDate = DateTime.parse(expireDate);
      DateTime now = DateTime.now();

      if (parsedExpireDate.isBefore(now)) {
        return '${l.S.of(context).expire}, ';
      } else {
        Duration difference = parsedExpireDate.difference(now);
        if (difference.inDays == 0) {
          return '${l.S.of(context).expireToday}, ';
        } else {
          return '${l.S.of(context).expireIn} ${difference.inDays} ${l.S.of(context).days}, ';
        }
      }
    } catch (e) {
      return 'Invalid date format';
    }
  }
}

class ContainerWithLabel extends StatelessWidget {
  final Color color;
  final String label;
  final String description;

  const ContainerWithLabel({
    super.key,
    required this.color,
    required this.label,
    required this.description,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(8.0),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          color: color,
        ),
        height: 72,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Text(
              label,
              maxLines: 1,
              style: theme.textTheme.titleLarge?.copyWith(color: kTitleColor, fontWeight: FontWeight.w600, fontSize: 16),
            ),
            SizedBox(height: 8),
            Text(
              description,
              maxLines: 1,
              textAlign: TextAlign.center,
              style: theme.textTheme.bodySmall?.copyWith(
                color: Color(0xff585865),
                fontWeight: FontWeight.w400,
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
