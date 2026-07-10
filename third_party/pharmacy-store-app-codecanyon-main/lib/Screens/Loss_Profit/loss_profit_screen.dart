import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Loss_Profit/repo/loss_profit_repo.dart';
import 'package:mobile_pos/Screens/Loss_Profit/single_loss_profit_screen.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../../constant.dart';
import '../../currency.dart';
import '../Report/components/report_components.dart';
import '../Sales List/sale_details.dart';
import '../stock_list/stock_list.dart';
import '../widget/empty_widgets.dart';
import 'model/loss_profit_model.dart';

class LossProfitScreen extends StatefulWidget {
  const LossProfitScreen({
    super.key,
  });

  @override
  State<LossProfitScreen> createState() => _LossProfitScreenState();
}

class _LossProfitScreenState extends State<LossProfitScreen> {
  num totalLoss = 0;
  num totalProfit = 0;

  // Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, LossProfitData> pageController = PagingController(firstPageKey: 1);
  final LossProfitRepo purchases = LossProfitRepo();
  final TextEditingController _searchController = TextEditingController();

  // Fetch List
  Future<void> fetchPurchaseDataList(int pageKey) async {
    try {
      final list = await purchases.getPurchaseReportList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        fromDate: _fromDate?.toString() ?? '',
        toDate: _toDate?.toString() ?? '',
        status: _status?.toLowerCase() ?? '',
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
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        title: Text(
          lang.lossProfitReport,
          style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 20),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
        elevation: 0.0,
      ),
      body: RefreshIndicator.adaptive(
        onRefresh: () async => await Future.sync(() => pageController.refresh()),
        child: CustomScrollView(
          slivers: [
            SliverAppBar(
              automaticallyImplyLeading: false,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
              backgroundColor: Colors.transparent,
              expandedHeight: 160.0,
              floating: false,
              pinned: false,
              flexibleSpace: FlexibleSpaceBar(
                background: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                  child: Column(
                    children: [
                      Container(
                        padding: EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: kMainColor),
                          color: Color(0xffE7F7EF),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: [
                            _buildProfitLossColumn(
                              value: "$currency ${totalProfit.toStringAsFixed(2)}",
                              label: lang.profit,
                              theme: theme,
                            ),
                            Container(
                              height: 45,
                              width: 1,
                              decoration: BoxDecoration(
                                color: kMainColor,
                              ),
                            ),
                            _buildProfitLossColumn(
                              value: "$currency ${totalLoss.abs().toStringAsFixed(2)}",
                              label: lang.lossTitle,
                              theme: theme,
                            )
                          ],
                        ),
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
                builderDelegate: PagedChildBuilderDelegate<LossProfitData>(
                  newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                  noItemsFoundIndicatorBuilder: (context) => Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Center(
                      child: EmptyListWidget(
                        title: lang.noLossOrProfitFound,
                      ),
                    ),
                  ),
                  itemBuilder: (context, item, index) => Padding(
                    padding: const EdgeInsets.only(bottom: 0),
                    child: InkWell(
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => SingleLossProfitScreen(
                            id: item.id ?? 0,
                          ),
                        ),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                Flexible(
                                  child: Text(
                                    item.party?.name ?? (item.party?.phone ?? lang.guest),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600, fontSize: 16, color: kMainColor),
                                  ),
                                ),
                                SizedBox(width: 10),
                                PopupMenuButton<String>(
                                  iconColor: kNutral700,
                                  padding: EdgeInsets.zero,
                                  onSelected: (String value) {
                                    // Use a lambda function here
                                    switch (value) {
                                      case 'view':
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (context) => SalesDetailsScreen(id: item.id ?? 0),
                                          ),
                                        );
                                        break;
                                      default:
                                        print('Unknown action');
                                    }
                                  },
                                  itemBuilder: (BuildContext context) => [
                                    PopupMenuItem<String>(
                                      value: 'view',
                                      child: Text('View invoice'),
                                    ),
                                  ],
                                )
                              ],
                            ),
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
                                Spacer(),
                                RichText(
                                  text: TextSpan(
                                    text: ('${lang.total}: ').toString(),
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    children: [
                                      TextSpan(
                                        text: '$currency${item.totalAmount ?? 'n/a'}',
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
                                    text: item.party?.phone.toString() ?? 'N/A',
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                  ),
                                ),
                                Spacer(),
                                RichText(
                                  text: TextSpan(
                                    text: (item.lossProfit! > 0 ? '${lang.profit}: ' : '${lang.loss}: ').toString(),
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                    children: [
                                      TextSpan(
                                        text: '$currency${item.lossProfit?.abs().toStringAsFixed(2) ?? 'N/A'}',
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
                                        text: ('$currency${(item.dueAmount??0) > 0 ? (item.dueAmount??0).toStringAsFixed(2) : 0}').toString(),
                                        style: theme.textTheme.bodyMedium?.copyWith(
                                          color: (item.dueAmount != null && (item.dueAmount??0) > 0) ? Color(0xffFF8C34) : kNutrals900,
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
  }

  Widget _buildProfitLossColumn({
    required String value,
    required String label,
    required ThemeData theme,
  }) {
    return Expanded(
      // Added Expanded
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis, // Added overflow
            style: theme.textTheme.titleLarge?.copyWith(
              color: kTitleColor,
              fontWeight: FontWeight.w600,
              fontSize: 16,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            maxLines: 1,
            textAlign: TextAlign.center,
            overflow: TextOverflow.ellipsis, // Added overflow
            style: theme.textTheme.bodySmall?.copyWith(
              color: const Color(0xff585865),
              fontWeight: FontWeight.w400,
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }
}
