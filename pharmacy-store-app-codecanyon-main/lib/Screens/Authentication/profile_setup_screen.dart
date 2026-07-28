import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import '../../Provider/shop_category_provider.dart';
import '../../Repository/API/business_setup_repo.dart';
import '../../constant.dart';
import '../../model/business_category_model.dart';
import '../../model/lalnguage_model.dart';
import '../widget/image_picker_dialog_widget.dart';

class ProfileSetup extends StatefulWidget {
  const ProfileSetup({super.key});

  @override
  State<ProfileSetup> createState() => _ProfileSetupState();
}

class _ProfileSetupState extends State<ProfileSetup> {
  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    // _loadLanguages();
  }

  // Language? selectedLanguage;
  BusinessCategoryModel? selectedBusinessCategory;
  List<Language> language = [];

  final ImagePicker _picker = ImagePicker();
  XFile? pickedImage;
  TextEditingController addressController = TextEditingController();
  TextEditingController openingBalanceController = TextEditingController();
  TextEditingController phoneController = TextEditingController();
  TextEditingController nameController = TextEditingController();

  DropdownButton<BusinessCategoryModel> getCategory({required List<BusinessCategoryModel> list}) {
    List<DropdownMenuItem<BusinessCategoryModel>> dropDownItems = [];

    for (BusinessCategoryModel category in list) {
      var item = DropdownMenuItem(
        value: category,
        child: Text(category.name),
      );
      dropDownItems.add(item);
    }
    return DropdownButton(
      hint: Text(lang.S.of(context).selectBusinessCategory
          //'Select Business Category'
          ),
      items: dropDownItems,
      value: selectedBusinessCategory,
      onChanged: (value) {
        setState(() {
          selectedBusinessCategory = value!;
        });
      },
    );
  }

  final GlobalKey<FormState> _formKey = GlobalKey();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return PopScope(
      canPop: false,
      child: Consumer(builder: (context, ref, __) {
        final businessCategoryList = ref.watch(businessCategoryProvider);
        return businessCategoryList.when(data: (categoryList) {
          return AcnooScafoldWidget(
            appBar: AppBar(
              backgroundColor: Colors.transparent,
              iconTheme: const IconThemeData(color: kWhite),
              title: Text(
                lang.S.of(context).setUpProfile,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(color: kWhite, fontWeight: FontWeight.w600, fontSize: 20),
              ),
              centerTitle: true,
              // backgroundColor: Colors.white,
              elevation: 0.0,
            ),
            body: SingleChildScrollView(
              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 20),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      lang.S.of(context).profilePictureOptional,
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500),
                    ),
                    SizedBox(height: 16),
                    Center(
                      child: Stack(
                        children: [
                          Container(
                            height: 120,
                            width: 120,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              // border: Border.all(color: Colors.black54, width: 1),
                              // borderRadius: const BorderRadius.all(Radius.circular(120)),
                              image: pickedImage == null
                                  ? const DecorationImage(
                                      image: AssetImage('images/nAvatar.png'),
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
                            child: GestureDetector(
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
                                    });
                              },
                              child: Container(
                                height: 35,
                                width: 35,
                                decoration: BoxDecoration(
                                  border: Border.all(color: Colors.white, width: 2),
                                  // borderRadius: const BorderRadius.all(Radius.circular(120)),
                                  shape: BoxShape.circle,
                                  color: kMainColor,
                                ),
                                child: const Icon(
                                  Icons.camera_alt_outlined,
                                  size: 20,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                          )
                        ],
                      ),
                    ),
                    const SizedBox(height: 28.0),
                    FormField(
                      builder: (FormFieldState<dynamic> field) {
                        return InputDecorator(
                          decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              labelText: lang.S.of(context).businessCat,
                              labelStyle: GoogleFonts.poppins(
                                color: Colors.black,
                                fontSize: 20.0,
                              ),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(5.0))),
                          child: DropdownButtonHideUnderline(child: getCategory(list: categoryList)),
                        );
                      },
                    ),
                    SizedBox.square(dimension: 20),

                    ///_________Name________________________
                    AppTextField(
                      // Optional
                      textFieldType: TextFieldType.NAME,
                      controller: nameController,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          // return 'Please enter a valid business name';
                          return lang.S.of(context).pleaseEnterAValidBusinessName;
                        }
                        return null;
                      },
                      decoration: InputDecoration(
                        labelText: lang.S.of(context).businessName,
                        border: const OutlineInputBorder(),
                        //hintText: 'Enter Business/Store Name'
                        hintText: lang.S.of(context).enterBusiness,
                      ),
                    ),
                    SizedBox.square(dimension: 20),

                    ///__________Phone_________________________
                    AppTextField(
                      controller: phoneController,
                      validator: (value) {
                        return null;
                      },
                      textFieldType: TextFieldType.PHONE,
                      decoration: InputDecoration(
                        labelText: lang.S.of(context).phone,
                        hintText: lang.S.of(context).enterYourPhoneNumber,
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    SizedBox.square(dimension: 20),

                    ///_________Address___________________________
                    AppTextField(
                      // ignore: deprecated_member_use
                      textFieldType: TextFieldType.ADDRESS,
                      controller: addressController,
                      decoration: InputDecoration(
                        focusedBorder: const OutlineInputBorder(
                          borderSide: BorderSide(color: kGreyTextColor),
                        ),
                        labelText: lang.S.of(context).companyAddress,
                        hintText: lang.S.of(context).enterFullAddress,
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    SizedBox.square(dimension: 20),

                    ///________Opening_balance_______________________
                    AppTextField(
                      validator: (value) {
                        return null;
                      },
                      controller: openingBalanceController, // Optional
                      textFieldType: TextFieldType.PHONE,
                      decoration: InputDecoration(
                        //hintText: 'Enter opening balance',
                        hintText: lang.S.of(context).enterOpeningBalance,
                        labelText: lang.S.of(context).openingBalance,
                        border: const OutlineInputBorder(),
                      ),
                    ),

                    ///_________Language___________________________
                    // Padding(
                    //   padding: const EdgeInsets.all(10.0),
                    //   child: SizedBox(
                    //     height: 60.0,
                    //     child: FormField(
                    //       builder: (FormFieldState<dynamic> field) {
                    //         return InputDecorator(
                    //           decoration: InputDecoration(
                    //               floatingLabelBehavior: FloatingLabelBehavior.always,
                    //               labelText: lang.S.of(context).language,
                    //               labelStyle: GoogleFonts.poppins(
                    //                 color: Colors.black,
                    //                 fontSize: 20.0,
                    //               ),
                    //               border: OutlineInputBorder(borderRadius: BorderRadius.circular(5.0))),
                    //           child: DropdownButtonHideUnderline(child: getLanguage()),
                    //         );
                    //       },
                    //     ),
                    //   ),
                    // ),
                  ],
                ),
              ),
            ),
            bottomNavigationBar: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
              child: NewPrimaryButton(
                onPressed: () async {
                  if (selectedBusinessCategory != null) {
                    if (_formKey.currentState!.validate()) {
                      try {
                        BusinessSetupRepo businessSetupRepo = BusinessSetupRepo();
                        await businessSetupRepo.businessSetup(
                          context: context,
                          name: nameController.text,
                          phone: phoneController.text,
                          address: addressController.text.isEmptyOrNull ? null : addressController.text,
                          categoryId: selectedBusinessCategory!.id.toString(),

                          image: pickedImage == null ? null : File(pickedImage!.path),

                          // languageCode: selectedLanguage!.code,
                          openingBalance: openingBalanceController.text.isEmptyOrNull ? null : openingBalanceController.text,
                          // phone: phoneController.text,
                        );
                      } catch (e) {
                        EasyLoading.dismiss();
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
                      }
                    }
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Select a Business Category')));
                  }

                  // Navigator.pushNamed(context, '/otp');
                },
                buttonText: lang.S.of(context).continueE,
              ),
            ),
          );
        }, error: (e, stack) {
          return Center(
            child: Text(e.toString()),
          );
        }, loading: () {
          return const Center(
            child: CircularProgressIndicator(),
          );
        });
      }),
    );
  }
}
