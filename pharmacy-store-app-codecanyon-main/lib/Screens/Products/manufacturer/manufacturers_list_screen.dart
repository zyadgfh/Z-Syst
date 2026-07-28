import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/provider/manufacturer_provider.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/repo/manufacturer_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:nb_utils/nb_utils.dart';
import '../Widgets/widgets.dart';
import 'add_medicine_type_screen.dart';

// ignore: must_be_immutable
class ManufacturersListScreen extends StatefulWidget {
  const ManufacturersListScreen({super.key, required this.isFromProductList});

  final bool isFromProductList;

  @override
  // ignore: library_private_types_in_public_api
  _ManufacturersListScreenState createState() => _ManufacturersListScreenState();
}

class _ManufacturersListScreenState extends State<ManufacturersListScreen> {
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
    final lang = lang.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        title: Text(
          lang.manufacturer,
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
        final manufacturers = ref.watch(manufacturerProvider);
        return manufacturers.when(
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
              return (category.name ?? '').toLowerCase().contains(_searchController.text.toLowerCase());
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
                          onTap: () {
                            const AddManufacturerScreen(
                              fromDropdown: false,
                            ).launch(context);
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

                // Manufacturer List
                if (filteredData.isNotEmpty)
                  Expanded(
                    child: ListView.separated(
                      padding: EdgeInsets.zero,
                      shrinkWrap: true,
                      itemCount: filteredData.length,
                      itemBuilder: (context, i) {
                        final manufacturer = filteredData[i];
                        return ListCardWidget(
                          title: manufacturer.name ?? '',
                          // Delete
                          onDelete: () async {
                            bool confirmDelete = await showDeleteAlert(context: context, itemsName: 'Manufacturer');
                            if (confirmDelete) {
                              EasyLoading.show();
                              if (await ManufacturerRepo().deleteManufacturer(context: context, unitId: manufacturer.id ?? 0)) {
                                ref.refresh(manufacturerProvider);
                              }
                              EasyLoading.dismiss();
                            }
                          },
                          // Edit
                          onEdit: () {
                            Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) => AddManufacturerScreen(
                                    manufacturer: manufacturer,
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
                      lang.S.of(context).noDataFound,
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
