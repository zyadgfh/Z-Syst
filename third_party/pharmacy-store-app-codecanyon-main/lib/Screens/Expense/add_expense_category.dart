// ignore_for_file: unused_result
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Expense/Repo/expanse_category_repo.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:nb_utils/nb_utils.dart';
import '../widget/acnoo_scafold.dart';
import 'Model/expanse_category.dart';
import 'Providers/expense_category_proivder.dart';

class AddExpenseCategory extends StatefulWidget {
  const AddExpenseCategory({super.key, this.catModel});
  final ExpenseCategoryModel? catModel;
  @override
  _AddExpenseCategoryState createState() => _AddExpenseCategoryState();
}

class _AddExpenseCategoryState extends State<AddExpenseCategory> {
  bool showProgress = false;

  TextEditingController nameController = TextEditingController();
  GlobalKey<FormState> key = GlobalKey();

  @override
  void initState() {
    super.initState();

    if (widget.catModel != null) {
      nameController.text = widget.catModel?.categoryName.toString() ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final _theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(builder: (context, ref, __) {
      //final allCategory = ref.watch(expanseCategoryProvider);
      return AcnooScafoldWidget(
        appBar: AppBar(
          title: Text(
            widget.catModel == null ? l.S.of(context).addExpenseCat : lang.editExpenseCategory,
            style: _theme.textTheme.titleMedium?.copyWith(color: Colors.white),
          ),
          iconTheme: const IconThemeData(color: Colors.white),
          centerTitle: true,
          backgroundColor: Colors.transparent,
          elevation: 0.0,
        ),
        body: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Visibility(
                  visible: showProgress,
                  child: const CircularProgressIndicator(
                    color: kMainColor,
                    strokeWidth: 5.0,
                  ),
                ),
                Form(
                  key: key,
                  child: TextFormField(
                    validator: (value) {
                      if (value?.trim().isEmptyOrNull ?? true) {
                        //return 'Enter expanse category name';
                        return l.S.of(context).enterExpanseCategoryName;
                      }
                      return null;
                    },
                    controller: nameController,
                    decoration: InputDecoration(
                      border: const OutlineInputBorder(),
                      hintText: lang.enterExpenseName,
                      floatingLabelBehavior: FloatingLabelBehavior.always,
                      labelText: l.S.of(context).categoryName,
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    minimumSize: Size(double.maxFinite, 46),
                    backgroundColor: _theme.colorScheme.primary,
                  ),
                  onPressed: () async {
                    if (key.currentState?.validate() ?? false) {
                      EasyLoading.show(status: '${lang.saving}...');
                      final categoryRepo = ExpanseCategoryRepo();

                      try {
                        if (widget.catModel != null) {
                          // Update category
                          await categoryRepo.updateExpanseCategory(
                            name: nameController.text,
                            id: widget.catModel?.id.toString() ?? '',
                          );
                        } else {
                          // Add new  category
                          await categoryRepo.addExpanseCategory(
                            ref: ref,
                            context: context,
                            categoryName: nameController.text.trim(),
                          );
                        }
                        ref.refresh(expanseCategoryProvider);
                        EasyLoading.showSuccess(lang.savedSuccessFully);
                        Navigator.pop(context);
                      } catch (e) {
                        EasyLoading.showError('Failed to save: $e');
                      } finally {
                        EasyLoading.dismiss();
                      }
                    }
                  },
                  child: Text(
                    l.S.of(context).save,
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    });
  }
}
