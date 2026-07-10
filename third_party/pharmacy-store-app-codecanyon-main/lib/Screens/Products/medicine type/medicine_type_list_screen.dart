import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/provider/medicine_type_provider.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/repo/medicine_type_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:nb_utils/nb_utils.dart';
import '../Widgets/widgets.dart';
import 'add_medicine_type_screen.dart';
import 'model/medicine_type_model.dart';

class MedicineTypeListScreen extends StatefulWidget {
  const MedicineTypeListScreen({super.key, required this.isFromProductList});
  final bool isFromProductList;
  @override
  State<MedicineTypeListScreen> createState() => _MedicineTypeListScreenState();
}

class _MedicineTypeListScreenState extends State<MedicineTypeListScreen> {
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
    final _lang = l.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        title: Text(
          l.S.of(context).medicineType,
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
      body: Consumer(builder: (context, ref, __) {
        final medicineTypeList = ref.watch(medicineTypeProvider);
        return medicineTypeList.when(
          data: (data) {
            if (_debounce?.isActive ?? false) {
              _debounce?.cancel();
            }
            _debounce = Timer(const Duration(milliseconds: 500), () {
              if (mounted) {
                setState(() {});
              }
            });
            final filteredData = data.where((type) {
              return (type.name ?? '').toLowerCase().contains(_searchController.text.toLowerCase());
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
                            final MedicineTypeModel? category = await const AddMedicineTypeScreen(
                              fromDropdown: false,
                            ).launch(context);
                            if (category != null) {
                              setState(() {
                                data.insert(0, category);
                              });
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.only(left: 20.0, right: 20.0),
                            height: 48.0,
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(5.0),
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

                // Type List
                if (filteredData.isNotEmpty)
                  Expanded(
                    child: ListView.separated(
                      padding: EdgeInsets.zero,
                      shrinkWrap: true,
                      itemCount: filteredData.length,
                      itemBuilder: (context, i) {
                        final medicineType = filteredData[i];
                        return ListCardWidget(
                          title: medicineType.name ?? '',
                          // Delete
                          onDelete: () async {
                            bool confirmDelete = await showDeleteAlert(context: context, itemsName: _lang.medicineType);
                            if (confirmDelete) {
                              EasyLoading.show();
                              if (await MedicineTypeRepo().deleteMedicineType(context: context, unitId: medicineType.id ?? 0)) {
                                ref.refresh(medicineTypeProvider);
                              }
                              EasyLoading.dismiss();
                            }
                          },

                          // Edit
                          onEdit: () {
                            Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) => AddMedicineTypeScreen(
                                    medicineType: medicineType,
                                    fromDropdown: false,
                                  ),
                                ));
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
      }),
    );
  }
}
