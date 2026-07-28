import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:iconly/iconly.dart';
import 'package:mobile_pos/Screens/tax%20rates/model/tax_model.dart';
import 'package:mobile_pos/Screens/tax%20rates/provider/text_repo.dart';
import 'package:mobile_pos/Screens/tax%20rates/repo/tax_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import '../Products/Widgets/widgets.dart';
import 'add_group_tax.dart';
import 'create_single_tax.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;

class TaxReport extends StatefulWidget {
  const TaxReport({super.key});

  @override
  State<TaxReport> createState() => _TaxReportState();
}

class _TaxReportState extends State<TaxReport> {
  int? _selectedRowIndex; // Track the selected row index

  void _showRowDetailsModal(BuildContext context, TaxModel selectedTax) {
    final theme = Theme.of(context);
    showModalBottomSheet(
      isScrollControlled: true,
      isDismissible: true,
      showDragHandle: true,
      context: context,
      builder: (context) {
        return Container(
          width: double.maxFinite,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                kMainColorBg,
                Colors.white,
              ],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
            borderRadius: const BorderRadius.vertical(
              top: Radius.circular(20),
            ),
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header with Close Icon
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Tax Details',
                      style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    IconButton(
                      padding: EdgeInsets.zero,
                      visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                      icon: Icon(Icons.close, color: kTitleColor),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Name
                Card(
                  elevation: 1,
                  shadowColor: Colors.grey.shade50,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: ListTile(
                    contentPadding: EdgeInsets.symmetric(horizontal: 16),
                    visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                    title: Text(
                      'Name',
                      style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w400),
                    ),
                    subtitle: Text(
                      selectedTax.name ?? 'N/A',
                      style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                ),
                const SizedBox(height: 8),

                // Tax Rate
                Card(
                  elevation: 1,
                  shadowColor: Colors.grey.shade50,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: ListTile(
                    contentPadding: EdgeInsets.symmetric(horizontal: 16),
                    visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                    leading: const Icon(Icons.percent, color: kMainColor, size: 16),
                    title: Text(
                      'Tax Rate',
                      style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w400),
                    ),
                    subtitle: Text(
                      selectedTax.rate?.toString() ?? 'N/A',
                      style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                ),
                const SizedBox(height: 8),

                // Sub Taxes
                Card(
                  elevation: 1,
                  shadowColor: Colors.grey.shade50,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Header
                        Row(
                          children: [
                            const Icon(Icons.list, color: kMainColor),
                            const SizedBox(width: 8),
                            Text(
                              'Sub Taxes',
                              style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor, fontWeight: FontWeight.w700),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),

                        if (selectedTax.subTax != null && selectedTax.subTax!.isNotEmpty)
                          ListView.builder(
                            shrinkWrap: true,
                            physics: const ClampingScrollPhysics(),
                            itemCount: selectedTax.subTax!.length,
                            itemBuilder: (context, index) {
                              final subTax = selectedTax.subTax![index];
                              return Container(
                                margin: const EdgeInsets.only(bottom: 8),
                                padding: const EdgeInsets.all(0),
                                decoration: BoxDecoration(
                                  // color: Colors.blue.shade50,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Row(
                                  children: [
                                    // Sub Tax Icon
                                    Container(
                                      height: 20,
                                      width: 20,
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        gradient: kGradiant.withOpacity(0.4),
                                      ),
                                      child: Center(
                                          child: Text(
                                        (index + 1).toString(),
                                        style: theme.textTheme.bodySmall?.copyWith(color: kTitleColor),
                                      )),
                                    ),

                                    const SizedBox(width: 12),

                                    // Sub Tax Name and Percentage
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            subTax.name ?? 'N/A',
                                            style: theme.textTheme.bodyMedium,
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            '${subTax.rate?.toString() ?? 'N/A'}%',
                                            style: theme.textTheme.bodySmall,
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          )
                        else
                          Text(
                            'No sub-taxes available',
                            style: theme.textTheme.bodyMedium,
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    final theme = Theme.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        title: Text(
          lang.taxRates,
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.bold,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0.0,
      ),
      body: LayoutBuilder(
        builder: (BuildContext context, BoxConstraints constraints) {
          final maxWidth = constraints.maxWidth - 16;
          return Consumer(
            builder: (_, ref, watch) {
              final tax = ref.watch(taxProvider);
              return tax.when(
                data: (taxes) {
                  List<TaxModel> singleTaxes = [];
                  List<TaxModel> groupTaxes = [];

                  for (var element in taxes) {
                    if (element.subTax == null) {
                      singleTaxes.add(element);
                    } else {
                      groupTaxes.add(element);
                    }
                  }

                  return SingleChildScrollView(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          //___________________________ Tax Rates Section ___________________________
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    lang.taxRatesManageYourTaxRates,
                                    style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                                  ),
                                  SizedBox(
                                    height: 25,
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: kMainColor,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 0),
                                      ),
                                      onPressed: () => Navigator.push(
                                        context,
                                        MaterialPageRoute(
                                          builder: (context) => CreateSingleTax(),
                                        ),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(FeatherIcons.plus, size: 16, color: Colors.white),
                                          const SizedBox(width: 4),
                                          Text(
                                            lang.add,
                                            style: GoogleFonts.poppins(
                                              fontSize: 14,
                                              color: Colors.white,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 16),
                              SingleChildScrollView(
                                scrollDirection: Axis.horizontal,
                                child: ConstrainedBox(
                                  constraints: BoxConstraints(minWidth: maxWidth),
                                  child: DataTable(
                                    headingRowColor: WidgetStateColor.resolveWith((states) => kMainColorBg),
                                    border: TableBorder.all(
                                      borderRadius: BorderRadius.circular(8),
                                      color: kTextFiledBorder,
                                    ),
                                    dividerThickness: 0.0,
                                    sortAscending: true,
                                    showCheckboxColumn: false,
                                    horizontalMargin: 12.0,
                                    columnSpacing: 16,
                                    dataRowMinHeight: 40,
                                    headingRowHeight: 48,
                                    dataRowColor: const WidgetStatePropertyAll(Colors.white),
                                    columns: <DataColumn>[
                                      DataColumn(
                                        label: Text(
                                          lang.name,
                                          style: GoogleFonts.poppins(
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      DataColumn(
                                        label: Center(
                                          child: Text(
                                            lang.taxRates,
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ),
                                      DataColumn(
                                        label: Align(
                                          alignment: Alignment.center,
                                          child: Text(
                                            lang.status,
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ),
                                      DataColumn(
                                        label: Align(
                                          alignment: Alignment.center,
                                          child: Text(
                                            lang.action,
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                    rows: List.generate(
                                      singleTaxes.length,
                                      (index) => DataRow(
                                        cells: [
                                          DataCell(
                                            Text(
                                              singleTaxes[index].name ?? '',
                                              style: GoogleFonts.poppins(
                                                color: kGreyTextColor,
                                              ),
                                            ),
                                          ),
                                          DataCell(
                                            Center(
                                              child: Text(
                                                singleTaxes[index].rate.toString(),
                                                style: GoogleFonts.poppins(
                                                  color: kGreyTextColor,
                                                ),
                                              ),
                                            ),
                                          ),
                                          DataCell(
                                            Center(
                                              child: Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                decoration: BoxDecoration(
                                                  color: (singleTaxes[index].status ?? false) ? Colors.green.withValues(alpha: 0.2) : Colors.red.withValues(alpha: 0.2),
                                                  borderRadius: BorderRadius.circular(12),
                                                ),
                                                child: Text(
                                                  (singleTaxes[index].status ?? false) ? lang.active : lang.inActive,
                                                  style: GoogleFonts.poppins(
                                                    color: (singleTaxes[index].status ?? false) ? Colors.green : Colors.red,
                                                  ),
                                                ),
                                              ),
                                            ),
                                          ),
                                          DataCell(
                                            Row(
                                              children: [
                                                IconButton(
                                                  onPressed: () => Navigator.push(
                                                    context,
                                                    MaterialPageRoute(
                                                      builder: (context) => CreateSingleTax(taxModel: singleTaxes[index]),
                                                    ),
                                                  ),
                                                  icon: const Icon(
                                                    IconlyLight.edit_square,
                                                    size: 20,
                                                    color: kMainColor,
                                                  ),
                                                ),
                                                IconButton(
                                                  onPressed: () async {
                                                    bool result = await showDeleteAlert(context: context, itemsName: 'Tax');
                                                    if (result) {
                                                      EasyLoading.show(status: lang.deleting);
                                                      final repo = TaxRepo();
                                                      try {
                                                        final result = await repo.deleteTax(id: singleTaxes[index].id.toString());
                                                        if (result) {
                                                          ref.refresh(taxProvider);
                                                          EasyLoading.showSuccess(lang.deletedSuccessfully);
                                                        } else {
                                                          EasyLoading.showError(lang.failedToDeleteThisTax);
                                                        }
                                                      } catch (e) {
                                                        EasyLoading.showError('Error deleting tax: $e');
                                                      } finally {
                                                        EasyLoading.dismiss();
                                                      }
                                                    }
                                                  },
                                                  icon: const Icon(
                                                    Icons.delete_outline,
                                                    size: 20,
                                                    color: Colors.red,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                        color: WidgetStateColor.resolveWith(
                                          (Set<WidgetState> states) {
                                            return index % 2 != 0 ? kTextFiledBorder.withValues(alpha: 0.1) : Colors.white;
                                          },
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 24),

                          //___________________________ Tax Group Section ___________________________
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        lang.taxGroup,
                                        style: GoogleFonts.poppins(
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold,
                                          color: kTitleColor,
                                        ),
                                      ),
                                      Text(
                                        '(${lang.combinationOfMultipleTaxes})',
                                        style: GoogleFonts.poppins(
                                          color: kGreyTextColor,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const Spacer(),
                                  SizedBox(
                                    height: 25,
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: kMainColor,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 0),
                                      ),
                                      onPressed: () => Navigator.push(
                                        context,
                                        MaterialPageRoute(
                                          builder: (context) => AddGroupTax(),
                                        ),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(FeatherIcons.plus, size: 16, color: Colors.white),
                                          const SizedBox(width: 4),
                                          Text(
                                            lang.add,
                                            style: GoogleFonts.poppins(
                                              fontSize: 14,
                                              color: Colors.white,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 16),
                              SingleChildScrollView(
                                scrollDirection: Axis.horizontal,
                                child: ConstrainedBox(
                                  constraints: BoxConstraints(minWidth: maxWidth),
                                  child: DataTable(
                                    headingRowColor: WidgetStateColor.resolveWith((states) => kMainColorBg),
                                    border: TableBorder.all(
                                      borderRadius: BorderRadius.circular(8),
                                      color: kTextFiledBorder,
                                    ),
                                    dividerThickness: 0.0,
                                    sortAscending: true,
                                    showCheckboxColumn: false,
                                    horizontalMargin: 12.0,
                                    columnSpacing: 16,
                                    dataRowMinHeight: 40,
                                    headingRowHeight: 48,
                                    dataRowColor: const WidgetStatePropertyAll(Colors.white),
                                    columns: <DataColumn>[
                                      DataColumn(
                                        label: Text(
                                          lang.name,
                                          style: GoogleFonts.poppins(
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      DataColumn(
                                        label: Center(
                                          child: Text(
                                            '${lang.taxRates} %',
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ),
                                      DataColumn(
                                        label: Align(
                                          alignment: Alignment.center,
                                          child: Text(
                                            lang.status,
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ),
                                      // DataColumn(
                                      //   label: Align(
                                      //     alignment: Alignment.center,
                                      //     child: Text(
                                      //       lang.subTaxes,
                                      //       style: GoogleFonts.poppins(
                                      //         fontWeight: FontWeight.bold,
                                      //       ),
                                      //     ),
                                      //   ),
                                      // ),
                                      DataColumn(
                                        label: Align(
                                          alignment: Alignment.center,
                                          child: Text(
                                            lang.action,
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                    rows: List.generate(
                                      groupTaxes.length,
                                      (index) => DataRow(
                                        selected: _selectedRowIndex == index,
                                        onSelectChanged: (isSelected) {
                                          setState(() {
                                            _selectedRowIndex = isSelected! ? index : null;
                                          });
                                          if (isSelected ?? false) {
                                            _showRowDetailsModal(context, groupTaxes[index]);
                                          }
                                        },
                                        cells: [
                                          DataCell(
                                            Text(
                                              groupTaxes[index].name ?? '',
                                              style: GoogleFonts.poppins(
                                                color: kGreyTextColor,
                                              ),
                                            ),
                                          ),
                                          DataCell(
                                            Center(
                                              child: Text(
                                                groupTaxes[index].rate.toString(),
                                                style: GoogleFonts.poppins(
                                                  color: kGreyTextColor,
                                                ),
                                              ),
                                            ),
                                          ),
                                          DataCell(
                                            Center(
                                              child: Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                decoration: BoxDecoration(
                                                  color: (groupTaxes[index].status ?? false) ? Colors.green.withValues(alpha: 0.2) : Colors.red.withValues(alpha: 0.2),
                                                  borderRadius: BorderRadius.circular(12),
                                                ),
                                                child: Text(
                                                  (groupTaxes[index].status ?? false) ? lang.active : lang.inActive,
                                                  style: GoogleFonts.poppins(
                                                    color: (groupTaxes[index].status ?? false) ? Colors.green : Colors.red,
                                                  ),
                                                ),
                                              ),
                                            ),
                                          ),
                                          // DataCell(
                                          //   Wrap(
                                          //     children: List.generate(
                                          //       groupTaxes[index].subTax?.length ?? 0,
                                          //       (i) {
                                          //         return Text(
                                          //           (i > 0 ? ' + ' : '') + (groupTaxes[index].subTax?[i].name.toString() ?? ''),
                                          //           style: GoogleFonts.poppins(
                                          //             color: kGreyTextColor,
                                          //           ),
                                          //         );
                                          //       },
                                          //     ),
                                          //   ),
                                          // ),
                                          DataCell(
                                            Row(
                                              children: [
                                                IconButton(
                                                  onPressed: () => Navigator.push(
                                                    context,
                                                    MaterialPageRoute(
                                                      builder: (context) => AddGroupTax(taxModel: groupTaxes[index]),
                                                    ),
                                                  ),
                                                  icon: const Icon(
                                                    IconlyLight.edit_square,
                                                    size: 20,
                                                    color: kMainColor,
                                                  ),
                                                ),
                                                IconButton(
                                                  onPressed: () async {
                                                    bool result = await showDeleteAlert(context: context, itemsName: 'Tax');
                                                    if (result) {
                                                      EasyLoading.show(status: lang.deleting);
                                                      final repo = TaxRepo();
                                                      try {
                                                        final result = await repo.deleteTax(id: groupTaxes[index].id.toString());
                                                        if (result) {
                                                          ref.refresh(taxProvider);
                                                          EasyLoading.showSuccess(lang.deletedSuccessfully);
                                                        } else {
                                                          EasyLoading.showError(lang.failedToDeleteThisTax);
                                                        }
                                                      } catch (e) {
                                                        EasyLoading.showError('Error deleting tax: $e');
                                                      } finally {
                                                        EasyLoading.dismiss();
                                                      }
                                                    }
                                                  },
                                                  icon: const Icon(
                                                    Icons.delete_outline,
                                                    size: 20,
                                                    color: Colors.red,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                        color: WidgetStateColor.resolveWith(
                                          (Set<WidgetState> states) {
                                            return index % 2 != 0 ? kTextFiledBorder.withValues(alpha: 0.1) : Colors.white;
                                          },
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                },
                error: (e, stack) {
                  return Center(
                    child: Text(e.toString()),
                  );
                },
                loading: () {
                  return const Center(
                    child: CircularProgressIndicator(),
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}
