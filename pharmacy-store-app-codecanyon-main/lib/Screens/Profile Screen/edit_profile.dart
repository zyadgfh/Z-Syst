import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/svg.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../Provider/profile_provider.dart';
import '../../Provider/shop_category_provider.dart';
import '../../Repository/API/business_info_update_repo.dart';
import '../../constant.dart';
import '../../model/business_category_model.dart';
import '../../model/business_info_model.dart';
import '../widget/image_picker_dialog_widget.dart';

class EditProfile extends StatefulWidget {
  const EditProfile({super.key, required this.profile, required this.ref});

  final BusinessInformation profile;
  final WidgetRef ref;

  @override
  State<EditProfile> createState() => _EditProfileState();
}

class _EditProfileState extends State<EditProfile> {
  // Controllers
  TextEditingController addressController = TextEditingController();
  TextEditingController phoneController = TextEditingController();
  TextEditingController nameController = TextEditingController();
  List<BusinessCategoryModel>? categories;

  @override
  void initState() {
    // TODO: implement initState
    super.initState();

    nameController.text = widget.profile.companyName ?? '';
    phoneController.text = widget.profile.phoneNumber ?? '';
    addressController.text = widget.profile.address ?? '';
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (categories != null && categories!.isNotEmpty) {
        selectedBusinessCategory = categories?.firstWhere(
          (element) => element.id == widget.profile.category?.id,
        );
        setState(() {});
      }
    });
  }

  // Business Category
  BusinessCategoryModel? selectedBusinessCategory;

  final ImagePicker _picker = ImagePicker();
  XFile? pickedImage;

  // Form Key
  final GlobalKey<FormState> _formKey = GlobalKey();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        iconTheme: const IconThemeData(color: Colors.white),
        title: Text(
          l.S.of(context).editProfile,
          style: theme.textTheme.titleLarge?.copyWith(color: Colors.white),
        ),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0.0,
      ),
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.all(16.0),
        child: NewPrimaryButton(
          buttonText: lang.update,
          onPressed: () async {
            if (_formKey.currentState!.validate()) {
              final businessRepository = BusinessUpdateRepository();
              final isProfileUpdated = await businessRepository.updateProfile(
                  id: widget.profile.id.toString(),
                  name: nameController.text,
                  categoryId: selectedBusinessCategory!.id.toString(),
                  address: addressController.text,
                  image: pickedImage != null ? File(pickedImage!.path) : null,
                  phone: phoneController.text);

              if (isProfileUpdated) {
                widget.ref.refresh(businessInfoProvider);
                EasyLoading.showSuccess(
                  l.S.of(context).dataSavedSuccessfully,
                  //'Data saved successfully.'
                );
                Navigator.pop(context);
              } else {
                EasyLoading.showError(
                  l.S.of(context).somethingIs,
                  // 'Something is '
                );
              }
            }
          },
        ),
      ),
      body: SingleChildScrollView(
        child: Consumer(builder: (context, ref, child) {
          final categoryList = ref.watch(businessCategoryProvider);
          return categoryList.when(data: (data) {
            categories = data;
            return Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      lang.profilePictureOptional,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.titleMedium,
                    ),
                    SizedBox(height: 16),

                    // Profile Picture
                    Center(
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
                            },
                          );
                        },
                        child: Stack(
                          children: [
                            widget.profile.pictureUrl == null
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
                                              image: NetworkImage('${APIConfig.domain}${widget.profile.pictureUrl}'),
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
                    ),
                    const SizedBox(height: 24.0),

                    // Business Category
                    DropdownButtonFormField<BusinessCategoryModel>(
                      decoration: InputDecoration(
                        fillColor: Colors.white,
                        floatingLabelBehavior: FloatingLabelBehavior.always,
                        labelText: lang.businessCategory,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(5.0),
                        ),
                      ),
                      hint: Text(lang.selectBusinessCategory),
                      initialValue: selectedBusinessCategory,
                      style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                      items: data.map((BusinessCategoryModel category) {
                        return DropdownMenuItem<BusinessCategoryModel>(
                          value: category,
                          child: Text(
                            category.name,
                            style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                          ),
                        );
                      }).toList(),
                      onChanged: (BusinessCategoryModel? value) {
                        setState(() {
                          selectedBusinessCategory = value;
                        });
                      },
                    ),
                    const SizedBox(height: 26),

                    // Company & Business Name
                    TextFormField(
                      controller: nameController,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          //return 'Please enter a valid business name';
                          return l.S.of(context).pleaseEnterAValidBusinessName;
                        }
                        return null;
                      },
                      decoration: InputDecoration(
                        labelText: l.S.of(context).businessName,
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 26),

                    // Phone Number
                    TextFormField(
                      controller: phoneController,
                      validator: (value) {
                        return null;
                      },
                      keyboardType: TextInputType.phone,
                      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                      decoration: InputDecoration(
                        labelText: l.S.of(context).phone,
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 26),

                    // Address
                    TextFormField(
                      controller: addressController,
                      validator: (value) {
                        return null;
                      },
                      decoration: InputDecoration(
                        labelText: l.S.of(context).address,
                        border: const OutlineInputBorder(),
                      ),
                    ),
                  ],
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
      ),
    );
  }
}
