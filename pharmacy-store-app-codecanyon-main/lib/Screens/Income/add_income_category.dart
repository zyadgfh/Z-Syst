// ignore_for_file: unused_result
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Income/Repo/income_category_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import 'Model/income_category.dart';
import 'Providers/income_category_provider.dart';

class AddIncomeCategory extends StatefulWidget {
  const AddIncomeCategory({super.key, this.category});
  final IncomeCategory? category;
  @override
  // ignore: library_private_types_in_public_api
  _AddIncomeCategoryState createState() => _AddIncomeCategoryState();
}

class _AddIncomeCategoryState extends State<AddIncomeCategory> {
  bool showProgress = false;
  TextEditingController nameController = TextEditingController();
  GlobalKey<FormState> key = GlobalKey();

  @override
  void initState() {
    super.initState();

    if (widget.category != null) {
      nameController.text = widget.category?.categoryName.toString() ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = lang.S.of(context);
    return Consumer(builder: (context, ref, __) {
      //final allCategory = ref.watch(expanseCategoryProvider);
      return AcnooScafoldWidget(
        // backgroundColor: kWhite,
        appBar: AppBar(
          title: Text(
            widget.category != null ? lang.editCategory : lang.addCategory,
            style: theme.textTheme.titleMedium?.copyWith(color: Colors.white),
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
                        return lang.enterIncomeCategoryName;
                      }
                      return null;
                    },
                    controller: nameController,
                    decoration: InputDecoration(
                        border: const OutlineInputBorder(),
                        floatingLabelBehavior: FloatingLabelBehavior.always,
                        labelText: lang.S.of(context).categoryName,
                        hintText: lang.enterCategoryName),
                  ),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    minimumSize: Size(double.maxFinite, 46),
                    backgroundColor: theme.colorScheme.primary,
                  ),
                  onPressed: () async {
                    if (key.currentState?.validate() ?? false) {
                      EasyLoading.show(status: '${lang.saving}...');
                      final incomeRepo = IncomeCategoryRepo();

                      try {
                        if (widget.category != null) {
                          // Update existing income category
                          await incomeRepo.updateIncomeCategory(
                            name: nameController.text,
                            id: widget.category?.id.toString() ?? '',
                          );
                        } else {
                          // Add new income category
                          await incomeRepo.addIncomeCategory(
                            ref: ref,
                            context: context,
                            categoryName: nameController.text.trim(),
                          );
                        }
                        ref.refresh(incomeCategoryProvider);
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
                    lang.S.of(context).save,
                  ),
                )
              ],
            ),
          ),
        ),
      );
    });
  }
}
