import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/PDF%20Invoice/due_pdf/due_pdf.dart';
import 'package:mobile_pos/Provider/profile_provider.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../../constant.dart';
import '../../../currency.dart';
import '../components/report_components.dart';
import '../model/due_report_model.dart';
import '../repo/report_repo.dart';

class DueReportScreen extends StatefulWidget {
  const DueReportScreen({super.key});

  @override
  State<DueReportScreen> createState() => _DueReportScreenState();
}

class _DueReportScreenState extends State<DueReportScreen> {
  num totalDue = 0;
  num totalPaid = 0;

  // Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, DueReportData> pageController = PagingController(firstPageKey: 1);
  final ReportRepo lossProfit = ReportRepo();
  final TextEditingController _searchController = TextEditingController();

  // Fetch List
  Future<void> fetchPurchaseDataList(int pageKey) async {
    try {
      final list = await lossProfit.getDueReportList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        fromDate: _fromDate?.toString() ?? '',
        toDate: _toDate?.toString() ?? '',
      );

      if (list != null) {
        final newItems = list.data?.data ?? [];
        totalPaid = list.totalPaid ?? 0;
        totalDue = list.totalDue ?? 0;

        final isLastPage = list.data?.lastPage == list.data?.currentPage;
        if (isLastPage) {
          pageController.appendLastPage(newItems);
        } else {
          pageController.appendPage(newItems, pageKey + 1);
        }
      }
    } catch (error) {
      pageController.error = error;
    }
    setState(() {});
  }

  // -------- Filter
  DateTime? _fromDate;
  DateTime? _toDate;

  void _onDateRangeSelected(DateTime? fromDate, DateTime? toDate, String? status) {
    setState(() {
      _fromDate = fromDate;
      _toDate = toDate;
    });
  }

  @override
  void initState() {
    super.initState();
    pageController.addPageRequestListener(fetchPurchaseDataList);
  }

  bool isSearchVisible = false;

  void _onSearchFieldToggle() {
    setState(() {
      isSearchVisible = !isSearchVisible;
      if (!isSearchVisible) {
        _searchController.clear();
        pageController.refresh();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    final theme = Theme.of(context);
    return Consumer(builder: (context, ref, __) {
      final personalData = ref.watch(businessInfoProvider);
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: isSearchVisible
              ? SizedBox(
                  height: 40,
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
                      filled: true,
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
                )
              : Text(
                  lang.dueReport,
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 20,
                  ),
                ),
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: 16.0),
              child: Row(
                children: [
                  if (!isSearchVisible) // Only show the search and filter icons when search is not visible
                    IconButton(
                      padding: EdgeInsets.zero,
                      visualDensity: VisualDensity(horizontal: -2),
                      style: IconButton.styleFrom(iconSize: 18, minimumSize: Size(24, 24)),
                      onPressed: _onSearchFieldToggle, // Toggle the search field visibility
                      icon: Icon(IconlyLight.search),
                    ),
                  if (!isSearchVisible)
                    IconButton(
                      visualDensity: VisualDensity(horizontal: -2),
                      padding: EdgeInsets.zero,
                      style: IconButton.styleFrom(iconSize: 18, minimumSize: Size(24, 24)),
                      onPressed: () {
                        showModalBottomSheet(
                          context: context,
                          builder: (context) {
                            return DateRangePicker(
                              onDateRangeSelected: _onDateRangeSelected,
                              controller: pageController,
                              fromDate: _fromDate,
                              toDate: _toDate,
                              showOnlyDatePicker: true,
                            );
                          },
                        );
                      },
                      icon: Icon(IconlyLight.filter_2),
                    ),
                  if (isSearchVisible)
                    CloseButton(
                      color: Colors.red,
                      onPressed: _onSearchFieldToggle,
                      style: IconButton.styleFrom(
                        iconSize: 18,
                        minimumSize: Size(24, 24),
                      ),
                    ),
                  if (!isSearchVisible)
                    personalData.when(data: (business) {
                      return IconButton(
                        visualDensity: VisualDensity(horizontal: -2),
                        padding: EdgeInsets.zero,
                        style: IconButton.styleFrom(iconSize: 18, minimumSize: Size(24, 24)),
                        onPressed: () {
                          if (pageController.itemList!.isNotEmpty) {
                            GenerateDuePdf().generateDueReportPdf(context, pageController.itemList, business, _fromDate, _toDate);
                          } else {
                            EasyLoading.showInfo(lang.noDataAvailableForGeneratePdf);
                          }
                        },
                        icon: Icon(Icons.picture_as_pdf_outlined),
                      );
                    }, error: (e, stack) {
                      return Text(e.toString());
                    }, loading: () {
                      return Center(
                        child: CircularProgressIndicator(),
                      );
                    })
                ],
              ),
            ),
          ],
          iconTheme: const IconThemeData(color: Colors.white),
          centerTitle: isSearchVisible ? false : true,
          titleSpacing: 0,
          elevation: 0.0,
        ),
        bottomNavigationBar: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(color: kMainColorBg),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(lang.totalDue, style: theme.textTheme.titleSmall),
                  Text('$currency${totalDue.toStringAsFixed(2)}', style: theme.textTheme.titleSmall),
                ],
              ),
              SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(lang.totalPaid, style: theme.textTheme.titleSmall),
                  Text('$currency${totalPaid.toStringAsFixed(2) ?? ''}', style: theme.textTheme.titleSmall),
                ],
              ),
            ],
          ),
        ),
        body: Column(
          children: [
            // Header Row
            _buildHeaderRow(theme),

            // Scrollable Data Table
            Expanded(
              child: PagedListView<int, DueReportData>(
                shrinkWrap: true,
                padding: EdgeInsets.zero,
                pagingController: pageController,
                scrollController: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                builderDelegate: PagedChildBuilderDelegate<DueReportData>(
                  newPageProgressIndicatorBuilder: (context) => Center(
                    child: const CircularProgressIndicator(color: kMainColor),
                  ),
                  noItemsFoundIndicatorBuilder: (context) => _buildNoDataFoundIndicator(theme),
                  itemBuilder: (context, item, index) => _buildDataRow(item, theme),
                ),
              ),
            ),
          ],
        ),
      );
    });
  }

  // --- Data not found
  Widget _buildNoDataFoundIndicator(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.all(20.0),
      child: Center(
        child: Text(
          l.S.of(context).noDataFound,
          style: theme.textTheme.bodyLarge,
        ),
      ),
    );
  }

  // ---Table Header
  Widget _buildHeaderRow(ThemeData theme) {
    return Table(
      border: TableBorder.symmetric(
        inside: BorderSide.none,
        outside: BorderSide.none,
      ),
      columnWidths: {
        0: FlexColumnWidth(4),
        1: FlexColumnWidth(3),
        2: FlexColumnWidth(3),
        3: FlexColumnWidth(3),
      },
      textBaseline: TextBaseline.alphabetic,
      children: [
        TableRow(
          decoration: BoxDecoration(
            color: kMainColorBg,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(35),
              topRight: Radius.circular(35),
            ),
          ),
          children: [
            _buildTableCell(l.S.of(context).invoice, theme.textTheme.titleMedium),
            _buildTableCell(l.S.of(context).total, theme.textTheme.titleMedium, textAlign: TextAlign.center),
            _buildTableCell(l.S.of(context).paid, theme.textTheme.titleMedium, textAlign: TextAlign.center),
            _buildTableCell(l.S.of(context).due, theme.textTheme.titleMedium, textAlign: TextAlign.end),
          ],
        ),
      ],
    );
  }

  //---Table data
  Widget _buildDataRow(DueReportData item, ThemeData theme) {
    return Table(
      border: TableBorder.symmetric(
        inside: BorderSide.none,
        outside: BorderSide.none,
      ),
      columnWidths: {
        0: FlexColumnWidth(4),
        1: FlexColumnWidth(3),
        2: FlexColumnWidth(3),
        3: FlexColumnWidth(3),
      },
      textBaseline: TextBaseline.alphabetic,
      children: [
        TableRow(
          children: [
            _buildTableCell(
              '${item.invoiceNumber ?? 'n/a'}\n${item.paymentDate != null ? DateFormat('d MMM yyyy').format(
                  DateTime.parse(
                    item.paymentDate.toString(),
                  ),
                ) : 'n/a'}',
              theme.textTheme.bodyMedium,
              textAlign: TextAlign.start,
            ),
            _buildTableCell('$currency${item.totalDue}', theme.textTheme.bodyMedium, textAlign: TextAlign.center),
            _buildTableCell('$currency${item.payDueAmount}', theme.textTheme.bodyMedium, textAlign: TextAlign.center),
            _buildTableCell('$currency${item.dueAmountAfterPay}', theme.textTheme.bodyMedium, textAlign: TextAlign.end),
          ],
        )
      ],
    );
  }

  // ---Table data design
  Widget _buildTableCell(String text, TextStyle? style, {TextAlign textAlign = TextAlign.start}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 10),
      child: Text(
        text,
        style: style,
        textAlign: textAlign,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      ),
    );
  }
}
