import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/category/model/category_model.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:nb_utils/nb_utils.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../Providers/category,brans,units_provide.dart';
import 'repo/category_repo.dart';
import '../Widgets/widgets.dart';
import 'add_category_screen.dart';

class CategoryList extends StatefulWidget {
  const CategoryList({super.key, required this.isFromProductList});
  final bool isFromProductList;
  @override
  State<CategoryList> createState() => _CategoryListState();
}

class _CategoryListState extends State<CategoryList> {
  final TextEditingController _searchController = TextEditingController();
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        title: Text(
          l.S.of(context).categories,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w600,
            color: kWhite,
            fontSize: 20,
          ),
        ),
        iconTheme: const IconThemeData(color: kWhite),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0.0,
      ),
      body: Consumer(
        builder: (context, ref, __) {
          final categoryData = ref.watch(categoryProvider);
          return categoryData.when(
            data: (data) {
              if (_debounce?.isActive ?? false) {
                _debounce?.cancel();
              }
              _debounce = Timer(const Duration(milliseconds: 500), () {
                if (mounted) {
                  setState(() {});
                }
              });

              final filteredData = data.where((category) {
                return (category.categoryName ?? '').toLowerCase().contains(_searchController.text.toLowerCase());
              }).toList();

              return Column(
                children: [
                  // Search TextField
                  Padding(
                    padding: const EdgeInsets.fromLTRB(24, 24, 24, 8),
                    child: Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: AppTextField(
                            controller: _searchController,
                            textFieldType: TextFieldType.NAME,
                            decoration: InputDecoration(
                              border: const OutlineInputBorder(),
                              hintText: l.S.of(context).search,
                              prefixIcon: Icon(
                                Icons.search,
                                color: kGreyTextColor.withValues(alpha: 0.5),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10.0),
                        Expanded(
                          flex: 1,
                          child: GestureDetector(
                            onTap: () async {
                              final ProductCategoryModel? category = await const AddCategoryScreen().launch(context);
                              if (category != null) {
                                setState(() {
                                  data.insert(0, category);
                                });
                              }
                            },
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 20.0),
                              height: 48.0,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(8.0),
                                border: Border.all(color: kMainColor),
                              ),
                              child: const Icon(
                                Icons.add,
                                color: kGreyTextColor,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Category List
                  if (filteredData.isNotEmpty)
                    Expanded(
                      child: ListView.separated(
                        padding: EdgeInsets.zero,
                        shrinkWrap: true,
                        itemCount: filteredData.length,
                        itemBuilder: (context, i) {
                          final category = filteredData[i];
                          return ListCardWidget(
                            title: category.categoryName ?? '',
                            // Delete
                            onDelete: () async {
                              bool confirmDelete = await showDeleteAlert(context: context, itemsName: 'category');
                              if (confirmDelete) {
                                EasyLoading.show();
                                if (await CategoryRepo().deleteCategory(
                                  context: context,
                                  categoryId: category.id ?? 0,
                                )) {
                                  ref.refresh(categoryProvider);
                                }
                                EasyLoading.dismiss();
                              }
                            },
                            // Edit
                            onEdit: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) => AddCategoryScreen(categoryModel: category),
                                ),
                              );
                            },
                          );
                        },
                        separatorBuilder: (_, i) {
                          return Divider(
                            thickness: 1.0,
                            color: theme.colorScheme.outline,
                          );
                        },
                      ),
                    )
                  else
                    Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Text(
                        l.S.of(context).noDataFound,
                      ),
                    ),
                ],
              );
            },
            error: (_, __) => Container(),
            loading: () => const Center(
              child: SizedBox(
                height: 40,
                width: 40,
                child: CircularProgressIndicator(),
              ),
            ),
          );
        },
      ),
    );
  }
}
