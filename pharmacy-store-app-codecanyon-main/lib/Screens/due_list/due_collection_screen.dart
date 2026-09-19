// ignore_for_file: unused_result
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/currency.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:nb_utils/nb_utils.dart';
import '../../Provider/profile_provider.dart';
import '../../constant.dart';
import '../Customers/Provider/customer_provider.dart';
import '../widget/primary_button.dart';
import 'Model/due_invoice_list_model.dart';
import 'Providers/due_provider.dart';
import 'Repo/due_repo.dart';

class DueCollectionScreen extends StatefulWidget {
  const DueCollectionScreen({
    super.key,
    this.id,
    required this.controller,
  });
  final PagingController controller;
  @override
  State<DueCollectionScreen> createState() => _DueCollectionScreenState();
  final num? id;
}

class _DueCollectionScreenState extends State<DueCollectionScreen> {
  num paidAmount = 0;
  num remainDueAmount = 0;
  num dueAmount = 0;
  num openingBal = 0;
  String? errorMessage;

  num calculateDueAmount({
    required num total,
  }) {
    if (total < 0) {
      remainDueAmount = 0;
    } else {
      remainDueAmount = (dueAmount - total);
    }
    return dueAmount - total;
  }

  TextEditingController paidText = TextEditingController();
  TextEditingController dateController = TextEditingController(text: DateFormat('yyyy-MM-dd').format(DateTime.now()));

  SalesDues? selectedInvoice;
  String paymentType = 'Cash';
  bool isClicked = false;
  GlobalKey<FormState> key = GlobalKey();
  int selectedIndex = -1;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(builder: (context, consumerRef, __) {
      final dueInvoiceData = consumerRef.watch(allDueInvListProvider(widget.id ?? 0));
      return dueInvoiceData.when(
        data: (snapShot) {
          dueAmount = selectedInvoice == null ? snapShot.data.openingBalance ?? 0 : selectedInvoice?.dueAmount ?? 0;
          return AcnooScafoldWidget(
            appBar: AppBar(
              backgroundColor: Colors.transparent,
              title: Text(
                l.S.of(context).collectDue,
                style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600),
              ),
              centerTitle: true,
              iconTheme: const IconThemeData(color: Colors.white),
              elevation: 0.0,
            ),
            body: SingleChildScrollView(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Form(
                  key: key,
                  child: Column(
                    children: [
                      // Invoice & Date
                      Row(
                        children: [
                          Expanded(
                            child: DropdownButtonFormField<SalesDues>(
                              icon: selectedInvoice != null
                                  ? IconButton(
                                      padding: EdgeInsets.zero,
                                      visualDensity: VisualDensity(horizontal: -4),
                                      icon: const Icon(
                                        Icons.close,
                                        color: Colors.grey,
                                        size: 18,
                                      ),
                                      onPressed: () {
                                        setState(() {
                                          selectedInvoice = null;
                                          dueAmount = 0;
                                          paidAmount = 0;
                                          paidText.clear();
                                          errorMessage = null;
                                        });
                                      },
                                    )
                                  : null,
                              decoration: InputDecoration(
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                labelText: lang.invoice,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(5.0),
                                ),
                              ),
                              hint: Text(
                                lang.selectInvoice,
                                softWrap: true,
                                overflow: TextOverflow.ellipsis,
                                maxLines: 1,
                              ),
                              value: selectedInvoice,
                              style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                              items: snapShot.data.salesDues.map((SalesDues category) {
                                return DropdownMenuItem<SalesDues>(
                                  value: category,
                                  child: Text(
                                    category.invoiceNumber.toString(),
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                                  ),
                                );
                              }).toList(),
                              onChanged: (SalesDues? value) {
                                setState(() {
                                  dueAmount = value?.dueAmount ?? 0;
                                  paidAmount = 0;
                                  paidText.clear();
                                  selectedInvoice = value;
                                  errorMessage = null;
                                });
                              },
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: AppTextField(
                              textFieldType: TextFieldType.NAME,
                              readOnly: true,
                              controller: dateController,
                              decoration: InputDecoration(
                                labelText: l.S.of(context).date,
                                suffixIcon: IconButton(
                                  onPressed: () async {
                                    final DateTime? picked = await showDatePicker(
                                      initialDate: DateTime.now(),
                                      firstDate: DateTime(2015, 8),
                                      lastDate: DateTime(2101),
                                      context: context,
                                    );
                                    if (picked != null) {
                                      setState(() {
                                        dateController.text = DateFormat('yyyy-MM-dd').format(picked);
                                      });
                                    }
                                  },
                                  icon: Icon(
                                    IconlyLight.calendar,
                                    color: kNutral700,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          // total Due Amount
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              Text(
                                '${lang.totalDueAmount}: ',
                                style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w500, color: kNutral700),
                              ),
                              Text(
                                '$currency ${selectedInvoice == null ? snapShot.data.due.toString() ?? '0.0' : selectedInvoice?.dueAmount.toString() ?? ''}',
                                style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w500, color: kSubTitleColor),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),

                          // Customer name
                          AppTextField(
                            textFieldType: TextFieldType.NAME,
                            readOnly: true,
                            value: snapShot.data.name.toString() ?? 'n/a',
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              labelText: l.S.of(context).customerName,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Total section
                      Container(
                        decoration: BoxDecoration(
                          borderRadius: const BorderRadius.all(Radius.circular(10)),
                          border: Border.all(color: Colors.grey.shade300, width: 1),
                        ),
                        child: Column(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: const BoxDecoration(
                                color: Color(0xffE7F7EF),
                                borderRadius: BorderRadius.only(
                                  topRight: Radius.circular(10),
                                  topLeft: Radius.circular(10),
                                ),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    l.S.of(context).totalAmount,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  Text(
                                    "$currency ${selectedInvoice == null ? snapShot.data.openingBalance.toString() ?? '0.0' : selectedInvoice?.dueAmount.toString() ?? ''}",
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.all(10.0),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(l.S.of(context).paidAmount, style: theme.textTheme.bodyLarge),
                                  SizedBox(
                                    width: context.width() / 4,
                                    height: 40,
                                    child: TextFormField(
                                      controller: paidText,
                                      onChanged: (value) {
                                        if (value == '') {
                                          setState(() {
                                            paidAmount = 0;
                                            errorMessage = null;
                                          });
                                        } else {
                                          num maxAllowedAmount = selectedInvoice != null ? dueAmount : snapShot.data.openingBalance ?? 0;

                                          if (double.parse(value) <= maxAllowedAmount) {
                                            setState(() {
                                              paidAmount = double.parse(value);
                                              errorMessage = null;
                                            });
                                          } else {
                                            paidText.clear();
                                            setState(() {
                                              paidAmount = 0;
                                              errorMessage = lang.cannotPayMoreThanDue;
                                            });
                                          }
                                        }
                                      },
                                      textAlign: TextAlign.right,
                                      decoration: const InputDecoration(
                                        contentPadding: EdgeInsets.only(right: 6),
                                        hintText: '0',
                                      ),
                                      keyboardType: TextInputType.number,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            if (errorMessage != null)
                              Align(
                                alignment: AlignmentDirectional.centerEnd,
                                child: Text(
                                  errorMessage!,
                                  textAlign: TextAlign.end,
                                  style: TextStyle(color: Colors.red, fontSize: 12),
                                ),
                              ),
                            Padding(
                              padding: const EdgeInsets.all(10.0),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(l.S.of(context).dueAmount, style: theme.textTheme.bodyLarge),
                                  Text(
                                    '$currency ${calculateDueAmount(total: paidAmount).toStringAsFixed(2)}',
                                    style: theme.textTheme.bodyLarge,
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 30),
                      Divider(thickness: 1, color: kOutlineBorder),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Text(l.S.of(context).paymentTypes, style: theme.textTheme.bodyLarge),
                              const SizedBox(width: 5),
                              const Icon(Icons.wallet, color: Colors.green)
                            ],
                          ),
                          DropdownButtonHideUnderline(
                            child: DropdownButton(
                              value: paymentType,
                              icon: const Icon(Icons.keyboard_arrow_down),
                              items: paymentsTypeList.map((String items) {
                                return DropdownMenuItem(
                                  value: items,
                                  child: Text(items),
                                );
                              }).toList(),
                              onChanged: (newValue) {
                                setState(() {
                                  paymentType = newValue.toString();
                                });
                              },
                            ),
                          ),
                        ],
                      ),
                      Divider(thickness: 1, color: kOutlineBorder),
                    ],
                  ),
                ),
              ),
            ),
            bottomNavigationBar: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 16),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(backgroundColor: Colors.red.withValues(alpha: 0.1), side: BorderSide.none),
                      onPressed: () {
                        Navigator.pop(context);
                      },
                      child: Text(
                        lang.cancel,
                        style: theme.textTheme.titleMedium?.copyWith(color: Colors.red),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: NewPrimaryButton(
                      onPressed: () async {
                        if (isClicked) {
                          return;
                        }
                        if (selectedInvoice == null && (snapShot.data.openingBalance ?? 0) <= 0) {
                          setState(() {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Please select an invoice or ensure there is an opening balance.'),
                              ),
                            );
                          });
                          return;
                        }

                        if (!(key.currentState?.validate() ?? false) || paidAmount <= 0 || dueAmount <= 0) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Please fill all required fields and enter valid amounts.'),
                            ),
                          );
                          return;
                        }

                        isClicked = true;
                        EasyLoading.show();

                        DueCollectModel data = DueCollectModel(
                          invoiceNumber: selectedInvoice?.invoiceNumber.toString() ?? '',
                          partyId: selectedInvoice?.partyId ?? widget.id ?? 0, // Use widget.id if selectedInvoice is null
                          payDueAmount: num.tryParse(paidAmount.toString() ?? '') ?? 0,
                          paymentDate: dateController.text,
                          paymentType: paymentType,
                        );

                        DueRepo repo = DueRepo();
                        bool success = await repo.collectDue(data: data);
                        EasyLoading.dismiss();
                        if (success) {
                          widget.controller.refresh();
                          consumerRef.refresh(allDueInvListProvider(widget.id ?? 0));
                          consumerRef.refresh(summaryInfoProvider);
                          consumerRef.refresh(partiesProvider);

                          Navigator.pop(context);
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(lang.dueCollectedSuccessfully)));
                        } else {
                          isClicked = false;
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(lang.failedToCollectDue)));
                        }
                      },
                      buttonText: lang.save,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
        error: (e, stack) {
          return Text(e.toString());
        },
        loading: () {
          return const Center(child: CircularProgressIndicator());
        },
      );
    });
  }
}

class DueCollectModel {
  DueCollectModel({
    required this.partyId,
    required this.invoiceNumber,
    required this.paymentDate,
    required this.paymentType,
    required this.payDueAmount,
  });

  num partyId;
  String? invoiceNumber;
  String? paymentDate;
  String paymentType;
  num payDueAmount;
}
