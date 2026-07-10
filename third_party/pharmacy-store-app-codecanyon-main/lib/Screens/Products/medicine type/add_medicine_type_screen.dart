// ignore_for_file: unused_result

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/repo/medicine_type_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'model/medicine_type_model.dart';

class AddMedicineTypeScreen extends StatefulWidget {
  const AddMedicineTypeScreen({super.key, this.medicineType, required this.fromDropdown});
  final MedicineTypeModel? medicineType;
  final bool fromDropdown;
  @override
  // ignore: library_private_types_in_public_api
  _AddMedicineTypeScreenState createState() => _AddMedicineTypeScreenState();
}

class _AddMedicineTypeScreenState extends State<AddMedicineTypeScreen> {
  bool showProgress = false;
  TextEditingController medicineTypeController = TextEditingController();
  final GlobalKey<FormState> _key = GlobalKey();

  @override
  void initState() {
    // TODO: implement initState
    super.initState();

    if (widget.medicineType != null) {
      medicineTypeController.text = widget.medicineType?.name ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
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
            widget.medicineType == null ? lang.addMedicineType : lang.editMedicineType,
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
                  controller: medicineTypeController,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return lang.pleaseEnterAValidMedicineType;
                    }
                    return null;
                  },
                  decoration: InputDecoration(
                    border: const OutlineInputBorder(),
                    hintText: lang.pleaseEnterMedicineType,
                    floatingLabelBehavior: FloatingLabelBehavior.always,
                    labelText: lang.medicineType,
                  ),
                ),
              ),
              SizedBox(height: 16),
              NewPrimaryButton(
                buttonText: l.S.of(context).save,
                onPressed: () async {
                  if (_key.currentState!.validate()) {
                    if ((widget.medicineType == null)) {
                      await MedicineTypeRepo().addMedicineType(ref: ref, context: context, medicineType: medicineTypeController.text, fromDropdown: widget.fromDropdown);
                    } else {
                      await MedicineTypeRepo().editMedicineType(ref: ref, id: widget.medicineType?.id ?? 0, context: context, name: medicineTypeController.text);
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
