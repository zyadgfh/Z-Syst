import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/currency.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

import '../Purchase List/purchase_details.dart';
import '../Report/components/report_components.dart';
import '../Report/model/PurchaseReportModel.dart';
import '../Report/model/sales_report_model.dart';
import '../Report/repo/report_repo.dart';
import '../Sales List/sale_details.dart';
import '../Customers/Model/parties_model.dart';

class ViewLedgerScreen extends StatefulWidget {
  const ViewLedgerScreen({super.key, required this.id, required this.party});
  final num id;
  final PartyModel party;
  @override
  State<ViewLedgerScreen> createState() => _ViewLedgerScreenState();
}

class _ViewLedgerScreenState extends State<ViewLedgerScreen> {
  num totalPaid = 0;
  num totalDue = 0;

  // ---Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, Datas> purchasePageController = PagingController(firstPageKey: 1);
  final PagingController<int, SaleData> pageController = PagingController(firstPageKey: 1);

  // Purchase List Repo
  ReportRepo purchases = ReportRepo();
  final TextEditingController _searchController = TextEditingController();
  String? productCode;

  // ---Fetch Purchase List
  Future<void> fetchPurchaseDataList(int pageKey) async {
    PurchaseReportModel? list;
    try {
      list = await purchases.getPurchaseReportList(
          search: _searchController.text,
          nextPage: pageKey.toString() ?? '',
          fromDate: _fromDate?.toString() ?? '',
          toDate: _toDate?.toString() ?? '',
          status: _status?.toLowerCase() ?? '',
          id: widget.id);
      if (list != null) {
        final newItems = list.data?.data ?? [];
        totalDue = list.totalDue ?? 0;
        totalPaid = list.totalPaid ?? 0;
        final isLastPage = list.data?.lastPage == list.data?.currentPage;

        if (isLastPage) {
          purchasePageController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          purchasePageController.appendPage(newItems, nextPageKey);
        }
      }
    } catch (error) {
      purchasePageController.error = error;
    }
    setState(() {});
  }

  // ---Fetch Sales List
  Future<void> fetchSalesDataList(int pageKey) async {
    SaleReportModel? list;
    try {
      list = await purchases.getSalesReportList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        fromDate: _fromDate?.toString() ?? '',
        toDate: _toDate?.toString() ?? '',
        status: _status?.toLowerCase() ?? '',
        id: widget.id,
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
        totalDue = list.totalDue ?? 0;
        totalPaid = list.totalPaid ?? 0;
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
    purchasePageController.addPageRequestListener((pageKey) => fetchPurchaseDataList(pageKey));
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
    final theme = Theme.of(context);
    final language = lang.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        iconTheme: IconThemeData(color: Colors.white),
        title: ListTile(
          contentPadding: EdgeInsets.zero,
          visualDensity: VisualDensity(horizontal: -4, vertical: -4),
          leading: Container(
            alignment: Alignment.center,
            height: 40,
            width: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: kWhite,
            ),
            child: Text(
              (widget.party.name?.length ?? 0) >= 2 ? widget.party.name!.substring(0, 2) : widget.party.name ?? 'n/a',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.party.name ?? 'n/a',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500, color: kWhite),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              Text(
                widget.party.type ?? 'n/a',
                style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w400, color: kWhite),
              ),
            ],
          ),
        ),
        titleSpacing: 0,
        // actions: [
        //   IconButton(
        //     visualDensity: VisualDensity(horizontal: -4, vertical: -4),
        //     padding: EdgeInsets.zero,
        //     onPressed: () {},
        //     icon: Container(
        //         padding: EdgeInsets.all(8),
        //         decoration: BoxDecoration(color: kWhite.withOpacity(0.15), shape: BoxShape.circle),
        //         child: Icon(
        //           Icons.print,
        //           color: kWhite,
        //           size: 22,
        //         )),
        //   ),
        //   SizedBox(
        //     width: 10,
        //   ),
        //   IconButton(
        //     padding: EdgeInsets.only(right: 10),
        //     onPressed: () {},
        //     icon: Container(
        //         padding: EdgeInsets.all(8),
        //         decoration: BoxDecoration(color: kWhite.withOpacity(0.15), shape: BoxShape.circle),
        //         child: Icon(
        //           FeatherIcons.bell,
        //           color: kWhite,
        //           size: 20,
        //         )),
        //   )
        // ],
      ),
      body: Padding(
        padding: const EdgeInsets.only(top: 5),
        child: SingleChildScrollView(
          child: Padding(
            padding: EdgeInsets.only(left: 16, right: 16, bottom: 10, top: 15),
            child: RefreshIndicator.adaptive(
              onRefresh: () async {
                await Future.sync(() => pageController.refresh());
                await Future.sync(() => purchasePageController.refresh());
              },
              child: Column(
                children: [
                  Container(
                    height: 95,
                    width: double.infinity,
                    decoration: BoxDecoration(
                        color: Color(0xffE7F7EF),
                        // border: Border.all(width: 1, color: kMainColor),
                        borderRadius: const BorderRadius.all(Radius.circular(10))),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Text(
                              '$currency ${totalPaid.toStringAsFixed(2)}',
                              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500, fontSize: 18),
                            ),
                            // const SizedBox(height: ),
                            Text(
                              language.totalPaid,
                              style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w500, color: kNutral700),
                            ),
                          ],
                        ),
                        Container(
                          width: 1,
                          height: 60,
                          color: kNutral700,
                        ),
                        Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Text(
                              '$currency ${totalDue.toStringAsFixed(2)}',
                              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500, fontSize: 18),
                            ),
                            // const SizedBox(height: ),
                            Text(
                              language.totalDue,
                              style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w500, color: kNutral700),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  SizedBox(
                    height: 16,
                  ),
                  // Search and filter
                  Row(
                    children: [
                      Expanded(
                        child: SizedBox(
                          height: 48,
                          child: TextFormField(
                            controller: _searchController,
                            onChanged: (value) {
                              if (value.isEmpty) {
                                purchasePageController.refresh();
                                pageController.refresh();
                              }
                            },
                            onFieldSubmitted: (value) async {
                              if (_searchController.text.isNotEmpty) {
                                _searchController.text = value;
                                purchasePageController.refresh();
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
                                controller: purchasePageController,
                                fromDate: _fromDate,
                                toDate: _toDate,
                                status: _status,
                                showOnlyDatePicker: false,
                              );
                            },
                          );
                        },
                        icon: Icon(Icons.filter_alt_outlined),
                      ),
                    ],
                  ),

                  widget.party.type != 'Supplier'
                      ? PagedListView(
                          shrinkWrap: true,
                          padding: EdgeInsets.only(top: 24),
                          pagingController: pageController,
                          scrollController: _scrollController,
                          physics: AlwaysScrollableScrollPhysics(),
                          builderDelegate: PagedChildBuilderDelegate<SaleData>(
                            newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                            noItemsFoundIndicatorBuilder: (context) => Padding(
                              padding: const EdgeInsets.all(20.0),
                              child: Center(
                                child: Text(
                                  lang.S.of(context).noDataFound,
                                  style: theme.textTheme.bodyLarge,
                                ),
                              ),
                            ),
                            itemBuilder: (context, item, index) => InkWell(
                              onTap: () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) => SalesDetailsScreen(id: item.id ?? 0),
                                ),
                              ),
                              child: Column(
                                children: [
                                  Row(
                                    children: [
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.invoice}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                                text: item.invoiceNumber ?? 'N/A',
                                                style: theme.textTheme.bodyMedium?.copyWith(
                                                  color: kTitleColor,
                                                  fontWeight: FontWeight.w500,
                                                ))
                                          ],
                                        ),
                                      ),
                                      Spacer(),
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.total}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.totalAmount ?? 'N/A'}',
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
                                          text: '${item.party?.phone.toString()}',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        ),
                                      ),
                                      Spacer(),
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.paid}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.paidAmount ?? 'N/A'}',
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
                                          text: item.saleDate != null
                                              ? '${language.date}: ${DateFormat('dd/MM/yyyy, h:mm a').format(
                                                  DateTime.parse(item.saleDate.toString()),
                                                )}'
                                              : '${language.date}: N/A',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        ),
                                      ),
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.due}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.dueAmount ?? 'N/A'}',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: (item.dueAmount != null && item.dueAmount! > 0) ? Color(0xffFF8C34) : kNutrals900,
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
                        )
                      : PagedListView(
                          shrinkWrap: true,
                          padding: EdgeInsets.only(top: 24),
                          pagingController: purchasePageController,
                          scrollController: _scrollController,
                          physics: const NeverScrollableScrollPhysics(),
                          builderDelegate: PagedChildBuilderDelegate<Datas>(
                            newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                            noItemsFoundIndicatorBuilder: (context) => Padding(
                              padding: const EdgeInsets.all(20.0),
                              child: Center(
                                child: Text(
                                  lang.S.of(context).noDataFound,
                                  style: theme.textTheme.bodyLarge,
                                ),
                              ),
                            ),
                            itemBuilder: (context, item, index) => InkWell(
                              onTap: () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) => PurchaseDetails(id: item.id ?? 0),
                                ),
                              ),
                              child: Column(
                                children: [
                                  Row(
                                    children: [
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.invoice}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                                text: item.invoiceNumber ?? 'N/A',
                                                style: theme.textTheme.bodyMedium?.copyWith(
                                                  color: kTitleColor,
                                                  fontWeight: FontWeight.w500,
                                                ))
                                          ],
                                        ),
                                      ),
                                      Spacer(),
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.total}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.totalAmount ?? 'N/A'}',
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
                                          text: '${item.party?.phone.toString()}',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        ),
                                      ),
                                      Spacer(),
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.paid}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.paidAmount ?? 'N/A'}',
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
                                          text: item.purchaseDate != null
                                              ? '${language.date}: ${DateFormat('dd/MM/yyyy, h:mm a').format(
                                                  DateTime.parse(item.purchaseDate.toString()),
                                                )}'
                                              : '${language.date}: N/A',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        ),
                                      ),
                                      RichText(
                                        text: TextSpan(
                                          text: '${language.due}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.dueAmount ?? 'N/A'}',
                                              style: theme.textTheme.bodyMedium?.copyWith(
                                                color: (item.dueAmount != null && item.dueAmount! > 0) ? Color(0xffFF8C34) : kNutrals900,
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
                        )
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
