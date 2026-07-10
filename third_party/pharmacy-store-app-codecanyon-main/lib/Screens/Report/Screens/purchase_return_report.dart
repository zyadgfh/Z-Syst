import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import '../../../constant.dart';
import '../../../currency.dart';
import '../../Purchase List/purchase_details.dart';
import '../../stock_list/stock_list.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../widget/acnoo_scafold.dart';
import '../components/report_components.dart';
import '../model/purchase_return_report.dart';
import '../repo/report_repo.dart';

class PurchaseReturnReport extends StatefulWidget {
  const PurchaseReturnReport({super.key});

  @override
  State<PurchaseReturnReport> createState() => _PurchaseReportState();
}

class _PurchaseReportState extends State<PurchaseReturnReport> {
  num totalQuantity = 0;
  num totalReturn = 0;

  // ---Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, PurchaseReturnData> pageController = PagingController(firstPageKey: 1);

  // Sale Return Repo
  ReportRepo purchases = ReportRepo();
  final TextEditingController _searchController = TextEditingController();
  String? productCode;

  // ---Fetch Sale  List
  Future<void> fetchSalesDataList(int pageKey) async {
    PurchaseReturnReportModel? list;
    try {
      list = await purchases.getPurchaseReturnReportList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        fromDate: _fromDate?.toString() ?? '',
        toDate: _toDate?.toString() ?? '',
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
        totalReturn = list.totalReturn ?? 0;
        totalQuantity = list.totalQty ?? 0;
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
    setState(() {});
  }

  @override
  void initState() {
    pageController.addPageRequestListener((pageKey) => fetchSalesDataList(pageKey));
    super.initState();
  }

  // -------- Filter
  DateTime? _fromDate;
  DateTime? _toDate;
  String? _status;

  void _onDateRangeSelected(DateTime? fromDate, DateTime? toDate, String? status) {
    setState(() {
      _fromDate = fromDate;
      _toDate = toDate;
      _status = status;
    });
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    final theme = Theme.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        title: Text(
          lang.purchaseReturnReport,
          style: theme.textTheme.titleLarge?.copyWith(
            color: Colors.white,
            fontSize: 20.0,
            fontWeight: FontWeight.w600,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0.0,
      ),
      body: Consumer(builder: (context, ref, __) {
        final theme = Theme.of(context);
        return RefreshIndicator.adaptive(
          onRefresh: () async => await Future.sync(() => pageController.refresh()),
          child: CustomScrollView(
            slivers: [
              SliverAppBar(
                automaticallyImplyLeading: false,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                backgroundColor: Colors.transparent,
                expandedHeight: 145.0,
                floating: false,
                pinned: false,
                flexibleSpace: FlexibleSpaceBar(
                  background: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                          children: [
                            ContainerWithLabel(
                              color: Color(0xffE1FFD8),
                              label: '$currency${totalReturn.toStringAsFixed(2)}',
                              description: lang.returnAmount,
                            ),
                            SizedBox(width: 8),
                            ContainerWithLabel(
                              color: Color(0xffFFE2E2),
                              label: totalQuantity.toString(),
                              description: lang.returnedQty,
                            ),
                          ],
                        ),
                        SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
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
                            SizedBox(width: 8),
                            IconButton(
                              style: IconButton.styleFrom(
                                backgroundColor: Colors.transparent,
                                side: BorderSide(color: kOutlineColor),
                                minimumSize: Size(48, 48),
                              ),
                              onPressed: () {
                                showModalBottomSheet(
                                  context: context,
                                  builder: (context) {
                                    return DateRangePicker(
                                      onDateRangeSelected: _onDateRangeSelected,
                                      controller: pageController,
                                      fromDate: _fromDate,
                                      toDate: _toDate,
                                      status: _status,
                                      showOnlyDatePicker: true,
                                    );
                                  },
                                );
                              },
                              icon: Icon(Icons.filter_alt_outlined),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Divider(
                  color: kOutlineBorder,
                  thickness: 1.0,
                ),
              ),
              SliverToBoxAdapter(
                child: PagedListView(
                  shrinkWrap: true,
                  padding: EdgeInsets.zero,
                  pagingController: pageController,
                  scrollController: _scrollController,
                  physics: const NeverScrollableScrollPhysics(),
                  builderDelegate: PagedChildBuilderDelegate<PurchaseReturnData>(
                    newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                    noItemsFoundIndicatorBuilder: (context) => Padding(
                      padding: const EdgeInsets.all(10.0),
                      child: Center(
                        child: Text(
                          lang.noDataFound,
                          style: theme.textTheme.bodyLarge,
                        ),
                      ),
                    ),
                    itemBuilder: (context, item, index) {
                      // total return amount
                      num getTotalReturn() {
                        num totalReturnAmount = 0;
                        if (item.details != null && item.details!.isNotEmpty) {
                          for (var detail in item.details!) {
                            totalReturnAmount += detail.returnAmount ?? 0;
                          }
                        }
                        return totalReturnAmount;
                      }

                      // total return qty
                      num getReturnQty() {
                        num totalReturnQty = 0;
                        if (item.details != null && item.details!.isNotEmpty) {
                          for (var detail in item.details!) {
                            totalReturnQty += detail.returnQty ?? 0;
                          }
                        }
                        return totalReturnQty;
                      }

                      return Padding(
                        padding: const EdgeInsets.only(bottom: 0),
                        child: InkWell(
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => PurchaseDetails(
                                id: item.purchaseId ?? 0,
                              ),
                            ),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 16.0),
                            child: Column(
                              children: [
                                Row(
                                  children: [
                                    RichText(
                                      text: TextSpan(
                                        text: '${lang.invoice}: ',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        children: [
                                          TextSpan(
                                            text: item.invoiceNo ?? 'N/A',
                                            style: theme.textTheme.bodyMedium?.copyWith(
                                              color: kTitleColor,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Spacer(),
                                    RichText(
                                      text: TextSpan(
                                        text: '${lang.total}: ',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        children: [
                                          TextSpan(
                                            text: '$currency${item.purchase?.totalAmount ?? '0.0'}',
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
                                        text: item.purchase?.party?.name.toString() ?? 'n/a',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                      ),
                                    ),
                                    Spacer(),
                                    RichText(
                                      text: TextSpan(
                                        text: '${lang.returns}: ',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        children: [
                                          TextSpan(
                                            // text: '$currency${item.details?[0].returnAmount ?? 'N/A'}',
                                            text: '$currency${getTotalReturn().toString()}',
                                            style: theme.textTheme.bodyMedium?.copyWith(
                                              color: kNutrals900,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                        ],
                                      ),
                                    )
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    RichText(
                                      text: TextSpan(
                                        text: item.returnDate != null
                                            ? '${lang.date}: ${DateFormat('dd/MM/yyyy').format(
                                                DateTime.parse(item.returnDate.toString()),
                                              )}'
                                            : '${lang.date}: N/A',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                      ),
                                    ),
                                    RichText(
                                      text: TextSpan(
                                        text: '${lang.returnedQty}: ',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        children: [
                                          TextSpan(
                                            text: getReturnQty().toString(),
                                            style: theme.textTheme.bodyMedium?.copyWith(
                                              color: kNutrals900,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          )
                                        ],
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
                      );
                    },
                  ),
                ),
              ),
            ],
          ),
        );
      }),
    );
  }
}
