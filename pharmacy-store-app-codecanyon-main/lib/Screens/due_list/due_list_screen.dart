import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Customers/Model/parties_model.dart';
import 'package:mobile_pos/Screens/due_list/Model/due_list_model.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:nb_utils/nb_utils.dart';

import '../../constant.dart';
import '../../currency.dart';
import '../stock_list/stock_list.dart';
import '../widget/empty_widgets.dart';
import 'Model/collected_due_list_model.dart';
import 'Repo/due_repo.dart';
import 'collected_due_details.dart';
import 'due_collection_screen.dart';

class DueListScreen extends StatefulWidget {
  const DueListScreen({super.key});

  @override
  State<DueListScreen> createState() => _DueListScreenState();
}

class _DueListScreenState extends State<DueListScreen> with SingleTickerProviderStateMixin {
  Color? color;

  num calculateTotalDue(List<PartyModel> dueCustomerList) {
    num totalDue = 0;
    for (var customer in dueCustomerList) {
      if (customer.type != 'Supplier') {
        totalDue += customer.due!;
      }
    }
    return totalDue;
  }

  num totalReceivable = 0;
  num totalPayable = 0;

  // ---Controllers
  late TabController _tabController;
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, DueListData> allDuesController = PagingController(firstPageKey: 1);
  final PagingController<int, CollectedDueData> collectedPageController = PagingController(firstPageKey: 1);

  // Purchase List Repo
  DueRepo purchases = DueRepo();
  final TextEditingController _searchController = TextEditingController();
  String? productCode;

  // ---Fetch Due List
  Future<void> fetchDueDataList(int pageKey) async {
    DueListModel? list;
    try {
      list = await purchases.getDueList(
        search: _searchController.text ?? '',
        nextPage: pageKey.toString() ?? '',
        status: _status != 'All' ? _status?.toLowerCase() ?? '' : '',
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
        totalPayable = list.totalPayable ?? 0;
        totalReceivable = list.totalReceivable ?? 0;
        final isLastPage = list.data?.lastPage == list.data?.currentPage;

        if (isLastPage) {
          allDuesController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          allDuesController.appendPage(newItems, nextPageKey);
        }
      }
    } catch (error) {
      allDuesController.error = error;
    }
    setState(() {});
  }

  // ---Fetch Collected Due List
  Future<void> fetchCollectedDueList(int pageKey) async {
    CollectedDueListModel? list;
    try {
      list = await purchases.getCollectedDueList(
        search: _searchController.text ?? '',
        nextPage: pageKey.toString() ?? '',
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
        final isLastPage = list.data?.lastPage == list.data?.currentPage;

        if (isLastPage) {
          collectedPageController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          collectedPageController.appendPage(newItems, nextPageKey);
        }
      }
    } catch (error) {
      collectedPageController.error = error;
    }
    setState(() {});
  }

  @override
  void initState() {
    allDuesController.addPageRequestListener(fetchDueDataList);
    collectedPageController.addPageRequestListener(fetchCollectedDueList);
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      setState(() {});
    });
    super.initState();
  }

  // -------- Filter
  String? _status;

  //--- status color
  Color getTextColor(String role) {
    Color color;
    switch (role.toLowerCase()) {
      case 'retailer':
        color = Color(0xFF56da87);
        break;
      case 'wholesaler':
        color = Color(0xFF25a9e0);
        break;
      case 'supplier':
        color = Color(0xFFA569BD);
        break;
      default:
        color = Colors.black;
        break;
    }
    return color;
  }

  bool isSearchVisible = false;
  bool isFilterVisible = false;

  // search filter
  void _onSearchFieldToggle() {
    setState(() {
      isSearchVisible = !isSearchVisible;
      if (!isSearchVisible) {
        _searchController.clear();
        isFilterVisible = false;
        _status = null;
      }
    });
  }

  // search filter
  void _onFilterToggle() {
    setState(() {
      isFilterVisible = !isFilterVisible;
      if (isFilterVisible) {
        isSearchVisible = false;
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    final theme = Theme.of(context);
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
                      _status = null;
                    }
                  },
                  onFieldSubmitted: (value) async {
                    if (_searchController.text.isNotEmpty) {
                      _searchController.text = value;
                      allDuesController.refresh();
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
            : isFilterVisible
                ? SizedBox(
                    height: 40,
                    child: DropdownButtonFormField<String?>(
                      decoration: InputDecoration(
                        contentPadding: EdgeInsets.all(10),
                        prefixIcon: Icon(
                          Icons.filter_list,
                          color: kNutral700,
                        ),
                        filled: true,
                        hintText: lang.selectType,
                        enabledBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kOutlineColor),
                          borderRadius: BorderRadius.circular(30),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kMainColor),
                          borderRadius: BorderRadius.circular(30),
                        ),
                      ),
                      initialValue: _status,
                      icon: const Icon(Icons.keyboard_arrow_down, color: kGreyTextColor),
                      items: [
                        "All",
                        "Customer",
                        "Supplier",
                        "Wholesaler",
                        "Retailer",
                      ]
                          .map((type) => DropdownMenuItem<String?>(
                                value: type,
                                child: Text(type, style: theme.textTheme.bodySmall),
                              ))
                          .toList(),
                      onChanged: (value) {
                        setState(() {
                          _status = value;
                        });
                        allDuesController.refresh();
                      },
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
                // search icon
                if (!isSearchVisible && !isFilterVisible)
                  IconButton(
                    padding: EdgeInsets.zero,
                    visualDensity: VisualDensity(horizontal: -2),
                    style: IconButton.styleFrom(iconSize: 18, minimumSize: Size(24, 24)),
                    onPressed: _onSearchFieldToggle,
                    icon: Icon(IconlyLight.search),
                  ),

                // filter icon
                if (!isSearchVisible && !isFilterVisible)
                  IconButton(
                    padding: EdgeInsets.zero,
                    visualDensity: VisualDensity(horizontal: -2),
                    style: IconButton.styleFrom(iconSize: 18, minimumSize: Size(24, 24)),
                    icon: Icon(Icons.filter_list),
                    onPressed: _onFilterToggle,
                  ),

                // close icon
                if (isSearchVisible || isFilterVisible)
                  CloseButton(
                    color: Colors.red,
                    onPressed: () {
                      setState(() {
                        isSearchVisible = false;
                        isFilterVisible = false;
                        _searchController.clear();
                        _status = null;
                        allDuesController.refresh();
                      });
                    },
                    style: IconButton.styleFrom(
                      iconSize: 18,
                      minimumSize: Size(24, 24),
                    ),
                  ),
              ],
            ),
          ),
        ],
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: isSearchVisible || isFilterVisible ? false : true,
        titleSpacing: 0,
        elevation: 0.0,
      ),
      body: RefreshIndicator.adaptive(
        onRefresh: () async => await Future.sync(() => allDuesController.refresh()),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  ContainerWithLabel(
                    color: Color(0xffFFF1E2),
                    label: '$currency${totalReceivable.toString()}',
                    description: lang.totalReceiveAble,
                  ),
                  SizedBox(width: 8),
                  ContainerWithLabel(
                    color: Color(0xffDEF7FA),
                    label: '$currency${totalPayable.toString()}',
                    description: lang.totalPayable,
                  ),
                ],
              ),
            ),
            Container(
              decoration: BoxDecoration(
                color: Color(0xffE7F7EF),
              ),
              child: TabBar(
                indicator: UnderlineTabIndicator(
                  borderSide: BorderSide(
                    color: theme.colorScheme.primary,
                    width: 2,
                  ),
                ),
                indicatorSize: TabBarIndicatorSize.tab,
                dividerHeight: 0,
                controller: _tabController,
                tabs: [
                  Tab(text: lang.dueList),
                  Tab(text: lang.dueCollected),
                ],
              ),
            ),
            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [
                  // All Due
                  RefreshIndicator(
                    onRefresh: () async => await Future.sync(() => allDuesController.refresh()),
                    child: PagedListView(
                      shrinkWrap: true,
                      padding: EdgeInsets.zero,
                      pagingController: allDuesController,
                      scrollController: _scrollController,
                      physics: AlwaysScrollableScrollPhysics(),
                      builderDelegate: PagedChildBuilderDelegate<DueListData>(
                        newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                        noItemsFoundIndicatorBuilder: (context) => Padding(
                          padding: const EdgeInsets.all(20.0),
                          child: Center(
                            child: EmptyListWidget(
                              title: lang.noDueFound,
                            ),
                          ),
                        ),
                        itemBuilder: (context, item, index) => Padding(
                          padding: const EdgeInsets.only(bottom: 0),
                          child: ListTile(
                            contentPadding: EdgeInsets.symmetric(horizontal: 16),
                            onTap: () async {
                              await DueCollectionScreen(
                                controller: allDuesController,
                                id: item.id,
                              ).launch(context);
                              collectedPageController.refresh();
                            },
                            leading: GestureDetector(
                              onTap: () {},
                              child: Container(
                                alignment: Alignment.center,
                                height: 40,
                                width: 40,
                                decoration: const BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: kMainColor,
                                ),
                                child: Text(
                                  (item.name?.length ?? 0) >= 2 ? item.name!.substring(0, 2) : "na",
                                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                        color: kWhite,
                                        fontWeight: FontWeight.w500,
                                      ),
                                ),
                              ),
                            ),
                            title: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Flexible(
                                  child: Text(
                                    item.name ?? (item.phone ?? 'N/A'),
                                    overflow: TextOverflow.ellipsis,
                                    maxLines: 1,
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor, fontWeight: FontWeight.w400, fontSize: 16),
                                  ),
                                ),
                                Text(
                                  '$currency ${item.due}',
                                  style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor, fontWeight: FontWeight.w400, fontSize: 16),
                                ),
                              ],
                            ),
                            subtitle: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  item.type.toString(),
                                  style: GoogleFonts.poppins(
                                    color: getTextColor(item.type?.toLowerCase().toString() ?? ''),
                                    fontSize: 15.0,
                                  ),
                                ),
                                Flexible(
                                  child: Text(
                                    (item.type?.toString().toLowerCase() ?? '') == 'supplier' ? lang.payable : lang.receivable,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      color: kGreyTextColor,
                                      fontWeight: FontWeight.w400,
                                      fontSize: 12,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            trailing: const Icon(
                              Icons.arrow_forward_ios,
                              color: kNutral700,
                              size: 18,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),

                  // due collected List
                  RefreshIndicator.adaptive(
                    onRefresh: () async => await Future.sync(() => collectedPageController.refresh()),
                    child: PagedListView(
                      shrinkWrap: true,
                      padding: EdgeInsets.only(top: 16),
                      pagingController: collectedPageController,
                      scrollController: _scrollController,
                      physics: AlwaysScrollableScrollPhysics(),
                      builderDelegate: PagedChildBuilderDelegate<CollectedDueData>(
                        newPageProgressIndicatorBuilder: (context) => Center(child: const CircularProgressIndicator(color: kMainColor)),
                        noItemsFoundIndicatorBuilder: (context) => Padding(
                          padding: const EdgeInsets.all(20.0),
                          child: Center(
                            child: EmptyListWidget(
                              title: lang.noCollectionFound,
                            ),
                          ),
                        ),
                        itemBuilder: (context, item, index) => Padding(
                          padding: const EdgeInsets.only(bottom: 0),
                          child: InkWell(
                            onTap: () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => CollectedDueDetails(details: item),
                              ),
                            ),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 24.0),
                              child: Column(
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Flexible(
                                        child: Text(
                                          item.invoiceNumber.toString() ?? '',
                                          maxLines: 1,
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
                                          text: '${lang.totalDue}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.totalDue ?? 'N/A'}',
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
                                      Flexible(
                                        child: RichText(
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          text: TextSpan(
                                            text: item.party?.name ?? 'n/a',
                                            style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          ),
                                        ),
                                      ),
                                      RichText(
                                        text: TextSpan(
                                          text: '${lang.dueAfterPay}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                              text: '$currency${item.dueAmountAfterPay ?? 'N/A'}',
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
                                          text: (item.paymentDate != null)
                                              ? DateFormat.yMMMd().format(
                                                  DateTime.parse(item.paymentDate?.toString() ?? ''),
                                                )
                                              : 'N/A',
                                          style: theme.textTheme.bodyMedium?.copyWith(
                                            color: kNutral600,
                                          ),
                                        ),
                                      ),
                                      Flexible(
                                        child: RichText(
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          text: TextSpan(
                                            text: '${lang.paidAmount}: ',
                                            style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                            children: [
                                              TextSpan(
                                                text: '$currency${item.payDueAmount.toString()}',
                                                style: theme.textTheme.bodyMedium?.copyWith(
                                                  color: kTitleColor,
                                                  fontWeight: FontWeight.w500,
                                                ),
                                              )
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
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
