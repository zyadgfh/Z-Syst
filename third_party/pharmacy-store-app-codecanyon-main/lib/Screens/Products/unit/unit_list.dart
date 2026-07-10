import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Products/Providers/category,brans,units_provide.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import 'model/unit_model.dart';
import 'repo/unit_repo.dart';
import '../Widgets/widgets.dart';
import 'add_units.dart';

class UnitList extends StatefulWidget {
  const UnitList({super.key, required this.isFromProductList});

  final bool isFromProductList;

  @override
  // ignore: library_private_types_in_public_api
  _UnitListState createState() => _UnitListState();
}

class _UnitListState extends State<UnitList> {
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
          lang.S.of(context).units,
          style: GoogleFonts.poppins(
            color: kWhite,
            fontSize: 20.0,
          ),
        ),
        iconTheme: const IconThemeData(color: kWhite),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0.0,
      ),
      body: Consumer(
        builder: (context, ref, __) {
          final unitData = ref.watch(unitsProvider);
          return unitData.when(
              data: (data) {
                if (_debounce?.isActive ?? false) {
                  _debounce?.cancel();
                }
                _debounce = Timer(const Duration(milliseconds: 500), () {
                  if (mounted) {
                    setState(() {});
                  }
                });
                final filteredData = data.where((unit) {
                  return (unit.unitName ?? '').toLowerCase().contains(_searchController.text.toLowerCase());
                }).toList();

                return Column(
                  children: [
                    // Search Text field
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
                                hintText: lang.S.of(context).search,
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
                                final UnitModel? unit = await const AddUnitsScreen().launch(context);
                                if (unit != null) {
                                  setState(() {
                                    data.insert(0, unit);
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

                    // Unit List
                    if (filteredData.isNotEmpty)
                      Expanded(
                        child: ListView.separated(
                          padding: EdgeInsets.zero,
                          shrinkWrap: true,
                          itemCount: filteredData.length,
                          itemBuilder: (context, i) {
                            final unit = filteredData[i];
                            return ListCardWidget(
                              title: unit.unitName ?? '',

                              // Delete
                              onDelete: () async {
                                bool confirmDelete = await showDeleteAlert(context: context, itemsName: 'unit');
                                if (confirmDelete) {
                                  EasyLoading.show();
                                  if (await UnitsRepo().deleteUnit(context: context, unitId: unit.id ?? 0)) {
                                    ref.refresh(unitsProvider);
                                  }
                                  EasyLoading.dismiss();
                                }
                              },

                              // Edit
                              onEdit: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => AddUnitsScreen(
                                      unit: unit,
                                    ),
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
                          lang.S.of(context).noDataFound,
                        ),
                      ),
                  ],
                );
              },
              error: (_, __) {
                return Container();
              },
              loading: () => const Center(
                    child: SizedBox(
                      height: 40,
                      width: 40,
                      child: CircularProgressIndicator(),
                    ),
                  ));
        },
      ),
    );
  }
}
