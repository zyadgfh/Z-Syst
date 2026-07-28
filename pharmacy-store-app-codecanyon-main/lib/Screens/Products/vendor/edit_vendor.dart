// ignore_for_file: unused_result

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/category/model/category_model.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../category/repo/category_repo.dart';

class EditVendor extends StatefulWidget {
  const EditVendor({super.key, required this.categoryModel});

  final ProductCategoryModel categoryModel;

  @override
  _AddCategoryState createState() => _AddCategoryState();
}

class _AddCategoryState extends State<EditVendor> {
  bool showProgress = false;
  late String categoryName;
  bool sizeCheckbox = false;
  bool colorCheckbox = false;
  bool weightCheckbox = false;
  bool capacityCheckbox = false;
  bool typeCheckbox = false;
  TextEditingController categoryNameController = TextEditingController();
  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    categoryNameController.text = widget.categoryModel.categoryName ?? '';
  }

  @override
  Widget build(BuildContext context) {
    return Consumer(builder: (context, ref, __) {
      final theme=Theme.of(context);
      return AcnooScafoldWidget(
        appBar: AppBar(
          leading: IconButton(
              onPressed: () {
                Navigator.pop(context);
              },
              icon: Icon(Icons.close)),
          title: Text(
            'Edit Vendor',
            //'Add Category',
            style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600,fontSize: 20,color: kWhite),
          ),
          iconTheme: const IconThemeData(color: kWhite),
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
                  decoration:  InputDecoration(
                    border: const OutlineInputBorder(),
                    // hintText: 'Enter category name',
                    hintText: 'Enter Vendor name',
                    floatingLabelBehavior: FloatingLabelBehavior.always,
                    //labelText: 'Category name',
                    labelText: 'Vendor Name',
                  ),
                ),
                const SizedBox(height: 20),
                // Text(lang.S.of(context).selectVariations,),
                // //'Select variations : '),
                // Row(
                //   children: [
                //     Expanded(
                //       child: CheckboxListTile(
                //         title:  Text(
                //           lang.S.of(context).size,
                //           // "Size",
                //           overflow: TextOverflow.ellipsis,
                //         ),
                //         value: sizeCheckbox,
                //         checkboxShape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(50))),
                //         onChanged: (newValue) {
                //           setState(() {
                //             sizeCheckbox = newValue!;
                //           });
                //         },
                //         controlAffinity: ListTileControlAffinity.leading, //  <-- leading Checkbox
                //       ),
                //     ),
                //     Expanded(
                //       child: CheckboxListTile(
                //         title:  Text(
                //           lang.S.of(context).color,
                //           //"Color",
                //           overflow: TextOverflow.ellipsis,
                //         ),
                //         value: colorCheckbox,
                //         checkboxShape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(50))),
                //         onChanged: (newValue) {
                //           setState(() {
                //             colorCheckbox = newValue!;
                //           });
                //         },
                //         controlAffinity: ListTileControlAffinity.leading, //  <-- leading Checkbox
                //       ),
                //     ),
                //   ],
                // ),
                // Row(
                //   children: [
                //     Expanded(
                //       child: CheckboxListTile(
                //         title:  Text(
                //           lang.S.of(context).weight,
                //           // "Weight",
                //           overflow: TextOverflow.ellipsis,
                //         ),
                //         checkboxShape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(50))),
                //         value: weightCheckbox,
                //         onChanged: (newValue) {
                //           setState(() {
                //             weightCheckbox = newValue!;
                //           });
                //         },
                //         controlAffinity: ListTileControlAffinity.leading, //  <-- leading Checkbox
                //       ),
                //     ),
                //     Expanded(
                //       child: CheckboxListTile(
                //         title:  Text(
                //           lang.S.of(context).capacity,
                //           // "Capacity",
                //           overflow: TextOverflow.ellipsis,
                //         ),
                //         checkboxShape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(50))),
                //         value: capacityCheckbox,
                //         onChanged: (newValue) {
                //           setState(() {
                //             capacityCheckbox = newValue!;
                //           });
                //         },
                //         controlAffinity: ListTileControlAffinity.leading, //  <-- leading Checkbox
                //       ),
                //     ),
                //   ],
                // ),
                // CheckboxListTile(
                //   title:  Text(
                //     lang.S.of(context).type,
                //     //"Type",
                //     overflow: TextOverflow.ellipsis,
                //   ),
                //   checkboxShape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(50))),
                //   value: typeCheckbox,
                //   onChanged: (newValue) {
                //     setState(() {
                //       typeCheckbox = newValue!;
                //     });
                //   },
                //   controlAffinity: ListTileControlAffinity.leading, //  <-- leading Checkbox
                // ),
                NewPrimaryButton(
                  //buttontext: 'Save',
                  buttonText: lang.S.of(context).save,
                  onPressed: () async {
                    setState(() {
                      showProgress = true;
                    });
                    final categoryRepo = CategoryRepo();
                    await categoryRepo.editCategory(
                      id: widget.categoryModel.id ?? 0,
                      ref: ref,
                      context: context,
                      name: categoryNameController.text,
                      // variationSize: sizeCheckbox,
                      // variationColor: colorCheckbox,
                      // variationCapacity: capacityCheckbox,
                      // variationType: typeCheckbox,
                      // variationWeight: weightCheckbox,
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
