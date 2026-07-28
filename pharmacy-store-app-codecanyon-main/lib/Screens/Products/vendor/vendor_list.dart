import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:mobile_pos/Screens/Products/category/model/category_model.dart';
import 'package:mobile_pos/Screens/Products/vendor/add_new_vendor.dart';
import 'package:mobile_pos/Screens/Products/vendor/edit_vendor.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:nb_utils/nb_utils.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../Providers/category,brans,units_provide.dart';
import '../category/repo/category_repo.dart';
import '../Widgets/widgets.dart';

// ignore: must_be_immutable
class VendorList extends StatefulWidget {
  const VendorList({super.key, required this.isFromProductList});

  final bool isFromProductList;

  @override
  // ignore: library_private_types_in_public_api
  _VendorListState createState() => _VendorListState();
}

class _VendorListState extends State<VendorList> {
  String search = '';
  @override
  Widget build(BuildContext context) {
    final theme=Theme.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        title: Text(
            'Vendor List',
            //'Categories',
            style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600,color: kWhite,fontSize: 20)
        ),
        iconTheme: const IconThemeData(color: kWhite),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0.0,
      ),
      body: Consumer(builder: (context, ref, __) {
        final categoryData = ref.watch(categoryProvider);
        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
              child: Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: AppTextField(
                      textFieldType: TextFieldType.NAME,
                      decoration: InputDecoration(
                        border:  const OutlineInputBorder(),
                        //hintText: 'Search',
                        hintText: 'Search here...',
                        prefixIcon: Icon(
                          FeatherIcons.search,
                          color: kGreyTextColor.withValues(alpha: 0.5),
                        ),
                      ),
                      onChanged: (value) {
                        setState(() {
                          search = value;
                        });
                      },
                    ),
                  ),
                  const SizedBox(width: 10.0),
                  Expanded(
                    flex: 1,
                    child: GestureDetector(
                      onTap: () {
                        const AddNewVendor().launch(context);
                      },
                      child: Container(
                        padding: const EdgeInsets.only(left: 20.0, right: 20.0),
                        height: 57.0,
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
                  // const SizedBox(width: 20.0),
                ],
              ),
            ),
            Expanded(
              child: categoryData.when(data: (data) {
                return SingleChildScrollView(
                  child: data.isNotEmpty
                      ? ListView.builder(
                      padding: EdgeInsets.zero,
                      physics: const NeverScrollableScrollPhysics(),
                      shrinkWrap: true,
                      itemCount: data.length,
                      itemBuilder: (context, i) {
                        final List<String> variations = [];

                        GetCategoryAndVariationModel get = GetCategoryAndVariationModel(categoryName: data[i], variations: variations);
                        return (data[i].categoryName ?? '').toLowerCase().contains(search.toLowerCase())
                            ? GestureDetector(
                          onTap: widget.isFromProductList
                              ? () {}
                              : () {
                            Navigator.pop(context, get);
                          },
                          child: Padding(
                            padding: const EdgeInsets.only(top: 10),
                            child: Column(
                              children: [
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 16),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Text(
                                        data[i].categoryName.toString(),
                                        style: theme.textTheme.bodyLarge,
                                      ),
                                      const Spacer(),
                                      Row(
                                        children: [
                                          IconButton(
                                              padding: EdgeInsets.zero,
                                              onPressed: () {
                                                Navigator.push(
                                                    context,
                                                    MaterialPageRoute(
                                                      builder: (context) => EditVendor(
                                                        categoryModel: data[i],
                                                      ),
                                                    ));
                                              },
                                              icon:  Icon(IconlyBold.edit,color: kMainColor,)),
                                          IconButton(
                                              visualDensity: const VisualDensity(horizontal: -4),
                                              padding: EdgeInsets.zero,
                                              onPressed: () async {
                                                bool confirmDelete = await showDeleteAlert(context: context, itemsName: 'Vendor');
                                                if (confirmDelete) {
                                                  EasyLoading.show();
                                                  if (await CategoryRepo().deleteCategory(context: context, categoryId: data[i].id ?? 0)) {
                                                    ref.refresh(categoryProvider);
                                                  }
                                                  EasyLoading.dismiss();
                                                }
                                              },
                                              icon:  Icon(
                                                IconlyLight.delete,
                                                color: Colors.redAccent,
                                              )),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                Divider(thickness: 1.0,color: kOutlineBorder,height: 1,)
                              ],
                            ),
                          ),
                        )
                            : Container();
                      })
                      :  Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Text(
                      lang.S.of(context).noDataFound,
                      //'No Data Found'
                    ),
                  ),
                );
              }, error: (_, __) {
                return Container();
              }, loading: () {
                return const Center(child: SizedBox(height: 40, width: 40, child: CircularProgressIndicator()));
              }),
            ),
          ],
        );
      }),
    );
  }
}
