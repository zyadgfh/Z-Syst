import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Expense/Model/expanse_category.dart';
import 'package:mobile_pos/Screens/Expense/expense_category_list.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import '../../constant.dart';
import '../Products/add product/drop_down-function.dart';
import '../widget/acnoo_scafold.dart';
import 'Model/expense_modle.dart';
import 'Providers/expense_category_proivder.dart';
import 'Repo/expanse_repo.dart';

class AddExpense extends StatefulWidget {
  const AddExpense({
    super.key,
    this.expenseList,
    this.pagingController,
  });
  final ExpenseData? expenseList;
  final PagingController? pagingController;
  @override
  _AddExpenseState createState() => _AddExpenseState();
}

class _AddExpenseState extends State<AddExpense> {
  ExpenseCategoryModel? selectedCategory;
  final dateController = TextEditingController();
  TextEditingController expanseTitleController = TextEditingController();
  TextEditingController expanseAmountController = TextEditingController();
  TextEditingController expanseNoteController = TextEditingController();
  TextEditingController expanseRefController = TextEditingController();
  List<String> paymentMethods = [];

  List<String> _getPaymentMethod(BuildContext context) {
    return [
      l10n.cash,
      l10n.bank,
      l10n.card,
      l10n.mobilePayment,
      l10n.due,
    ];
  }

  String? selectedPaymentType;
  List<ExpenseCategoryModel>? categoryList;
  DropdownButton<String> getPaymentMethods() {
    List<DropdownMenuItem<String>> dropDownItems = [];
    for (String des in paymentMethods) {
      var item = DropdownMenuItem(
        value: des,
        child: Text(des),
      );
      dropDownItems.add(item);
    }
    return DropdownButton(
      items: dropDownItems,
      value: selectedPaymentType,
      onChanged: (value) {
        setState(() {
          selectedPaymentType = value!;
        });
      },
    );
  }

  @override
  void initState() {
    super.initState();
    if (widget.expenseList != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (categoryList != null && categoryList!.isNotEmpty) {
          selectedCategory = categoryList?.firstWhere(
            (element) => element.id == widget.expenseList?.category?.id,
          );
          setState(() {});
        }
      });
      expanseTitleController.text = widget.expenseList?.expanseFor.toString() ?? '';
      dateController.text = widget.expenseList?.expenseDate.toString() ?? '';
      expanseAmountController.text = widget.expenseList?.amount.toString() ?? '';
      selectedPaymentType = widget.expenseList?.paymentType.toString() ?? '';
      expanseRefController.text = widget.expenseList?.referenceNo.toString() ?? '';
      expanseNoteController.text = widget.expenseList?.note.toString() ?? '';
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

  final PagingController<int, ExpenseData> pageController = PagingController(firstPageKey: 1);
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final paymentMethods = _getPaymentMethod(context);
    selectedPaymentType ??= paymentMethods.first;
    final l10n = lang.S.of(context);
    return Consumer(builder: (context, ref, __) {
      final data = ref.watch(expanseCategoryProvider);
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: Text(
            widget.expenseList != null ? lang.editExpense : l10n.addExpense,
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
              padding: const EdgeInsets.all(20.0),
              child: Form(
                key: formKey,
                child: Column(
                  children: [
                    // Title
                    TextFormField(
                      showCursor: true,
                      controller: expanseTitleController,
                      validator: (value) {
                        if (value.isEmptyOrNull) {
                          return l10n.pleaseEnterName;
                        }
                        return null;
                      },
                      onSaved: (value) {
                        expanseTitleController.text = value!;
                      },
                      decoration: InputDecoration(
                        floatingLabelBehavior: FloatingLabelBehavior.always,
                        labelText: l10n.expenseFor,
                        hintText: l10n.enterName,
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Category
                    data.when(
                      data: (dataList) {
                        categoryList = dataList ?? [];
                        return TextFormField(
                          showCursor: false,
                          readOnly: true,
                          controller: TextEditingController(
                            text: selectedCategory?.categoryName ?? lang.selectACategory,
                          ),
                          onTap: () async {
                            selectedCategory = await const ExpenseCategoryList().launch(context);
                            setState(() {}); // Rebuild the widget after selecting category
                          },
                          decoration: InputDecoration(
                            floatingLabelBehavior: FloatingLabelBehavior.always,
                            labelText: lang.incomeCategory,
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
                        labelText: lang.incomeDate,
                        hintText: lang.enterIncomeDate,
                        suffixIcon: Icon(Icons.calendar_month, color: Colors.grey),
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Amount
                    TextFormField(
                      showCursor: true,
                      controller: expanseAmountController,
                      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                      validator: (value) {
                        if (value.isEmptyOrNull) {
                          //return 'Please Enter Amount';
                          return l10n.pleaseEnterAmount;
                        }
                        return null;
                      },
                      onSaved: (value) {
                        expanseAmountController.text = value!;
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

                    // Payment type
                    DropdownButtonFormField<String>(
                      hint: Text(lang.selectOne),
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
                      controller: expanseRefController,
                      validator: (value) {
                        return null;
                      },
                      onSaved: (value) {
                        expanseRefController.text = value!;
                      },
                      decoration: InputDecoration(
                        border: const OutlineInputBorder(),
                        labelText: l10n.referenceNo,
                        floatingLabelBehavior: FloatingLabelBehavior.always,
                        hintText: l10n.enterRefNumber,
                      ),
                    ),
                    const SizedBox(height: 20),

                    ///_________note____________________________________________________
                    TextFormField(
                      showCursor: true,
                      controller: expanseNoteController,
                      validator: (value) {
                        if (value == null) {
                          //return 'please Inter Amount';
                          return l10n.pleaseEnterAmount;
                        }
                        return null;
                      },
                      onSaved: (value) {
                        expanseNoteController.text = value!;
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
                ),
              ),
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
                  minimumSize: Size(double.maxFinite, 46),
                  backgroundColor: theme.colorScheme.primary,
                ),
                onPressed: () async {
                  if (validateAndSave()) {
                    if (selectedCategory != null) {
                      EasyLoading.show();
                      ExpenseRepo repo = ExpenseRepo();

                      try {
                        if (widget.expenseList != null) {
                          // Update expense
                          await repo.updateExpense(
                            id: widget.expenseList?.id.toString() ?? '',
                            amount: num.tryParse(expanseAmountController.text) ?? 0,
                            expenseCategoryId: selectedCategory?.id ?? 0,
                            expanseFor: expanseTitleController.text,
                            paymentType: selectedPaymentType!,
                            referenceNo: expanseRefController.text,
                            expenseDate: selectedDate.toString(),
                            note: expanseNoteController.text,
                          );
                        } else {
                          // Add new expense
                          await repo.createExpense(
                            ref: ref,
                            context: context,
                            amount: num.tryParse(expanseAmountController.text) ?? 0,
                            expenseCategoryId: selectedCategory?.id ?? 0,
                            expanseFor: expanseTitleController.text,
                            paymentType: selectedPaymentType!,
                            referenceNo: expanseRefController.text,
                            expenseDate: selectedDate.toString(),
                            note: expanseNoteController.text,
                          );
                        }
                        // ref.refresh(expenseProvider);
                        widget.pagingController?.refresh();
                        EasyLoading.showSuccess(lang.savedSuccessFully);
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
                child: Text(lang.save),
              ),
            ],
          ),
        ),
      );
    });
  }
}
