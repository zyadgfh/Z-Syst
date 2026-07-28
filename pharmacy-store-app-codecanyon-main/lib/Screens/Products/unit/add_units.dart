// ignore_for_file: unused_result

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Products/unit/model/unit_model.dart';
import 'package:mobile_pos/Screens/Products/unit/repo/unit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

class AddUnitsScreen extends StatefulWidget {
  const AddUnitsScreen({super.key, this.unit});
  final UnitModel? unit;
  @override
  // ignore: library_private_types_in_public_api
  _AddUnitsScreenState createState() => _AddUnitsScreenState();
}

class _AddUnitsScreenState extends State<AddUnitsScreen> {
  bool showProgress = false;
  TextEditingController unitController = TextEditingController();
  final GlobalKey<FormState> _key = GlobalKey();

  @override
  void initState() {
    // TODO: implement initState
    super.initState();

    if (widget.unit != null) {
      unitController.text = widget.unit?.unitName ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer(builder: (context, ref, __) {
      return AcnooScafoldWidget(
        appBar: AppBar(
          leading: IconButton(
              onPressed: () {
                Navigator.pop(context);
              },
              icon: Icon(
                Icons.close,
                color: kWhite,
              )),
          title: Text(
            widget.unit == null ? lang.S.of(context).addUnit : lang.S.of(context).editUnit,
            style: GoogleFonts.poppins(
              color: kWhite,
              fontSize: 20.0,
            ),
          ),
          iconTheme: const IconThemeData(color: Colors.black),
          centerTitle: true,
          backgroundColor: Colors.transparent,
          elevation: 0.0,
        ),
        body: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            children: [
              Visibility(
                visible: showProgress,
                child: const CircularProgressIndicator(
                  color: kMainColor,
                  strokeWidth: 5.0,
                ),
              ),
              Form(
                key: _key,
                child: TextFormField(
                  controller: unitController,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      // return 'Please enter a valid unit name';
                      return lang.S.of(context).pleaseEnterAValidUnitName;
                    }
                    return null;
                  },
                  decoration: InputDecoration(
                    border: const OutlineInputBorder(),
                    // hintText: 'Please enter unit name',
                    hintText: lang.S.of(context).pleaseEnterUnitName,
                    floatingLabelBehavior: FloatingLabelBehavior.always,
                    labelText: lang.S.of(context).unitName,
                  ),
                ),
              ),
              SizedBox(
                height: 16,
              ),
              NewPrimaryButton(
                buttonText: lang.S.of(context).save,
                onPressed: () async {
                  if (_key.currentState!.validate()) {
                    UnitsRepo unit = UnitsRepo();

                    if ((widget.unit == null)) {
                      await unit.addUnit(ref: ref, context: context, name: unitController.text);
                    } else {
                      await unit.editUnit(ref: ref, id: widget.unit?.id ?? 0, context: context, name: unitController.text);
                    }
                  }
                },
              ),
            ],
          ),
        ),
      );
    });
  }
}
