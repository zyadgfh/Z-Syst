import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:nb_utils/nb_utils.dart';
import '../../constant.dart';
import '../widget/acnoo_scafold.dart';
import 'Providers/income_category_provider.dart';
import 'add_income_category.dart';

class IncomeCategoryList extends StatefulWidget {
  const IncomeCategoryList({super.key, this.mainContext});

  final BuildContext? mainContext;

  @override
  State<IncomeCategoryList> createState() => _IncomeCategoryListState();
}

class _IncomeCategoryListState extends State<IncomeCategoryList> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final l10n = lang.S.of(context);
    return Consumer(builder: (context, ref, _) {
      final data = ref.watch(incomeCategoryProvider);

      final filteredData = data.when(
        data: (data) {
          return data.where((item) {
            return item.categoryName?.toLowerCase().contains(_searchQuery.toLowerCase()) ?? false;
          }).toList();
        },
        error: (error, stackTrace) {
          return [];
        },
        loading: () {
          return [];
        },
      );

      return AcnooScafoldWidget(
        appBar: AppBar(
          title: Text(
            l10n.incomeCategories,
            style: GoogleFonts.poppins(
              color: Colors.white,
              fontSize: 20.0,
            ),
          ),
          iconTheme: const IconThemeData(color: Colors.white),
          centerTitle: true,
          backgroundColor: Colors.transparent,
          elevation: 0.0,
        ),
        body: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: AppTextField(
                      textFieldType: TextFieldType.NAME,
                      controller: _searchController,
                      onChanged: (value) {
                        setState(() {
                          _searchQuery = value;
                        });
                      },
                      decoration: InputDecoration(
                        border: const OutlineInputBorder(),
                        hintText: l10n.search,
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
                    child: OutlinedButton(
                      onPressed: () {
                        const AddIncomeCategory().launch(context);
                      },
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 20.0),
                        minimumSize: Size(double.infinity, 48.0),
                        side: BorderSide(color: Color(0xffDCDBE5)),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(5.0),
                        ),
                      ),
                      child: const Icon(
                        Icons.add,
                        color: kMainColor,
                      ),
                    ),
                  ),
                ],
              ),
              filteredData.isEmpty
                  ? Center(
                      child: Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Text(l10n.noCategoryFound),
                    ))
                  : Expanded(
                      child: ListView.builder(
                        padding: EdgeInsets.symmetric(horizontal: 2, vertical: 16),
                        physics: AlwaysScrollableScrollPhysics(),
                        shrinkWrap: true,
                        itemCount: filteredData.length,
                        itemBuilder: (BuildContext context, int index) {
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: Container(
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
                                contentPadding: EdgeInsets.symmetric(horizontal: 10),
                                title: Text(
                                  filteredData[index].categoryName ?? '',
                                  style: GoogleFonts.poppins(
                                    fontSize: 18.0,
                                    color: Colors.black,
                                  ),
                                ),
                                trailing: TextButton(
                                  onPressed: () {
                                    Navigator.pop(
                                      context,
                                      filteredData[index],
                                    );
                                  },
                                  child: Text(l10n.choose),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
            ],
          ),
        ),
      );
    });
  }
}
