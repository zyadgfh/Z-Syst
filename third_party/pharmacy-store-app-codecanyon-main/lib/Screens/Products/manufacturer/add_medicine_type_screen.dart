// ignore_for_file: unused_result

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/repo/manufacturer_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;

import 'model/manufacturer_model.dart';

class AddManufacturerScreen extends StatefulWidget {
  const AddManufacturerScreen({super.key, this.manufacturer, required this.fromDropdown});
  final ManufacturerModel? manufacturer;
  final bool fromDropdown;
  @override
  // ignore: library_private_types_in_public_api
  _AddManufacturerScreenState createState() => _AddManufacturerScreenState();
}

class _AddManufacturerScreenState extends State<AddManufacturerScreen> {
  bool showProgress = false;
  TextEditingController manufacturerNameController = TextEditingController();
  final GlobalKey<FormState> _key = GlobalKey();

  @override
  void initState() {
    // TODO: implement initState
    super.initState();

    if (widget.manufacturer != null) {
      manufacturerNameController.text = widget.manufacturer?.name ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer(builder: (context, ref, __) {
      final lang = l.S.of(context);
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
            widget.manufacturer == null ? lang.addManufacturer : lang.editManufacturer,
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
                  controller: manufacturerNameController,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return lang.pleaseEnterAValidManufacturerName;
                    }
                    return null;
                  },
                  decoration: InputDecoration(
                    border: const OutlineInputBorder(),
                    hintText: lang.pleaseEnterManufacturerName,
                    floatingLabelBehavior: FloatingLabelBehavior.always,
                    labelText: lang.manufacturerName,
                  ),
                ),
              ),
              SizedBox(height: 16),
              NewPrimaryButton(
                buttonText: l.S.of(context).save,
                onPressed: () async {
                  if (_key.currentState!.validate()) {
                    if ((widget.manufacturer == null)) {
                      await ManufacturerRepo().addManufacturer(ref: ref, context: context, name: manufacturerNameController.text, fromDropdown: widget.fromDropdown);
                    } else {
                      await ManufacturerRepo().editManufacturer(ref: ref, id: widget.manufacturer?.id ?? 0, context: context, name: manufacturerNameController.text);
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
