import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/svg.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';

import '../../app_config/api_config.dart';
import '../widget/image_picker_dialog_widget.dart';
import 'Model/parties_model.dart';
import 'Repo/parties_repo.dart';

class AddParty extends StatefulWidget {
  const AddParty({super.key, this.customerModel});
  final PartyModel? customerModel;

  @override
  // ignore: library_private_types_in_public_api
  _AddPartyState createState() => _AddPartyState();
}

class _AddPartyState extends State<AddParty> {
  String groupValue = 'Retailer';
  bool expanded = false;
  final ImagePicker _picker = ImagePicker();
  bool showProgress = false;
  XFile? pickedImage;

  TextEditingController phoneController = TextEditingController();
  TextEditingController nameController = TextEditingController();
  TextEditingController emailController = TextEditingController();
  TextEditingController dueController = TextEditingController();
  TextEditingController addressController = TextEditingController();

  final GlobalKey<FormState> _formKay = GlobalKey();
  FocusNode focusNode = FocusNode();

  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    if (widget.customerModel != null) {
      phoneController.text = widget.customerModel?.phone ?? '';
      nameController.text = widget.customerModel?.name ?? '';
      emailController.text = widget.customerModel?.email ?? '';
      dueController.text = (widget.customerModel?.due ?? 0).toString();
      addressController.text = widget.customerModel?.address ?? '';
      groupValue = widget.customerModel?.type ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer(builder: (context, ref, __) {
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: Text(
            widget.customerModel != null ? lang.S.of(context).editParties : lang.S.of(context).addParties,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: kWhite,
                  fontSize: 20,
                  fontWeight: FontWeight.w600,
                ),
          ),
          centerTitle: true,
          iconTheme: const IconThemeData(color: kWhite),
          elevation: 0.0,
        ),
        body: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.only(left: 24, right: 24, top: 24, bottom: 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Form(
                    key: _formKay,
                    child: Column(
                      children: [
                        ///_________Phone_______________________
                        TextFormField(
                          controller: phoneController,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return lang.S.of(context).pleaseEnterAValidPhoneNumber;
                            }
                            return null;
                          },
                          keyboardType: TextInputType.phone,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly, // Only digits are allowed
                          ],
                          decoration: InputDecoration(
                            floatingLabelBehavior: FloatingLabelBehavior.always,
                            labelText: lang.S.of(context).phone,
                            hintText: lang.S.of(context).enterYourPhoneNumber,
                            border: const OutlineInputBorder(),
                          ),
                        ),
                        // IntlPhoneField(
                        //   decoration: InputDecoration(
                        //     labelText: lang.S.of(context).phoneNumber,
                        //     border: const OutlineInputBorder(
                        //       borderSide: BorderSide(),
                        //     ),
                        //   ),
                        //   initialCountryCode: 'BD',
                        //   onChanged: (phone) {
                        //     phoneNumber = phone.completeNumber;
                        //   },
                        //   disableLengthCheck: true,
                        // ),
                        SizedBox(height: 20),

                        ///_________Name_______________________
                        TextFormField(
                          controller: nameController,
                          keyboardType: TextInputType.name,
                          decoration: InputDecoration(
                            floatingLabelBehavior: FloatingLabelBehavior.always,
                            labelText: lang.S.of(context).name,
                            hintText: lang.S.of(context).enterYourName,
                            border: const OutlineInputBorder(),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  ///_______Type___________________________
                  Text(
                    lang.S.of(context).selectContactType,
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                  const SizedBox(height: 16),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.start,
                    children: [
                      buildRadioTile(
                        title: lang.S.of(context).retailer,
                        value: 'Retailer',
                        groupValue: groupValue,
                        onChanged: (value) {
                          setState(() {
                            groupValue = value.toString();
                          });
                        },
                      ),
                      buildRadioTile(
                        title: lang.S.of(context).wholesaler,
                        value: 'Wholesaler',
                        groupValue: groupValue,
                        onChanged: (value) {
                          setState(() {
                            groupValue = value.toString();
                          });
                        },
                      ),
                    ],
                  ),
                  Row(
                    children: [
                      buildRadioTile(
                        title: lang.S.of(context).supplier,
                        value: 'Supplier',
                        groupValue: groupValue,
                        onChanged: (value) {
                          setState(() {
                            groupValue = value.toString();
                          });
                        },
                      ),
                    ],
                  ),

                  SizedBox(height: 29),
                  Visibility(
                    visible: showProgress,
                    child: const CircularProgressIndicator(
                      color: kMainColor,
                      strokeWidth: 5.0,
                    ),
                  ),
                  ExpansionPanelList(
                    expandIconColor: Colors.transparent,
                    expandedHeaderPadding: EdgeInsets.zero,
                    expansionCallback: (int index, bool isExpanded) {
                      setState(() {
                        expanded = !expanded;
                      });
                    },
                    animationDuration: const Duration(milliseconds: 600),
                    elevation: 0,
                    dividerColor: Colors.white,
                    children: [
                      ExpansionPanel(
                        backgroundColor: kWhite,
                        headerBuilder: (BuildContext context, bool isExpanded) {
                          return Padding(
                            padding: const EdgeInsets.only(left: 70.0),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  lang.S.of(context).moreInfo,
                                  textAlign: TextAlign.center,
                                  style: GoogleFonts.poppins(fontSize: 20.0, color: kMainColor),
                                ),
                                const SizedBox(width: 4),
                                Icon(
                                  isExpanded ? Icons.expand_less : Icons.expand_more,
                                  size: 30.0,
                                  color: kMainColor,
                                ),
                              ],
                            ),
                          );
                        },
                        body: Column(
                          children: [
                            SizedBox(height: 25),
                            GestureDetector(
                              onTap: () {
                                showDialog(
                                  context: context,
                                  builder: (BuildContext context) {
                                    return ImagePickerDialog(
                                      onCameraTap: () async {
                                        pickedImage = await _picker.pickImage(source: ImageSource.camera);
                                        setState(() {});
                                        Navigator.pop(context);
                                      },
                                      onGalleryTap: () async {
                                        pickedImage = await _picker.pickImage(source: ImageSource.gallery);
                                        setState(() {});
                                        Navigator.pop(context);
                                      },
                                    );
                                  },
                                );
                              },
                              child: Stack(
                                children: [
                                  widget.customerModel?.image == null
                                      ? Container(
                                          height: 100,
                                          width: 100,
                                          decoration: BoxDecoration(
                                              shape: BoxShape.circle,
                                              image: pickedImage == null
                                                  ? const DecorationImage(
                                                      image: AssetImage('images/nAvatar.png'),
                                                      fit: BoxFit.cover,
                                                    )
                                                  : DecorationImage(
                                                      image: FileImage(File(pickedImage!.path)),
                                                      fit: BoxFit.cover,
                                                    )),
                                        )
                                      : Container(
                                          height: 100,
                                          width: 100,
                                          decoration: BoxDecoration(
                                            shape: BoxShape.circle,
                                            image: pickedImage == null
                                                ? DecorationImage(
                                                    image: NetworkImage('${APIConfig.domain}${widget.customerModel?.image}'),
                                                    fit: BoxFit.cover,
                                                  )
                                                : DecorationImage(
                                                    image: FileImage(File(pickedImage!.path)),
                                                    fit: BoxFit.cover,
                                                  ),
                                          ),
                                        ),
                                  Positioned(
                                    bottom: 0,
                                    right: 0,
                                    child: Container(
                                      height: 35,
                                      width: 35,
                                      decoration: BoxDecoration(
                                        border: Border.all(color: Colors.white, width: 2),
                                        borderRadius: const BorderRadius.all(Radius.circular(120)),
                                        color: kMainColor,
                                      ),
                                      child: const Icon(Icons.camera_alt_outlined, size: 20, color: Colors.white),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            SizedBox(height: 30),
                            TextFormField(
                              controller: emailController,
                              decoration: InputDecoration(
                                border: const OutlineInputBorder(),
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                labelText: lang.S.of(context).email,
                                hintText: lang.S.of(context).hintEmail,
                              ),
                            ),
                            const SizedBox(height: 20),
                            TextFormField(
                              controller: addressController,
                              decoration: InputDecoration(
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                labelText: lang.S.of(context).address,
                                hintText: lang.S.of(context).hintEmail,
                              ),
                            ),
                            const SizedBox(height: 20),
                            TextFormField(
                              controller: dueController,
                              inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                labelText: lang.S.of(context).previousDue,
                                hintText: lang.S.of(context).amount,
                              ),
                            ),
                          ],
                        ),
                        isExpanded: expanded,
                        canTapOnHeader: true,
                      ),
                    ],
                  )
                ],
              ),
            ),
          ),
        ),
        bottomNavigationBar: Container(
          margin: EdgeInsets.all(24),
          child: Padding(
            padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
            child: NewPrimaryButton(
              buttonText: lang.S.of(context).save,
              onPressed: () async {
                EasyLoading.show();
                final partyRepo = PartyRepository();

                if (widget.customerModel != null) {
                  await partyRepo.updateParty(
                    id: widget.customerModel?.id.toString() ?? '',
                    ref: ref,
                    context: context,
                    name: nameController.text,
                    phone: phoneController.text,
                    type: groupValue,
                    image: pickedImage != null ? File(pickedImage!.path) : null,
                    email: emailController.text,
                    address: addressController.text,
                    due: dueController.text,
                  );
                } else {
                  await partyRepo.addParty(
                      ref: ref,
                      context: context,
                      name: nameController.text,
                      // phone: phoneNumber ?? '',
                      phone: phoneController.text ?? '',
                      type: groupValue,
                      image: pickedImage != null ? File(pickedImage!.path) : null,
                      address: addressController.text.isEmptyOrNull ? null : addressController.text,
                      email: emailController.text.isEmptyOrNull ? null : emailController.text,
                      due: dueController.text.isEmptyOrNull ? '0' : dueController.text);
                }

                EasyLoading.dismiss();
              },
            ),
          ),
        ),
      );
    });
  }

  Widget buildRadioTile({
    required String title,
    required String value,
    required String groupValue,
    required ValueChanged<String?> onChanged,
  }) {
    return Expanded(
      child: ListTile(
        onTap: () => onChanged(value),
        contentPadding: EdgeInsets.zero, // Remove all internal padding
        visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
        leading: Radio<String>(
          value: value,
          groupValue: groupValue,
          onChanged: onChanged,
          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
          visualDensity: VisualDensity(horizontal: -4, vertical: -4),
        ),
        title: Text(
          title,
          maxLines: 1,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kNutral800),
        ),
      ),
    );
  }
}
