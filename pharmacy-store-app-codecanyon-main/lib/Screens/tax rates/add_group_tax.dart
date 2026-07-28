import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/tax%20rates/provider/text_repo.dart';
import 'package:mobile_pos/Screens/tax%20rates/model/tax_model.dart';
import 'package:mobile_pos/Screens/tax%20rates/repo/tax_repo.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:mobile_pos/constant.dart';
import 'package:skeletonizer/skeletonizer.dart';

class AddGroupTax extends ConsumerStatefulWidget {
  const AddGroupTax({
    super.key,
    this.taxModel,
  });

  final TaxModel? taxModel;

  @override
  AddTaxGroupState createState() => AddTaxGroupState();
}

class AddTaxGroupState extends ConsumerState<AddGroupTax> {
  List<TaxModel> subTaxList = [];

  TextEditingController nameController = TextEditingController();
  bool status = true;

  final GlobalKey<FormState> _fromKey = GlobalKey<FormState>();

  void _saveTax({required BuildContext context, required WidgetRef ref}) async {
    if (_fromKey.currentState!.validate()) {
      if (subTaxList.isNotEmpty) {
        EasyLoading.show();
        TaxRepo repo = TaxRepo();
        List<num> ids = [];
        for (var element in subTaxList) {
          ids.add(element.id!);
        }
        if (widget.taxModel != null) {
          await repo.updateGroupTax(id: widget.taxModel!.id!, ref: ref, context: context, taxName: nameController.text, taxIds: ids, status: status);
        } else {
          print(status);
          await repo.createGroupTax(ref: ref, context: context, taxName: nameController.text, taxIds: ids, status: status);
        }
        EasyLoading.dismiss();

        Navigator.pop(context);
      } else {
        EasyLoading.showError(l.S.current.pleaseSelectTaxes);
      }
    }
  }

  @override
  void initState() {
    super.initState();

    if (widget.taxModel != null) {
      nameController.text = widget.taxModel?.name ?? '';
      status = widget.taxModel?.status ?? false;

      if (widget.taxModel!.subTax != null) {
        subTaxList = widget.taxModel!.subTax!
            .map((subTax) => TaxModel(
                  id: subTax.id,
                  name: subTax.name,
                  rate: subTax.rate,
                ))
            .toList();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    return Scaffold(
      backgroundColor: kMainColor,
      appBar: AppBar(
        title: Text(
          widget.taxModel == null ? lang.addTaxGroup : lang.editTaxGroup,
          style: GoogleFonts.poppins(
            color: Colors.white,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
        backgroundColor: kMainColor,
        elevation: 0.0,
      ),
      body: Container(
        padding: const EdgeInsets.all(15),
        width: MediaQuery.of(context).size.width,
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.only(
            topRight: Radius.circular(30),
            topLeft: Radius.circular(30),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            //___________________________________Tax Rates______________________________
            Text(lang.addNewTaxWithSingle, style: TextStyle(color: kTitleColor, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10.0),
            Text('${l.S.of(context).name}*', style: TextStyle(color: kTitleColor)),
            const SizedBox(height: 8.0),
            Form(
              key: _fromKey,
              child: TextFormField(
                controller: nameController,
                keyboardType: TextInputType.text,
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return lang.taxNameIsRequired;
                  }
                  return null;
                },
                decoration: InputDecoration(
                  contentPadding: EdgeInsets.only(left: 8, right: 8.0),
                  border: OutlineInputBorder(),
                  // hintText: 'Enter Name',
                  hintText: l.S.of(context).enterName,
                ),
              ),
            ),
            const SizedBox(height: 20.0),
            Text('${lang.subTaxes}*', style: TextStyle(color: kTitleColor)),
            const SizedBox(height: 8.0),
            Consumer(builder: (context, ref, __) {
              final taxes = ref.watch(singleTaxProvider);
              return taxes.when(
                data: (taxes) {
                  return GestureDetector(
                    onTap: () async {
                      subTaxList = await getTaxesModalSheet(
                        mainContext: context,
                        ref: ref,
                        taxList: taxes,
                        oldList: subTaxList, // Pass the current subTaxList
                      );
                      setState(() {});
                    },
                    child: Container(
                      padding: const EdgeInsets.only(left: 10),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(4.0),
                        color: Colors.transparent,
                        border: Border.all(color: kBorderColorTextField),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          subTaxList.isNotEmpty
                              ? Expanded(
                                  child: SingleChildScrollView(
                                    scrollDirection: Axis.horizontal,
                                    child: Wrap(
                                      children: List.generate(
                                        subTaxList.length,
                                        (index) {
                                          final category = subTaxList[index];
                                          return Padding(
                                            padding: const EdgeInsets.only(right: 5.0),
                                            child: Container(
                                              height: 30,
                                              decoration: BoxDecoration(
                                                borderRadius: BorderRadius.circular(4.0),
                                                color: kMainColor,
                                              ),
                                              child: Row(
                                                children: [
                                                  IconButton(
                                                    visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
                                                    padding: EdgeInsets.zero,
                                                    onPressed: () {
                                                      setState(() {
                                                        subTaxList.removeAt(index);
                                                      });
                                                    },
                                                    icon: const Icon(
                                                      Icons.close,
                                                      color: kWhite,
                                                      size: 16,
                                                    ),
                                                  ),
                                                  Text(
                                                    category.name ?? '',
                                                    style: TextStyle(color: kWhite),
                                                  ),
                                                  const SizedBox(width: 8)
                                                ],
                                              ),
                                            ),
                                          );
                                        },
                                      ),
                                    ),
                                  ),
                                )
                              : Text(lang.noSubTaxSelected, style: TextStyle(color: kTitleColor)),
                          const Padding(
                            padding: EdgeInsets.all(11.0),
                            child: Icon(
                              Icons.keyboard_arrow_down_rounded,
                              color: kGreyTextColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
                error: (error, stackTrace) {
                  return Text(error.toString());
                },
                loading: () => Skeletonizer(
                  enabled: true,
                  child: Container(
                    padding: const EdgeInsets.only(left: 10),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(4.0),
                      color: Colors.transparent,
                      border: Border.all(color: kBorderColorTextField),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(lang.noSubTaxSelected, style: TextStyle(color: kTitleColor)),
                        const Padding(
                          padding: EdgeInsets.all(11.0),
                          child: Icon(
                            Icons.keyboard_arrow_down_rounded,
                            color: kGreyTextColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            }),
            const SizedBox(height: 20.0),

            if (widget.taxModel != null)
              Row(
                children: [
                  Text(
                    lang.status,
                    style: TextStyle(color: kTitleColor),
                  ),
                  const SizedBox(width: 8.0),
                  Switch(
                    value: status,
                    onChanged: (value) {
                      setState(() {
                        status = value;
                      });
                    },
                  )
                ],
              ),

            //___________________________________________save_button______________________
            const Spacer(),
            Padding(
              padding: const EdgeInsets.all(10.0),
              child: SizedBox(
                height: 45.0,
                width: MediaQuery.of(context).size.width,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.only(left: 2, right: 2),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30.0),
                    ),
                    backgroundColor: kMainColor,
                    elevation: 1.0,
                    foregroundColor: kGreyTextColor.withValues(alpha: 0.1),
                    shadowColor: kMainColor,
                    animationDuration: const Duration(milliseconds: 300),
                    textStyle: const TextStyle(color: Colors.white, fontFamily: 'Display', fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  onPressed: () => _saveTax(ref: ref, context: context),
                  child: Text(
                    l.S.of(context).save,
                    style: TextStyle(color: kWhite, fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

Future<List<TaxModel>> getTaxesModalSheet({
  required BuildContext mainContext,
  required WidgetRef ref,
  required List<TaxModel> taxList,
  required List<TaxModel> oldList,
}) async {
  List<TaxModel> subTaxList = [...oldList];

  bool? isDone = await showModalBottomSheet(
    isScrollControlled: true,
    useSafeArea: true,
    context: mainContext,
    builder: (BuildContext context) {
      final lang = l.S.of(context);
      return StatefulBuilder(
        builder: (BuildContext context, StateSetter setNewState) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20.0, 13.0, 0.0, 0.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(lang.subTaxList, style: TextStyle(color: kTitleColor)),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(
                        Icons.close_rounded,
                        size: 21,
                        color: kTitleColor,
                      ),
                      padding: EdgeInsets.zero,
                    ),
                  ],
                ),
              ),
              const Divider(
                color: kBorderColorTextField,
              ),
              const SizedBox(height: 5),
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.fromLTRB(20.0, 5.0, 20.0, 10.0),
                  itemCount: taxList.length,
                  itemBuilder: (context, index) {
                    final category = taxList[index];
                    return Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        CheckboxListTile(
                          contentPadding: EdgeInsets.zero,
                          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          checkboxShape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(50.0),
                          ),
                          checkColor: Colors.white,
                          activeColor: kMainColor,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(6.0),
                          ),
                          fillColor: WidgetStateProperty.all(
                            subTaxList.any((tax) => tax.id == category.id) ? kMainColor : Colors.transparent,
                          ),
                          visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
                          side: const BorderSide(color: kBorderColorTextField),
                          title: Text(category.name.toString(), style: TextStyle(color: kTitleColor, overflow: TextOverflow.ellipsis)),
                          subtitle: Text('${lang.taxPercent}: ${category.rate}%', style: TextStyle(color: kGreyTextColor)),
                          value: subTaxList.any((tax) => tax.id == category.id),
                          onChanged: (isChecked) {
                            setNewState(() {
                              if (isChecked!) {
                                if (!subTaxList.any((tax) => tax.id == category.id)) {
                                  subTaxList.add(category);
                                }
                              } else {
                                subTaxList.removeWhere((tax) => tax.id == category.id);
                              }
                            });
                          },
                        ),
                        const Divider(
                          color: kBorderColorTextField,
                          height: 0.0,
                        )
                      ],
                    );
                  },
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(10.0),
                child: SizedBox(
                  height: 45.0,
                  width: MediaQuery.of(context).size.width,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.only(left: 2, right: 2),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(30.0),
                      ),
                      backgroundColor: kMainColor,
                      elevation: 1.0,
                      foregroundColor: kGreyTextColor.withValues(alpha: 0.1),
                      shadowColor: kMainColor,
                      animationDuration: const Duration(milliseconds: 300),
                      textStyle: const TextStyle(color: Colors.white, fontFamily: 'Display', fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    onPressed: () {
                      Navigator.pop(context, true);
                    },
                    child: Text(lang.done, style: TextStyle(color: kWhite, fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ),
              ),
            ],
          );
        },
      );
    },
  );
  return (isDone ?? false) ? subTaxList : oldList;
}
