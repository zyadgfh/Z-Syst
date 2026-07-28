import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:mobile_pos/Screens/Loss_Profit/repo/loss_profit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../../constant.dart';
import '../../../currency.dart';
import '../../Loss_Profit/model/loss_profit_model.dart';
import '../../Loss_Profit/single_loss_profit_screen.dart';

class LossProfitReportScreen extends StatefulWidget {
  const LossProfitReportScreen({
    super.key,
  });

  @override
  State<LossProfitReportScreen> createState() => _LossProfitReportScreenState();
}

class _LossProfitReportScreenState extends State<LossProfitReportScreen> {
  num totalLoss = 0;
  num totalProfit = 0;

  // Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, LossProfitData> pageController = PagingController(firstPageKey: 1);
  final LossProfitRepo lossProfit = LossProfitRepo();
  final TextEditingController _searchController = TextEditingController();

  // Fetch List
  Future<void> fetchPurchaseDataList(int pageKey) async {
    try {
      final list = await lossProfit.getPurchaseReportList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
      );

      if (list != null) {
        final newItems = list.data?.data ?? [];
        totalProfit = list.totalProfit ?? 0;
        totalLoss = list.totalLoss ?? 0;

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

  @override
  void initState() {
    super.initState();
    pageController.addPageRequestListener(fetchPurchaseDataList);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        title: Text(
          lang.lossOrProfitReport,
          style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 20),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
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
                Text(lang.totalLoss, style: theme.textTheme.titleSmall),
                Text('$currency${totalLoss.abs().toStringAsFixed(2)}', style: theme.textTheme.titleSmall),
              ],
            ),
            SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(lang.totalProfit, style: theme.textTheme.titleSmall),
                Text('$currency${totalProfit.toStringAsFixed(2)}', style: theme.textTheme.titleSmall),
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
            child: PagedListView<int, LossProfitData>(
              shrinkWrap: true,
              padding: EdgeInsets.zero,
              pagingController: pageController,
              scrollController: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              builderDelegate: PagedChildBuilderDelegate<LossProfitData>(
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
        // Define fixed column widths if needed
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
            _buildTableCell(l.S.of(context).invoice, theme.textTheme.titleMedium?.copyWith(fontSize: 14)),
            _buildTableCell(l.S.of(context).due, theme.textTheme.titleMedium?.copyWith(fontSize: 14), textAlign: TextAlign.center),
            _buildTableCell(l.S.of(context).profit, theme.textTheme.titleMedium?.copyWith(fontSize: 14), textAlign: TextAlign.end),
            _buildTableCell(l.S.of(context).lossTitle , theme.textTheme.titleMedium?.copyWith(fontSize: 14), textAlign: TextAlign.end),
          ],
        ),
      ],
    );
  }

  //---Table data
  Widget _buildDataRow(LossProfitData item, ThemeData theme) {
    return Table(
      border: TableBorder.symmetric(
        inside: BorderSide.none,
        outside: BorderSide.none,
      ),
      columnWidths: {
        // Define fixed column widths if needed
      },
      textBaseline: TextBaseline.alphabetic,
      children: [
        TableRow(
          children: [
            InkWell(
                onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => SingleLossProfitScreen(
                          id: item.id ?? 0,
                        ),
                      ),
                    ),
                child: _buildTableCell(item.invoiceNumber ?? '', theme.textTheme.bodyMedium)),
            _buildTableCell('$currency${item.dueAmount}', theme.textTheme.bodyMedium, textAlign: TextAlign.center),
            _buildTableCell(
              '$currency${(item.lossProfit! > 0 ? '${item.lossProfit?.abs().toStringAsFixed(2)}' : 0).toString()}',
              theme.textTheme.bodyMedium,
              textAlign: TextAlign.end,
            ),
            _buildTableCell(
              '$currency${(item.lossProfit! < 0 ? '${item.lossProfit?.abs().toStringAsFixed(2)}' : 0).toString()}',
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
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 16),
      child: Text(
        text,
        style: style,
        textAlign: textAlign,
      ),
    );
  }
}
