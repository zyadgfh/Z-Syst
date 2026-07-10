// ignore_for_file: unused_result
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Products/box/repo/box_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'model/box_model.dart';

class AddBoxScreen extends StatefulWidget {
  const AddBoxScreen({super.key, this.box});
  final BoxSizeModel? box;
  @override
  // ignore: library_private_types_in_public_api
  _AddBoxScreenState createState() => _AddBoxScreenState();
}

class _AddBoxScreenState extends State<AddBoxScreen> {
  bool showProgress = false;
  TextEditingController boxSizeController = TextEditingController();
  final GlobalKey<FormState> _key = GlobalKey();

  @override
  void initState() {
    // TODO: implement initState
    super.initState();

    if (widget.box != null) {
      boxSizeController.text = widget.box?.name ?? '';
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
            widget.box == null ? lang.addLeafOrBoxSize : lang.editLeafOrBoxSize,
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
                  controller: boxSizeController,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return lang.enterAValidBoxSize;
                    }
                    return null;
                  },
                  decoration: InputDecoration(
                    border: const OutlineInputBorder(),
                    hintText: lang.pleaseEnterLeafOrBoxSize,
                    floatingLabelBehavior: FloatingLabelBehavior.always,
                    labelText: lang.leafOrBoxSize,
                  ),
                ),
              ),
              SizedBox(height: 16),
              NewPrimaryButton(
                buttonText: l.S.of(context).save,
                onPressed: () async {
                  if (_key.currentState!.validate()) {
                    BoxRepo box = BoxRepo();

                    if ((widget.box == null)) {
                      await box.addBox(ref: ref, context: context, boxSize: boxSizeController.text);
                    } else {
                      await box.editBox(ref: ref, id: widget.box?.id ?? 0, context: context, name: boxSizeController.text);
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
