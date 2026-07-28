// ignore_for_file: unused_result

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'model/category_model.dart';
import 'repo/category_repo.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

class AddCategoryScreen extends StatefulWidget {
  const AddCategoryScreen({super.key, this.categoryModel});

  final ProductCategoryModel? categoryModel;

  @override
  _AddCategoryScreenState createState() => _AddCategoryScreenState();
}

class _AddCategoryScreenState extends State<AddCategoryScreen> {
  bool showProgress = false;
  late String categoryName;
  TextEditingController categoryNameController = TextEditingController();

  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    if (widget.categoryModel != null) {
      categoryNameController.text = widget.categoryModel?.categoryName ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Consumer(builder: (context, ref, __) {
      return AcnooScafoldWidget(
        appBar: AppBar(
          leading: IconButton(
              onPressed: () {
                Navigator.pop(context);
              },
              icon: Icon(
                Icons.close,
              )),
          title: Text(
            widget.categoryModel == null ? lang.S.of(context).addCategory : lang.S.of(context).editCategory,
            //'Add Category',
            style: theme.textTheme.titleLarge?.copyWith(color: kWhite, fontSize: 20),
          ),
          iconTheme: const IconThemeData(color: kWhite),
          centerTitle: true,
          backgroundColor: Colors.transparent,
          elevation: 0.0,
        ),
        body: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Visibility(
                  visible: showProgress,
                  child: const Padding(
                    padding: EdgeInsets.all(8.0),
                    child: CircularProgressIndicator(
                      color: kMainColor,
                      strokeWidth: 5.0,
                    ),
                  ),
                ),
                TextFormField(
                  controller: categoryNameController,
                  decoration: InputDecoration(
                    border: const OutlineInputBorder(),
                    //hintText: 'Enter category name',
                    hintText: lang.S.of(context).enterCategoryName,
                    floatingLabelBehavior: FloatingLabelBehavior.always,
                    //labelText: 'Category name',
                    labelText: lang.S.of(context).categoryName,
                  ),
                ),
                const SizedBox(height: 20),
                NewPrimaryButton(
                  buttonText: lang.S.of(context).save,
                  onPressed: widget.categoryModel != null
                      ? () async {
                          setState(() {
                            showProgress = true;
                          });
                          final categoryRepo = CategoryRepo();
                          await categoryRepo.editCategory(
                            id: widget.categoryModel?.id ?? 0,
                            ref: ref,
                            context: context,
                            name: categoryNameController.text,
                          );
                          setState(() {
                            showProgress = false;
                          });
                        }
                      : () async {
                          setState(() {
                            showProgress = true;
                          });
                          await CategoryRepo().addCategory(
                            ref: ref,
                            context: context,
                            name: categoryNameController.text,
                          );
                          setState(() {
                            showProgress = false;
                          });
                        },
                ),
              ],
            ),
          ),
        ),
      );
    });
  }
}
