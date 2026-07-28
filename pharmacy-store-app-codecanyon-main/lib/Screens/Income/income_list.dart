import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Income/Repo/income_repo.dart';
import 'package:mobile_pos/Screens/Products/Widgets/widgets.dart';
import 'package:nb_utils/nb_utils.dart';
import '../../constant.dart';
import '../../currency.dart';
import '../widget/empty_widgets.dart';
import 'Model/income_model.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'Providers/income_category_provider.dart';
import 'Repo/income_category_repo.dart';
import 'add_income.dart';
import 'add_income_category.dart';

class IncomeList extends StatefulWidget {
  const IncomeList({super.key});

  @override
  // ignore: library_private_types_in_public_api
  _IncomeListState createState() => _IncomeListState();
}

class _IncomeListState extends State<IncomeList> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  //__________________________________controllers_________________________________
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, IncomeData> pageController = PagingController(firstPageKey: 1);

  IncomeRepo incomeList = IncomeRepo();

  // Fetch Income List
  Future<void> fetchIncomeListData(int pageKey) async {
    IncomeListModel? list;
    try {
      list = await incomeList.fetchIncome(
        nextPage: pageKey.toString(),
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
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
  }

  @override
  void initState() {
    super.initState();
    pageController.addPageRequestListener((pageKey) => fetchIncomeListData(pageKey));
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      setState(() {});
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(builder: (context, ref, __) {
      final data = ref.watch(incomeCategoryProvider);
      return Scaffold(
        backgroundColor: theme.colorScheme.primaryContainer,
        appBar: AppBar(
            title: Text(
              lang.income,
              style: GoogleFonts.poppins(
                color: Colors.white,
                fontSize: 20.0,
              ),
            ),
            iconTheme: const IconThemeData(color: Colors.white),
            centerTitle: true,
            backgroundColor: theme.colorScheme.primary,
            elevation: 0.0,
            bottom: PreferredSize(
              preferredSize: Size.fromHeight(60),
              child: Container(
                decoration: BoxDecoration(
                  color: Color(0xffE7F7EF),
                  borderRadius: BorderRadius.vertical(
                    top: Radius.circular(
                      35,
                    ),
                  ),
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
                    Tab(text: lang.allIncomes),
                    Tab(text: lang.category),
                  ],
                ),
              ),
            )),
        body: TabBarView(
          controller: _tabController,
          children: [
            // All Incomes Tab
            RefreshIndicator.adaptive(
              onRefresh: () => Future.sync(() => pageController.refresh()),
              child: PagedListView(
                shrinkWrap: true,
                padding: EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                pagingController: pageController,
                scrollController: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                builderDelegate: PagedChildBuilderDelegate<IncomeData>(
                  newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                  noItemsFoundIndicatorBuilder: (context) => Center(
                    child: EmptyListWidget(
                      title: lang.noIncomeFound,
                    ),
                  ),
                  itemBuilder: (context, item, index) => Padding(
                    padding: const EdgeInsets.only(bottom: 16.0),
                    child: Container(
                      // margin: EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(8),
                        color: theme.colorScheme.primaryContainer,
                        boxShadow: [
                          BoxShadow(
                            color: Color(0xff473232).withValues(alpha: 0.05),
                            blurRadius: 8,
                            spreadRadius: -1,
                            offset: Offset(0, 3),
                          ),
                          BoxShadow(
                            color: Color(0xff0C1A4B).withValues(alpha: 0.02),
                            blurRadius: 8,
                            spreadRadius: -1,
                            offset: Offset(0, 0),
                          ),
                        ],
                      ),
                      child: ListTile(
                        contentPadding: EdgeInsets.only(left: 15),
                        visualDensity: VisualDensity(horizontal: -2, vertical: -2),
                        tileColor: theme.colorScheme.primaryContainer,
                        title: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              item.incomeFor ?? '',
                              style: theme.textTheme.bodyLarge,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            Text(
                              '$currency${item.amount.toString()}',
                              maxLines: 1,
                              style: theme.textTheme.bodyLarge,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                        subtitle: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              item.category?.categoryName ?? '',
                              maxLines: 2,
                              style: theme.textTheme.bodySmall,
                              overflow: TextOverflow.ellipsis,
                            ),
                            Text(
                              DateFormat.yMMMd().format(
                                DateTime.parse(item.incomeDate.toString()),
                              ),
                              style: theme.textTheme.bodySmall,
                            ),
                          ],
                        ),
                        trailing: PopupMenuButton<int>(
                          padding: EdgeInsets.zero,
                          onSelected: (value) async {
                            switch (value) {
                              case 0:
                                AddIncome(
                                  income: item,
                                  pagingController: pageController,
                                ).launch(context);
                                break;
                              case 1:
                                bool deletable = await showDeleteAlert(itemsName: lang.income, context: context);
                                if (deletable) {
                                  try {
                                    EasyLoading.show(status: lang.deleting);
                                    final incomeRepo = IncomeRepo();
                                    final result = await incomeRepo.deleteIncome(id: item.id.toString());
                                    if (result) {
                                      pageController.refresh();
                                      EasyLoading.showSuccess(lang.deletedSuccessfully);
                                    } else {
                                      EasyLoading.showError(lang.failedToDeleteTheIncome);
                                    }
                                  } catch (e) {
                                    EasyLoading.showError('Error deleting income: $e');
                                  } finally {
                                    EasyLoading.dismiss();
                                  }
                                }

                                break;
                              case 2:
                                showDialog(
                                  context: (context),
                                  builder: (context) {
                                    return Dialog(
                                      backgroundColor: theme.colorScheme.primaryContainer,
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: Padding(
                                        padding: const EdgeInsets.all(8.0),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                              children: [
                                                Text(
                                                  lang.viewDetails,
                                                  style: theme.textTheme.titleMedium,
                                                ),
                                                CloseButton(
                                                  onPressed: () => Navigator.pop(context),
                                                )
                                              ],
                                            ),
                                            Divider(
                                              color: theme.colorScheme.outline,
                                              height: 0,
                                            ),
                                            SizedBox(height: 10),
                                            ...{
                                              lang.title: item.incomeFor ?? 'n/a',
                                              lang.category: item.category?.categoryName ?? 'n/a',
                                              lang.cost: '$currency${item.amount.toString()}',
                                              lang.date: DateFormat.yMMMd().format(
                                                DateTime.parse(item.incomeDate.toString() ?? 'n/a'),
                                              ),
                                              lang.note: item.note ?? 'n/a',
                                              // Add other values here if needed
                                            }.entries.map(
                                              (entry) {
                                                return Padding(
                                                  padding: const EdgeInsets.symmetric(vertical: 10.0),
                                                  child: Column(
                                                    crossAxisAlignment: CrossAxisAlignment.start,
                                                    children: [
                                                      Text(entry.key, style: theme.textTheme.bodySmall),
                                                      SizedBox(height: 2),
                                                      Text(entry.value, style: theme.textTheme.bodyMedium),
                                                    ],
                                                  ),
                                                );
                                              },
                                            ),
                                          ],
                                        ),
                                      ),
                                    );
                                  },
                                );
                                break;
                            }
                          },
                          itemBuilder: (BuildContext context) => [
                            PopupMenuItem<int>(
                              value: 0,
                              child: Row(
                                children: [
                                  Icon(IconlyLight.edit_square, color: Colors.black),
                                  SizedBox(width: 10),
                                  Text(lang.edit),
                                ],
                              ),
                            ),
                            PopupMenuItem<int>(
                              value: 1,
                              child: Row(
                                children: [
                                  Icon(IconlyLight.delete, color: Colors.black),
                                  SizedBox(width: 10),
                                  Text(lang.delete),
                                ],
                              ),
                            ),
                            PopupMenuItem<int>(
                              value: 2,
                              child: Row(
                                children: [
                                  Icon(IconlyLight.show, color: Colors.black),
                                  SizedBox(width: 10),
                                  Text(lang.view),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),

            // Income Category List
            data.when(
              data: (data) {
                if (data.isNotEmpty) {
                  return ListView.separated(
                    shrinkWrap: true,
                    itemCount: data.length,
                    padding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                    physics: AlwaysScrollableScrollPhysics(),
                    itemBuilder: (BuildContext context, int index) {
                      return Container(
                        margin: EdgeInsets.only(bottom: 16),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(8),
                          color: theme.colorScheme.primaryContainer,
                          boxShadow: [
                            BoxShadow(
                              color: Color(0xff473232).withValues(alpha: 0.05),
                              blurRadius: 8,
                              spreadRadius: -1,
                              offset: Offset(0, 0),
                            ),
                            BoxShadow(
                              color: Color(0xff0C1A4B).withValues(alpha: 0.02),
                              blurRadius: 8,
                              spreadRadius: -1,
                              offset: Offset(0, 0),
                            ),
                          ],
                        ),
                        child: ListTile(
                          contentPadding: EdgeInsets.only(left: 15),
                          visualDensity: VisualDensity(horizontal: -2, vertical: -2),
                          tileColor: theme.colorScheme.primaryContainer,
                          title: Text(
                            data[index].categoryName ?? '',
                            style: theme.textTheme.bodyLarge,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                onPressed: () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => AddIncomeCategory(
                                      category: data[index],
                                    ),
                                  ),
                                ),
                                icon: Icon(
                                  IconlyLight.edit,
                                  color: theme.colorScheme.primary,
                                ),
                              ),
                              IconButton(
                                onPressed: () async {
                                  bool deletable = await showDeleteAlert(itemsName: lang.incomeCategory, context: context);
                                  if (deletable) {
                                    EasyLoading.show(status: lang.deleting);
                                    final incomeRepo = IncomeCategoryRepo();
                                    try {
                                      final result = await incomeRepo.deleteCategory(id: data[index].id.toString());
                                      if (result) {
                                        ref.refresh(incomeCategoryProvider);
                                        EasyLoading.showSuccess(lang.deletedSuccessfully);
                                      } else {
                                        EasyLoading.showError(lang.failedToDeleteThisCategory);
                                      }
                                    } catch (e) {
                                      EasyLoading.showError('Error deleting category: $e');
                                    } finally {
                                      EasyLoading.dismiss();
                                    }
                                  }
                                },
                                icon: Icon(
                                  IconlyLight.delete,
                                  color: Colors.red,
                                ),
                              )
                            ],
                          ),
                        ),
                      );
                    },
                    separatorBuilder: (_, i) {
                      return SizedBox.shrink();
                    },
                  );
                } else {
                  return EmptyListWidget(
                    title: lang.noCategoryFound,
                  );
                }
              },
              error: (error, stackTrace) {
                return Text(error.toString());
              },
              loading: () => const Center(
                child: SizedBox(
                  height: 40,
                  width: 40,
                  child: CircularProgressIndicator(),
                ),
              ),
            )
          ],
        ),
        bottomNavigationBar: Container(
          decoration: BoxDecoration(
            color: theme.colorScheme.primaryContainer,
            boxShadow: [
              BoxShadow(
                color: Color(0xff000000).withValues(alpha: 0.05),
                blurRadius: 20,
                spreadRadius: 0,
                offset: Offset(0, -4),
              ),
            ],
          ),
          padding: EdgeInsets.symmetric(
            horizontal: 24,
            vertical: 16,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                    minimumSize: Size(
                      double.maxFinite,
                      46,
                    ),
                    backgroundColor: theme.colorScheme.primary),
                onPressed: () => _tabController.index == 0
                    ? AddIncome(
                        pagingController: pageController,
                      ).launch(context)
                    : const AddIncomeCategory().launch(context),
                label: _tabController.index == 0 ? Text(lang.createIncome) : Text(lang.createCategory),
                icon: Icon(Icons.add),
              ),
            ],
          ),
        ),
      );
    });
  }
}
