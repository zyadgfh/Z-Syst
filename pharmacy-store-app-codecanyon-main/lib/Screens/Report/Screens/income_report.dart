import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/currency.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../components/report_components.dart';
import '../model/income_report_model.dart';
import '../repo/report_repo.dart';

class IncomeReport extends StatefulWidget {
  const IncomeReport({
    super.key,
  });

  @override
  State<IncomeReport> createState() => _IncomeReportState();
}

class _IncomeReportState extends State<IncomeReport> {
  num totalIncome = 0;

  // Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, IncomeReportData> pageController = PagingController(firstPageKey: 1);
  final ReportRepo incomes = ReportRepo();
  final TextEditingController _searchController = TextEditingController();

  // Fetch List
  Future<void> fetchPurchaseDataList(int pageKey) async {
    try {
      final list = await incomes.getIncomeReportList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        fromDate: _fromDate?.toString() ?? '',
        toDate: _toDate?.toString() ?? '',
      );

      if (list != null) {
        final newItems = list.data?.data ?? [];
        totalIncome = list.totalIncome ?? 0;

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
  String? _status;

  void _onDateRangeSelected(DateTime? fromDate, DateTime? toDate, String? status) {
    setState(() {
      _fromDate = fromDate;
      _toDate = toDate;
      _status = status;
    });
  }

  @override
  void initState() {
    super.initState();
    pageController.addPageRequestListener(fetchPurchaseDataList);
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    final theme = Theme.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        title: Text(
          lang.incomeReport,
          style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 20),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
        elevation: 0.0,
        actions: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: IconButton(
              padding: EdgeInsets.all(4),
              style: IconButton.styleFrom(
                  iconSize: 16,
                  backgroundColor: Colors.white.withValues(alpha: 0.13),
                  side: BorderSide(color: Colors.white.withValues(alpha: 0.22)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                  minimumSize: Size(24, 24)),
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
          ),
        ],
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(color: kMainColorBg),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(lang.total, style: theme.textTheme.titleSmall),
            Text('$currency ${totalIncome.toStringAsFixed(2)}', style: theme.textTheme.titleSmall),
          ],
        ),
      ),
      body: Column(
        children: [
          // Header Row
          _buildHeaderRow(theme),

          // Scrollable Data Table
          Expanded(
            child: PagedListView<int, IncomeReportData>(
              shrinkWrap: true,
              padding: EdgeInsets.zero,
              pagingController: pageController,
              scrollController: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              builderDelegate: PagedChildBuilderDelegate<IncomeReportData>(
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
  }

  // --- Data not found
  Widget _buildNoDataFoundIndicator(ThemeData theme) {
    return Padding(
      padding: EdgeInsets.all(20.0),
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
            _buildTableCell(
              l.S.of(context).incomeFrom,
              theme.textTheme.titleMedium,
            ),
            _buildTableCell(l.S.of(context).category, theme.textTheme.titleMedium, textAlign: TextAlign.start),
            _buildTableCell(l.S.of(context).amount, theme.textTheme.titleMedium, textAlign: TextAlign.end),
          ],
        ),
      ],
    );
  }

  //---Table data
  Widget _buildDataRow(IncomeReportData item, ThemeData theme) {
    return Table(
      border: TableBorder.symmetric(
        inside: BorderSide.none,
        outside: BorderSide.none,
      ),
      columnWidths: {
        0: FlexColumnWidth(4),
        1: FlexColumnWidth(3),
        2: FlexColumnWidth(3),
      },
      textBaseline: TextBaseline.alphabetic,
      children: [
        TableRow(
          children: [
            _buildTableCell(
              '${item.incomeFor ?? 'n/a'}\n${item.incomeDate != null ? DateFormat('d MMM yyyy').format(DateTime.parse(item.incomeDate.toString())) : 'n/a'}',
              theme.textTheme.bodyMedium,
            ),
            _buildTableCell(
              item.category?.categoryName ?? 'n/a',
              theme.textTheme.bodyMedium,
              textAlign: TextAlign.start,
            ),
            _buildTableCell(
              '$currency${item.amount ?? 'n/a'}',
              theme.textTheme.bodyMedium,
              textAlign: TextAlign.end,
            ),
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
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
    );
  }
}
