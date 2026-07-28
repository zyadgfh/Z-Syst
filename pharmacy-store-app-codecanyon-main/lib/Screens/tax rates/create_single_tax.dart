import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/tax%20rates/model/tax_model.dart';
import 'package:mobile_pos/Screens/tax%20rates/repo/tax_repo.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;

class CreateSingleTax extends StatefulWidget {
  const CreateSingleTax({super.key, this.taxModel});

  final TaxModel? taxModel;

  @override
  State<CreateSingleTax> createState() => _CreateSingleTaxState();
}

class _CreateSingleTaxState extends State<CreateSingleTax> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController taxNameController;
  late TextEditingController taxRateController;
  bool status = true;

  @override
  void initState() {
    super.initState();
    taxNameController = TextEditingController(text: widget.taxModel?.name ?? '');
    taxRateController = TextEditingController(
      text: widget.taxModel?.rate != null ? widget.taxModel!.rate.toString() : '',
    );
    status = widget.taxModel?.status ?? true;
  }

  @override
  void dispose() {
    taxNameController.dispose();
    taxRateController.dispose();
    super.dispose();
  }

  void _saveTax({required BuildContext context, required WidgetRef ref}) async {
    if (_formKey.currentState!.validate()) {
      EasyLoading.show();
      TaxRepo repo = TaxRepo();

      if (widget.taxModel == null) {
        await repo.createSingleTax(ref: ref, context: context, taxRate: num.tryParse(taxRateController.text) ?? 0, taxName: taxNameController.text, status: status);
      } else {
        await repo.updateSingleTax(
            ref: ref, context: context, rate: num.tryParse(taxRateController.text) ?? 0, name: taxNameController.text, id: widget.taxModel!.id!, status: status);
      }
      EasyLoading.dismiss();
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    return Scaffold(
      backgroundColor: kMainColor,
      appBar: AppBar(
        title: Text(
          widget.taxModel == null ? lang.addTax : lang.editTax,
          style: GoogleFonts.poppins(color: Colors.white),
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
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.taxModel == null ? lang.addNewTax : lang.editTax,
                style: TextStyle(color: kTitleColor, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 20.0),
              // Tax Name Field
              Text(
                '${l.S.of(context).name}*',
                style: TextStyle(color: kTitleColor),
              ),
              const SizedBox(height: 8.0),
              TextFormField(
                controller: taxNameController,
                keyboardType: TextInputType.text,
                decoration: InputDecoration(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 8.0),
                  border: const OutlineInputBorder(),
                  hintText: l.S.of(context).enterName,
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return lang.taxNameIsRequired;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20.0),
              // Tax Rate Field
              Text(
                '${lang.taxRates}*',
                style: TextStyle(color: kTitleColor),
              ),
              const SizedBox(height: 8.0),
              TextFormField(
                controller: taxRateController,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  contentPadding: EdgeInsets.symmetric(horizontal: 8.0),
                  border: OutlineInputBorder(),
                  hintText: lang.enterTaxRate,
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return lang.taxRateIsRequired;
                  }
                  if (double.tryParse(value.trim()) == null) {
                    return lang.enterAValidNumber;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20.0),
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
              const Spacer(),
              // Save Button
              Consumer(builder: (context, ref, __) {
                return Padding(
                  padding: const EdgeInsets.all(10.0),
                  child: SizedBox(
                    height: 45.0,
                    width: MediaQuery.of(context).size.width,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 2),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(30.0),
                        ),
                        backgroundColor: kMainColor,
                        elevation: 1.0,
                        shadowColor: kMainColor,
                        animationDuration: const Duration(milliseconds: 300),
                      ),
                      onPressed: () {
                        _saveTax(context: context, ref: ref);
                      },
                      child: Text(
                        lang.save,
                        style: TextStyle(
                          color: kWhite,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                );
              }),
            ],
          ),
        ),
      ),
    );
  }
}
