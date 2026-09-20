import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Income/Model/income_category.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';

import '../../constant.dart';
import '../Products/add product/drop_down-function.dart';
import 'Model/income_model.dart';
import 'Providers/income_category_provider.dart';
import 'Repo/income_repo.dart';
import 'income_category_list.dart';

class AddIncome extends StatefulWidget {
  const AddIncome({super.key, this.income, this.pagingController});
  final IncomeData? income;
  final PagingController? pagingController;

  @override
  State<AddIncome> createState() => _AddIncomeState();
}

class _AddIncomeState extends State<AddIncome> {
  IncomeCategory? selectedCategory;
  TextEditingController incomeTitleController = TextEditingController();
  TextEditingController incomeAmountController = TextEditingController();
  TextEditingController incomeNoteController = TextEditingController();
  TextEditingController incomeRefController = TextEditingController();

  List<String> paymentMethods = [];
  List<String> _getPaymentMethod(BuildContext context) {
    return [
      lang.S.of(context).cash,
      lang.S.of(context).bank,
      lang.S.of(context).card,
      lang.S.of(context).mobilePayment,
      lang.S.of(context).due,
    ];
  }

  String? selectedPaymentType;

  List<IncomeCategory>? categoryList;

  @override
  void initState() {
    super.initState();
    if (widget.income != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (categoryList != null && categoryList!.isNotEmpty) {
          selectedCategory = categoryList?.firstWhere(
            (element) => element.id == widget.income?.category?.id,
          );
          setState(() {});
        }
      });
      incomeTitleController.text = widget.income?.incomeFor.toString() ?? '';
      incomeAmountController.text = widget.income?.amount.toString() ?? '';
      selectedPaymentType = widget.income?.paymentType.toString() ?? '';
      incomeRefController.text = widget.income?.referenceNo.toString() ?? '';
      incomeNoteController.text = widget.income?.note.toString() ?? '';
      selectedDate = DateTime.tryParse(widget.income?.incomeDate ?? '') ?? DateTime.now();
    }
  }

  DateTime selectedDate = DateTime.now();

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: selectedDate,
      firstDate: DateTime(2015, 1, 1),
      lastDate: DateTime(2101),
    );
    if (picked != null && picked != selectedDate) {
      setState(() {
        selectedDate = picked;
        print(selectedDate);
      });
    }
  }

  GlobalKey<FormState> formKey = GlobalKey<FormState>();

  bool validateAndSave() {
    final form = formKey.currentState;
    if (form!.validate()) {
      form.save();
      return true;
    }
    return false;
  }

  @override
  Widget build(BuildContext context) {
    final paymentMethods = _getPaymentMethod(context);
    selectedPaymentType ??= paymentMethods.first;
    print(paymentMethods);
    final theme = Theme.of(context);
    final l10n = lang.S.of(context);
    return Consumer(
      builder: (context, ref, __) {
        final data = ref.watch(incomeCategoryProvider);
        return AcnooScafoldWidget(
          // backgroundColor: _theme,
          appBar: AppBar(
            backgroundColor: Colors.transparent,
            title: Text(
              widget.income != null ? l10n.editIncome : l10n.createIncome,
              style: theme.textTheme.titleLarge?.copyWith(color: Colors.white),
            ),
            centerTitle: true,
            iconTheme: const IconThemeData(color: Colors.white),
            elevation: 0.0,
          ),
          body: SingleChildScrollView(
            child: SizedBox(
              width: context.width(),
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Form(
                    key: formKey,
                    child: Column(
                      children: [
                        // Title
                        TextFormField(
                          showCursor: true,
                          controller: incomeTitleController,
                          validator: (value) {
                            if (value.isEmptyOrNull) {
                              //return 'Please Enter Name';
                              return l10n.pleaseEnterName;
                            }
                            return null;
                          },
                          onSaved: (value) {
                            incomeTitleController.text = value!;
                          },
                          decoration: InputDecoration(
                            floatingLabelBehavior: FloatingLabelBehavior.always,
                            // border: const OutlineInputBorder(),
                            labelText: l10n.incomeTitle,
                            hintText: l10n.enterName,
                          ),
                        ),
                        const SizedBox(height: 20),

                        //Category
                        data.when(
                          data: (dataList) {
                            categoryList = dataList ?? [];
                            return TextFormField(
                              showCursor: false,
                              readOnly: true,
                              controller: TextEditingController(
                                text: selectedCategory?.categoryName ?? l10n.selectACategory,
                              ),
                              onTap: () async {
                                selectedCategory = await const IncomeCategoryList().launch(context);
                                setState(() {}); // Rebuild the widget after selecting category
                              },
                              decoration: InputDecoration(
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                labelText: l10n.incomeCategory,
                                suffixIcon: const Icon(Icons.keyboard_arrow_down, color: kGreyTextColor),
                              ),
                            );
                          },
                          error: (error, stackTrace) {
                            return Text(error.toString());
                          },
                          loading: () {
                            return const DropdownSkeletonWidget();
                          },
                        ),
                        const SizedBox(height: 20),

                        // Date
                        TextFormField(
                          controller: TextEditingController(
                            text: DateFormat('d MMM y').format(selectedDate),
                          ),
                          readOnly: true,
                          onTap: () => _selectDate(context),
                          decoration: InputDecoration(
                            labelText: l10n.incomeDate,
                            hintText: l10n.enterIncomeDate,
                            suffixIcon: Icon(Icons.calendar_month, color: Colors.grey),
                            border: OutlineInputBorder(),
                          ),
                        ),
                        const SizedBox(height: 20),

                        // Amount
                        TextFormField(
                          showCursor: true,
                          controller: incomeAmountController,
                          inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                          validator: (value) {
                            if (value.isEmptyOrNull) {
                              //return 'Please Enter Amount';
                              return l10n.pleaseEnterAmount;
                            }
                            return null;
                          },
                          onSaved: (value) {
                            incomeAmountController.text = value!;
                          },
                          decoration: InputDecoration(
                            border: const OutlineInputBorder(),
                            errorBorder: const OutlineInputBorder(
                              borderSide: BorderSide(color: Colors.red),
                            ),
                            labelText: l10n.amount,
                            floatingLabelBehavior: FloatingLabelBehavior.always,
                            hintText: l10n.enterAmount,
                          ),
                          keyboardType: TextInputType.number,
                        ),
                        const SizedBox(height: 20),

                        // Payment Method
                        DropdownButtonFormField<String>(
                          hint: Text(l10n.selectOne),
                          value: selectedPaymentType,
                          onChanged: (value) {
                            setState(() {
                              selectedPaymentType = value!;
                            });
                          },
                          icon: const Icon(Icons.keyboard_arrow_down, color: kGreyTextColor),
                          decoration: InputDecoration(
                            labelText: l10n.paymentTypes,
                            border: OutlineInputBorder(),
                          ),
                          items: paymentMethods.map((String des) {
                            return DropdownMenuItem<String>(
                              value: des,
                              child: Text(
                                des,
                                style: theme.textTheme.bodyMedium,
                              ),
                            );
                          }).toList(),
                        ),
                        const SizedBox(height: 20),

                        // Reference
                        TextFormField(
                          showCursor: true,
                          controller: incomeRefController,
                          validator: (value) {
                            return null;
                          },
                          onSaved: (value) {
                            incomeRefController.text = value!;
                          },
                          decoration: InputDecoration(
                            border: const OutlineInputBorder(),
                            labelText: l10n.referenceNo,
                            floatingLabelBehavior: FloatingLabelBehavior.always,
                            hintText: l10n.enterRefNumber,
                          ),
                        ),
                        const SizedBox(height: 20),

                        // Note
                        TextFormField(
                          showCursor: true,
                          controller: incomeNoteController,
                          validator: (value) {
                            if (value == null) {
                              //return 'please Inter Amount';
                              return l10n.pleaseEnterAmount;
                            }
                            return null;
                          },
                          onSaved: (value) {
                            incomeNoteController.text = value!;
                          },
                          maxLines: 4,
                          decoration: InputDecoration(
                            border: const OutlineInputBorder(),
                            labelText: l10n.note,
                            contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                            hintText: l10n.enterNote,
                          ),
                        ),
                      ],
                    )),
              ),
            ),
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
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                      minimumSize: Size(
                        double.maxFinite,
                        46,
                      ),
                      backgroundColor: theme.colorScheme.primary),
                  onPressed: () async {
                    if (validateAndSave()) {
                      if (selectedCategory != null) {
                        EasyLoading.show();
                        IncomeRepo repo = IncomeRepo();

                        try {
                          if (widget.income != null) {
                            // Update existing income
                            await repo.updateIncome(
                              id: widget.income?.id.toString() ?? '',
                              amount: num.tryParse(incomeAmountController.text) ?? 0,
                              incomeCatId: selectedCategory?.id ?? 0,
                              expanseFor: incomeTitleController.text,
                              paymentType: selectedPaymentType!,
                              referenceNo: incomeRefController.text,
                              incomeDate: selectedDate.toString(),
                              note: incomeNoteController.text,
                            );
                          } else {
                            // Add new income
                            await repo.createIncome(
                              ref: ref,
                              context: context,
                              amount: num.tryParse(incomeAmountController.text) ?? 0,
                              expenseCategoryId: selectedCategory?.id ?? 0,
                              expanseFor: incomeTitleController.text,
                              paymentType: selectedPaymentType!,
                              referenceNo: incomeRefController.text,
                              expenseDate: selectedDate.toString(),
                              note: incomeNoteController.text,
                            );
                          }
                          widget.pagingController?.refresh();
                          EasyLoading.showSuccess(l10n.savedSuccessFully);
                          Navigator.pop(context);
                        } catch (e) {
                          EasyLoading.showError('Failed to save: $e');
                        } finally {
                          EasyLoading.dismiss();
                        }
                      } else {
                        EasyLoading.showError(
                          l10n.pleaseSelectAExpenseCategory,
                          //'Please select a expense category'
                        );
                      }
                    }
                  },
                  child: Text(l10n.save),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
