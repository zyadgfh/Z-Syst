import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:iconly/iconly.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/PDF%20Invoice/sales_pdf/sales_pdf.dart';
import 'package:mobile_pos/Provider/profile_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../../constant.dart';
import '../../../currency.dart';
import '../../Sales List/sale_details.dart';
import '../../stock_list/stock_list.dart';
import '../components/report_components.dart';
import '../model/sales_report_model.dart';
import '../repo/report_repo.dart';

class SalesReportScreen extends StatefulWidget {
  const SalesReportScreen({super.key});

  @override
  // ignore: library_private_types_in_public_api
  _PurchaseReportState createState() => _PurchaseReportState();
}

class _PurchaseReportState extends State<SalesReportScreen> {
  num totalPaid = 0;
  num totalDue = 0;

  //---Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, SaleData> pageController = PagingController(firstPageKey: 1);

  // SaleReport List Repo
  ReportRepo purchases = ReportRepo();
  final TextEditingController _searchController = TextEditingController();
  String? productCode;

  // ---Fetch Sale  List
  Future<void> fetchSalesDataList(int pageKey) async {
    SaleReportModel? list;
    try {
      list = await purchases.getSalesReportList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        fromDate: _fromDate?.toString() ?? '',
        toDate: _toDate?.toString() ?? '',
        status: _status?.toLowerCase() ?? '',
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
    final lang = l.S.of(context);
    return Consumer(builder: (context, ref, __) {
      final personalData = ref.watch(businessInfoProvider);
      return RefreshIndicator.adaptive(
        onRefresh: () async => await Future.sync(() => pageController.refresh()),
        child: AcnooScafoldWidget(
          appBar: AppBar(
            title: Text(
              lang.salesReport,
              style: theme.textTheme.titleLarge?.copyWith(color: Colors.white, fontSize: 20.0, fontWeight: FontWeight.w600),
            ),
            iconTheme: const IconThemeData(color: Colors.white),
            centerTitle: true,
            backgroundColor: Colors.transparent,
            elevation: 0.0,
            actions: [
              personalData.when(data: (business) {
                return GlobalIconButton(
                  onPressed: () {
                    if (pageController.itemList!.isNotEmpty) {
                      GenerateSalesPdf().generateSaleReportPdf(context, pageController.itemList, business, _fromDate, _toDate);
                    } else {
                      EasyLoading.showInfo(lang.noDataAvailableForGeneratePdf);
                    }
                  },
                  icon: pdfIcon,
                );
              }, error: (e, stack) {
                return Text(e.toString());
              }, loading: () {
                return Center(
                  child: CircularProgressIndicator(),
                );
              }),
              SizedBox(width: 16),
            ],
          ),
          body: CustomScrollView(
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
                              color: kMainColor100,
                              label: '$currency${totalPaid.toStringAsFixed(2)}',
                              description: lang.totalPaid,
                            ),
                            SizedBox(width: 8),
                            ContainerWithLabel(
                              color: kRed100,
                              label: '$currency${totalDue.toStringAsFixed(2)}',
                              description: lang.totalDue,
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
                                      showOnlyDatePicker: false,
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
                  builderDelegate: PagedChildBuilderDelegate<SaleData>(
                    newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                    noItemsFoundIndicatorBuilder: (context) => Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Center(
                        child: Text(
                          l.S.of(context).noDataFound,
                          style: theme.textTheme.bodyLarge,
                        ),
                      ),
                    ),
                    itemBuilder: (context, item, index) => Padding(
                      padding: const EdgeInsets.only(bottom: 0),
                      child: InkWell(
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => SalesDetailsScreen(id: item.id ?? 0),
                          ),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 24.0),
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
                                            text: item.invoiceNumber ?? 'N/A',
                                            style: theme.textTheme.bodyMedium?.copyWith(
                                              color: kTitleColor,
                                              fontWeight: FontWeight.w500,
                                            ))
                                      ],
                                    ),
                                  ),
                                  Visibility(
                                    visible: (item.saleReturnCounter ?? 0) > 0,
                                    child: Padding(
                                      padding: const EdgeInsets.only(left: 7),
                                      child: Icon(
                                        IconlyLight.arrow_left_square,
                                        size: 16,
                                        color: kMainColor,
                                      ),
                                    ),
                                  ),
                                  Spacer(),
                                  RichText(
                                    text: TextSpan(
                                      text: '${lang.total}: ',
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
                                      text: item.party?.phone ?? lang.guest,
                                      style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    ),
                                  ),
                                  Spacer(),
                                  RichText(
                                    text: TextSpan(
                                      text: '${lang.paid}: ',
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
                                          ? '${lang.date}: ${DateFormat('dd/MM/yyyy, h:mm a').format(
                                              DateTime.parse(item.saleDate.toString()),
                                            )}'
                                          : '${lang.date}: N/A',
                                      style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    ),
                                  ),
                                  RichText(
                                    text: TextSpan(
                                      text: '${lang.due}: ',
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
