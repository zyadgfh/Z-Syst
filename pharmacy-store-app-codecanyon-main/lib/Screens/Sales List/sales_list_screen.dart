import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Sales%20List/model/sales_list_paginated_data_model.dart';
import 'package:mobile_pos/Screens/Sales%20List/repo/sales_list_repo.dart';
import 'package:mobile_pos/Screens/Sales%20List/sale_details.dart';
import 'package:mobile_pos/Screens/Sales/add_sales.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../../../constant.dart';
import '../../currency.dart';
import '../Sales/Model/sales_details_model.dart';
import '../due_list/due_collection_screen.dart';
import '../invoice return/invoice_return_screen.dart';
import '../widget/empty_widgets.dart';

class SalesListScreen extends StatefulWidget {
  const SalesListScreen({super.key});

  @override
  SalesListState createState() => SalesListState();
}

class SalesListState extends State<SalesListScreen> {
  ///_____Controllers______________________________________
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, SalesData> pageController = PagingController(firstPageKey: 1);

  ///_______Sales_List_Repo_________________________________________
  SalesListRepo sales = SalesListRepo();
  final TextEditingController _searchController = TextEditingController();
  String? productCode;

  ///_______Floating button_____________________________________
  bool _isFabVisible = true;
  double _previousScrollOffset = 0;

  ///______Get_sales_List________________________________________
  Future<void> fetchSalesListData(int pageKey) async {
    SalesListPaginatedDataModel? data;
    try {
      data = await sales.getSalesList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
      );
      if (data != null) {
        final newItems = data.data?.sales ?? [];
        final isLastPage = data.data?.lastPage == data.data?.currentPage;

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
    pageController.addPageRequestListener((pageKey) => fetchSalesListData(pageKey));
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

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final language = lang.S.of(context);
    return PopScope(
      canPop: true,
      child: AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          surfaceTintColor: kWhite,
          elevation: 0,
          iconTheme: const IconThemeData(color: kWhite),
          title: Text(
            lang.S.of(context).salesList,
            style: theme.textTheme.titleLarge?.copyWith(
              color: kWhite,
              fontSize: 20,
              fontWeight: FontWeight.w600,
            ),
          ),
          centerTitle: true,
        ),
        body: Consumer(builder: (context, ref, __) {
          final theme = Theme.of(context);
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.only(left: 16, right: 16, top: 16),
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
                    builderDelegate: PagedChildBuilderDelegate<SalesData>(
                      newPageProgressIndicatorBuilder: (context) => Center(
                        child: const CircularProgressIndicator(color: kMainColor),
                      ),
                      noItemsFoundIndicatorBuilder: (context) => Padding(
                        padding: const EdgeInsets.all(20.0),
                        child: Center(
                          child: EmptyListWidget(
                            title: language.noYetSaleAnyThing,
                          ),
                        ),
                      ),
                      itemBuilder: (context, item, index) => Padding(
                        padding: const EdgeInsets.only(bottom: 0),
                        child: InkWell(
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => SalesDetailsScreen(id: item.id ?? 0),
                              ),
                            );
                          },
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 24.0),
                            child: Column(
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Flexible(
                                      child: Text(
                                        item.party?.name ?? (item.party?.phone ?? language.guest),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                        style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600, fontSize: 16, color: kMainColor),
                                      ),
                                    ),
                                    SizedBox(width: 10),
                                    PopupMenuButton<String>(
                                      iconColor: kNutral700,
                                      padding: EdgeInsets.zero,
                                      onSelected: (String value) async {
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
                                          case 'edit':
                                            if ((item.saleReturnCounter ?? 0) > 0) {
                                              ScaffoldMessenger.of(context).showSnackBar(
                                                SnackBar(
                                                  content: Text('Returned sale cannot be edited.'),
                                                  backgroundColor: Colors.red,
                                                ),
                                              );
                                              return;
                                            }
                                            // Proceed with loading
                                            EasyLoading.show();
                                            if (item.id == null) {
                                              EasyLoading.dismiss();
                                              return;
                                            }
                                            try {
                                              SalesListRepo repo = SalesListRepo();
                                              SalesDetailsModel? details = await repo.getSalesDetails(id: item.id!);

                                              if (details != null) {
                                                EasyLoading.dismiss();
                                                await Navigator.push(
                                                  context,
                                                  MaterialPageRoute(
                                                    builder: (context) => AddSalesScreen(salesDetails: details),
                                                  ),
                                                );
                                                pageController.refresh();
                                              }
                                            } catch (e) {
                                              EasyLoading.showError(language.someThingWentWrong);
                                            } finally {
                                              EasyLoading.dismiss();
                                            }
                                            break;
                                          case 'receive':
                                            if (item.partyId != null) {
                                              await Navigator.push(
                                                context,
                                                MaterialPageRoute(
                                                  builder: (context) => DueCollectionScreen(
                                                    controller: pageController,
                                                    id: item.partyId,
                                                  ),
                                                ),
                                              );
                                            }
                                            break;
                                          case 'return':
                                            EasyLoading.show();

                                            if (item.id != null) {
                                              try {
                                                SalesListRepo repo = SalesListRepo();
                                                SalesDetailsModel? details = await repo.getSalesDetails(id: item.id!);
                                                EasyLoading.dismiss();
                                                if (details != null) {
                                                  await Navigator.push(
                                                    context,
                                                    MaterialPageRoute(
                                                      builder: (context) => InvoiceReturnScreen(sales: details),
                                                    ),
                                                  );
                                                  pageController.refresh();
                                                }
                                              } catch (e) {
                                                EasyLoading.showError(language.someThingWentWrong);
                                              }
                                            }
                                            break;
                                          default:
                                            print('Unknown action');
                                        }
                                      },
                                      itemBuilder: (BuildContext context) => [
                                        PopupMenuItem<String>(
                                          value: 'view',
                                          child: Text(language.viewInvoice),
                                        ),
                                        PopupMenuItem<String>(
                                          value: 'edit',
                                          child: Text(language.edit),
                                        ),
                                        if ((item.dueAmount ?? 0) > 0)
                                          PopupMenuItem<String>(
                                            value: 'receive',
                                            child: Text(language.receivedPayment),
                                          ),
                                        PopupMenuItem<String>(
                                          value: 'return',
                                          child: Text(language.returns),
                                        ),
                                      ],
                                    )
                                  ],
                                ),
                                Row(
                                  children: [
                                    Flexible(
                                      child: RichText(          maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        text: TextSpan(
                                          text: '${language.invoice}: ',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                          children: [
                                            TextSpan(
                                                text: item.invoiceNumber ?? 'N/A',
                                                style: theme.textTheme.bodyMedium?.copyWith(
                                                  color: kTitleColor,
                                                  fontWeight: FontWeight.w500,
                                                )),
                                          ],
                                        ),
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
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Flexible(
                                      child: RichText(
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        text: TextSpan(
                                          text: '${language.phone}: ${item.party?.phone.toString() ?? 'N/A'}',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        ),
                                      ),
                                    ),

                                    RichText(
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
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
                                    Flexible(

                                      child: RichText(
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        text: TextSpan(
                                          text: item.saleDate != null
                                              ? '${language.date}: ${DateFormat('dd/MM/yyyy, h:mm a').format(
                                                  DateTime.parse(item.saleDate.toString()),
                                                )}'
                                              : '${language.date}: N/A',
                                          style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                        ),
                                      ),
                                    ),
                                    RichText(
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
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
                      ),
                    ),
                  ),
                ),
              ),
            ],
          );
        }),
        flotingActionButton: Padding(
          padding: const EdgeInsets.all(16.0),
          child: AnimatedOpacity(
            opacity: _isFabVisible ? 1.0 : 0.0,
            duration: Duration(milliseconds: 300),
            child: Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0xff14b8a6),
                    Color(0xFF00987F),
                  ],
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                ),
                boxShadow: [
                  BoxShadow(
                    color: Color(0xff14AE5C).withValues(alpha: 0.3),
                    blurRadius: 20,
                    spreadRadius: 0,
                    offset: Offset(0, 8),
                  ),
                ],
                borderRadius: BorderRadius.circular(30.0),
              ),
              child: FloatingActionButton.extended(
                elevation: 1,
                extendedIconLabelSpacing: 10.0,
                backgroundColor: Colors.transparent,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30.0),
                ),
                onPressed: () async {
                  await Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => AddSalesScreen(),
                    ),
                  );
                  pageController.refresh();
                },
                label: Row(
                  children: [
                    const Icon(Icons.add, color: Colors.white),
                    const SizedBox(width: 10.0),
                    Text(
                      language.addSale,
                      style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
