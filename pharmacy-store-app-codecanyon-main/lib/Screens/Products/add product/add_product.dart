// ignore_for_file: unused_result, use_build_context_synchronously
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:image_picker/image_picker.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/Products/Providers/product_provider.dart';
import 'package:mobile_pos/Screens/Products/unit/model/unit_model.dart';
import 'package:mobile_pos/Screens/Products/Providers/category,brans,units_provide.dart';
import 'package:mobile_pos/Screens/Products/unit/add_units.dart';
import 'package:mobile_pos/Screens/Products/box/add_box.dart';
import 'package:mobile_pos/Screens/Products/box/model/box_model.dart';
import 'package:mobile_pos/Screens/Products/box/provider/box_provider.dart';
import 'package:mobile_pos/Screens/Products/category/add_category_screen.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/add_medicine_type_screen.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/model/manufacturer_model.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/provider/manufacturer_provider.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/add_medicine_type_screen.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/model/medicine_type_model.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/provider/medicine_type_provider.dart';
import 'package:mobile_pos/Screens/tax%20rates/create_single_tax.dart';
import 'package:mobile_pos/Screens/tax%20rates/model/tax_model.dart';
import 'package:mobile_pos/Screens/tax%20rates/provider/text_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../../app_config/api_config.dart';
import '../../../constant.dart';
import '../../widget/image_picker_dialog_widget.dart';
import 'model/add_product_model.dart';
import '../Model/product_model.dart';
import '../Repo/product_repo.dart';
import '../category/model/category_model.dart';
import 'drop_down-function.dart';

class AddProduct extends ConsumerStatefulWidget {
  const AddProduct({
    super.key,
    this.productModel,
    this.pagingController,
    required this.isFromHome,
  });

  final ProductModel? productModel;
  final PagingController? pagingController;
  final bool isFromHome;

  @override
  AddProductState createState() => AddProductState();
}

class AddProductState extends ConsumerState<AddProduct> {
  UnitModel? selectedUnit;
  String selectedTaxType = 'Inclusive';
  late String productName, productStock, productSalePrice, productPurchasePrice, productCode;
  TextEditingController nameController = TextEditingController();
  TextEditingController productStockController = TextEditingController();
  TextEditingController stockAlertController = TextEditingController();
  TextEditingController salePriceController = TextEditingController();
  TextEditingController purchaseWithoutTaxController = TextEditingController();
  TextEditingController purchaseWithTaxController = TextEditingController();
  TextEditingController profitPercentController = TextEditingController();
  TextEditingController productCodeController = TextEditingController();
  TextEditingController wholeSalePriceController = TextEditingController();
  TextEditingController dealerPriceController = TextEditingController();
  TextEditingController strengthController = TextEditingController();
  TextEditingController geneticController = TextEditingController();
  TextEditingController shelfController = TextEditingController();
  TextEditingController batchController = TextEditingController();
  TextEditingController medicineDetailsController = TextEditingController();
  TextEditingController fromDateTextEditingController = TextEditingController();
  String? selectedDate;

  List<XFile> pickedImage = [];
  final int maxImages = 3;
  List<String> alreadyAddedImage = [];
  List<String> removedImg = [];
  List<String> codeList = [];
  String promoCodeHint = 'Enter Product Code';
  bool isClicked = false;

  final List<File> _imageFiles = [];

  final List<String> oldImageList = [];

  Future<void> _pickImages(ImageSource source) async {
    if (source == ImageSource.camera) {
      // Pick a single image from the camera
      XFile? pickedFile = await ImagePicker().pickImage(source: source);
      if (pickedFile != null) {
        setState(() {
          if (oldImageList.length + (_imageFiles.length + 1) <= 3) {
            _imageFiles.add(File(pickedFile.path));
          } else {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Cannot add more than 3 images')));
          }
        });
      }
    } else {
      // Pick multiple images from the gallery
      List<XFile>? pickedFiles = await ImagePicker().pickMultiImage();
      setState(() {
        if ((oldImageList.length + (pickedFiles.length + _imageFiles.length) <= 3)) {
          for (var pickedFile in pickedFiles) {
            _imageFiles.add(File(pickedFile.path));
          }
        } else {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Cannot add more than 3 images')));
        }
      });
    }
  }

  @override
  void initState() {
    super.initState();

    if (widget.productModel != null) {
      alreadyAddedImage.addAll(widget.productModel?.images ?? []);
      nameController.text = widget.productModel!.productName!;
      productStockController.text = widget.productModel?.stockSum.toString() ?? '0';
      salePriceController.text = widget.productModel?.productSalePrice.toString() ?? '0';
      purchaseWithoutTaxController.text = widget.productModel?.productPurchasePriceWithoutTax.toString() ?? '0';
      purchaseWithTaxController.text = widget.productModel?.productPurchasePriceWithTax.toString() ?? '0';
      profitPercentController.text = widget.productModel?.profitPercent.toString() ?? '0';

      stockAlertController.text = widget.productModel?.alertQty.toString() ?? '';
      selectedTaxType = widget.productModel?.taxType ?? '';
      wholeSalePriceController.text = widget.productModel?.productWholeSalePrice.toString() ?? '';
      if (widget.productModel?.productCode != null) productCodeController.text = widget.productModel?.productCode.toString() ?? '';
      if (widget.productModel?.productDealerPrice != null) dealerPriceController.text = widget.productModel!.productDealerPrice.toString();
      if (widget.productModel?.meta?.strength != null) strengthController.text = widget.productModel!.meta!.strength.toString();
      if (widget.productModel?.meta?.genericName != null) geneticController.text = widget.productModel?.meta?.genericName.toString() ?? '';

      if (widget.productModel?.meta?.shelf != null) shelfController.text = widget.productModel?.meta?.shelf.toString() ?? '';
      if (widget.productModel?.stocks?.first.batchNo != null) batchController.text = widget.productModel?.stocks?.first.batchNo.toString() ?? '';
      if (widget.productModel?.meta?.medicineDetails != null) medicineDetailsController.text = widget.productModel?.meta?.medicineDetails.toString() ?? '';
      if (widget.productModel?.stocks?.first.expireDate != null) {
        fromDateTextEditingController.text = DateFormat.yMd().format(DateTime.parse(widget.productModel!.stocks?.first.expireDate.toString() ?? ''));
        selectedDate = widget.productModel!.stocks?.first.expireDate?.toString();
      }

      for (String image in widget.productModel?.images ?? []) {
        oldImageList.add(image);
      }
    }
  }

  @override
  void dispose() {
    nameController.dispose();
    productStockController.dispose();
    stockAlertController.dispose();
    salePriceController.dispose();
    purchaseWithoutTaxController.dispose();
    purchaseWithTaxController.dispose();
    profitPercentController.dispose();
    productCodeController.dispose();
    wholeSalePriceController.dispose();
    dealerPriceController.dispose();
    strengthController.dispose();
    geneticController.dispose();
    shelfController.dispose();
    batchController.dispose();
    medicineDetailsController.dispose();
    fromDateTextEditingController.dispose();
    super.dispose();
  }

  void calculatePurchaseAndMrp({String? from}) {
    num taxRate = selectedTax?.rate ?? 0;
    num purchaseExc = 0;
    num purchaseInc = 0;
    num profitMargin = num.tryParse(profitPercentController.text) ?? 0;
    num salePrice = 0;

    // Calculate Purchase Exclusive Price if input is 'purchase_inc'
    if (from == 'purchase_inc') {
      purchaseExc = (num.tryParse(purchaseWithTaxController.text) ?? 0) / (1 + taxRate / 100);
      purchaseWithoutTaxController.text = purchaseExc.toStringAsFixed(2);
    } else {
      purchaseExc = num.tryParse(purchaseWithoutTaxController.text) ?? 0;
      // Calculate Purchase Inclusive Price if not 'purchase_inc'
      purchaseInc = purchaseExc + (purchaseExc * taxRate / 100);
      purchaseWithTaxController.text = purchaseInc.toStringAsFixed(2);
    }

    purchaseInc = num.tryParse(purchaseWithTaxController.text) ?? 0;

    // If input is 'mrp', recalculate profit margin based on sale price
    if (from == 'mrp') {
      salePrice = num.tryParse(salePriceController.text) ?? 0;

      // Calculate profit margin as a percentage
      profitMargin =
          ((salePrice - (selectedTaxType.toLowerCase() == 'exclusive' ? purchaseExc : purchaseInc)) / (selectedTaxType.toLowerCase() == 'exclusive' ? purchaseExc : purchaseInc)) *
              100;

      // Update the profit margin text field with percentage
      profitPercentController.text = profitMargin.toStringAsFixed(2);
    } else {
      // Calculate Sale Price based on Tax Type
      salePrice = (selectedTaxType.toLowerCase() == 'exclusive') ? purchaseExc + (purchaseExc * profitMargin / 100) : purchaseInc + (purchaseInc * profitMargin / 100);

      // Update the sale price text field
      salePriceController.text = salePrice.toStringAsFixed(2);
    }

    setState(() {});
  }

  GlobalKey<FormState> key = GlobalKey();
  GetCategoryAndVariationModel data = GetCategoryAndVariationModel(variations: [], categoryName: ProductCategoryModel());

  MedicineTypeModel? selectedMedicineType;
  ManufacturerModel? selectedManufacturer;
  BoxSizeModel? selectedBoxSize;
  TaxModel? selectedTax;
  ProductCategoryModel? selectedCategory;

  bool isUpdating = false;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        iconTheme: const IconThemeData(color: Colors.white),
        title: Text(l.S.of(context).addNewProduct, style: theme.textTheme.titleMedium?.copyWith(color: kWhite, fontSize: 20)),
        centerTitle: true,
      ),
      body: Consumer(builder: (context, ref, __) {
        final medicineTypes = ref.watch(medicineTypeProvider);
        final boxSize = ref.watch(leafAndBoxProvider);
        final taxesData = ref.watch(taxProvider);
        final categoryData = ref.watch(categoryProvider);
        final unitData = ref.watch(unitsProvider);
        final manufacturerData = ref.watch(manufacturerProvider);
        return Padding(
          padding: const EdgeInsets.only(top: 5),
          child: SingleChildScrollView(
            child: Form(
              key: key,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    buildImageList(),
                    SizedBox(height: 24),

                    ///__________Bar Code/QR Code__________________________
                    Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: TextFormField(
                            controller: productCodeController,
                            // validator: (value) {
                            //   if (value == null || value.isEmpty) {
                            //     //return 'product code is required';
                            //     return lang.S.of(context).productCodeIsRequired;
                            //   }
                            //   return null;
                            // },
                            onChanged: (value) {
                              setState(() {
                                productCode = value;
                                promoCodeHint = value;
                              });
                            },
                            onFieldSubmitted: (value) {
                              if (codeList.contains(value)) {
                                EasyLoading.showError(
                                  l.S.of(context).thisProductAlreadyAdded,
                                  // 'This Product Already added!'
                                );
                                productCodeController.clear();
                              } else {
                                setState(() {
                                  productCode = value;
                                  promoCodeHint = value;
                                });
                              }
                            },
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              label: getFieldLabelText(context: context, label: lang.barCodeOrQrCode),
                              hintText: lang.pleaseEnterProductCode,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ),
                        SizedBox(width: 10),
                        Expanded(
                          flex: 1,
                          child: GestureDetector(
                            onTap: () async {
                              await showDialog(
                                context: context,
                                useSafeArea: true,
                                builder: (context1) {
                                  MobileScannerController controller = MobileScannerController(
                                    torchEnabled: false,
                                    returnImage: false,
                                  );
                                  return Container(
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadiusDirectional.circular(6.0),
                                    ),
                                    child: Column(
                                      children: [
                                        AppBar(
                                          backgroundColor: Colors.transparent,
                                          iconTheme: const IconThemeData(color: Colors.white),
                                          leading: IconButton(
                                            icon: const Icon(Icons.arrow_back),
                                            onPressed: () {
                                              Navigator.pop(context1);
                                            },
                                          ),
                                        ),
                                        Expanded(
                                          child: MobileScanner(
                                            fit: BoxFit.contain,
                                            controller: controller,
                                            onDetect: (capture) {
                                              final List<Barcode> barcodes = capture.barcodes;

                                              if (barcodes.isNotEmpty) {
                                                final Barcode barcode = barcodes.first;
                                                debugPrint('Barcode found! ${barcode.rawValue}');
                                                // productCode = barcode.rawValue!;
                                                productCodeController.text = barcode.rawValue!;
                                                // globalKey.currentState!.save();
                                                Navigator.pop(context1);
                                              }
                                            },
                                          ),
                                        ),
                                      ],
                                    ),
                                  );
                                },
                              );
                            },
                            child: Container(
                              height: 48.0,
                              // width: 100.0,
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(8.0),
                                border: Border.all(color: kOutlineColor),
                              ),
                              child: const Image(
                                image: AssetImage('images/barcode.png'),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 29),

                    ///__________Product_Name_____________________________
                    TextFormField(
                      controller: nameController,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return l.S.of(context).pleaseEnterAValidProductName;
                        }
                        return null;
                      },
                      decoration: InputDecoration(
                        floatingLabelBehavior: FloatingLabelBehavior.always,
                        label: RichText(
                            text: TextSpan(text: lang.productName, style: theme.textTheme.bodyLarge, children: [
                          TextSpan(
                              text: '*',
                              style: theme.textTheme.bodyLarge?.copyWith(
                                color: kSubTitleColor,
                              ))
                        ])),
                        //hintText: 'Enter product Name',
                        hintText: lang.enterProductName,
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    SizedBox(height: 29),

                    ///_______Category__________________________________
                    categoryData.when(
                      data: (dataList) {
                        if (widget.productModel != null && widget.productModel?.categoryId != null) {
                          selectedCategory = dataList.firstWhere(
                            (element) => element.id == widget.productModel?.categoryId,
                          );
                        }
                        return GenericDropdown<ProductCategoryModel>(
                          dataAsyncValue: dataList,
                          label: '${lang.category}*',
                          hint: lang.selectOne,
                          ref: ref,
                          labelProvider: (item) => item.categoryName.toString(),
                          addNewScreenBuilder: () => AddCategoryScreen(),
                          onChanged: (value) {
                            setState(() {
                              selectedCategory = value;
                            });
                          },
                          onNewItemAdded: (value) async {
                            setState(() {
                              dataList.insert(0, value!);
                              selectedCategory = value;
                            });
                          },
                          validator: (value) {
                            if (value == null) {
                              return lang.pleaseSelectACategory;
                            }
                            return null;
                          },
                          selectedValue: selectedCategory,
                          clearSelectedButton: selectedCategory != null,
                          selectedCancelFunction: () {
                            setState(() {
                              selectedCategory = null;
                            });
                          },
                        );
                      },
                      error: (error, stackTrace) {
                        return Text(error.toString());
                      },
                      loading: () {
                        return const DropdownSkeletonWidget();
                      },
                    ),
                    SizedBox(height: 29),

                    ///--------------medicine type------------------------------
                    medicineTypes.when(
                      data: (dataList) {
                        if (widget.productModel != null && widget.productModel?.medicineType != null) {
                          MedicineTypeModel matched = dataList.firstWhere(
                            (element) => element.id == widget.productModel?.medicineType?.id,
                            orElse: () => MedicineTypeModel(),
                          );
                          if (matched.id != null) {
                            selectedMedicineType = matched;
                          }
                        }
                        return GenericDropdown<MedicineTypeModel>(
                          // provider: medicineTypeProvider,
                          dataAsyncValue: dataList,
                          label: lang.medicineType,
                          hint: lang.selectOne,
                          ref: ref,
                          labelProvider: (item) => item.name.toString(),
                          addNewScreenBuilder: () => AddMedicineTypeScreen(fromDropdown: true),
                          onChanged: (value) {
                            setState(() {
                              selectedMedicineType = value;
                            });
                          },
                          onNewItemAdded: (value) async {
                            setState(() {
                              dataList.insert(0, value!);
                              selectedMedicineType = value;
                            });
                          },
                          selectedValue: selectedMedicineType,
                          clearSelectedButton: selectedMedicineType != null,
                          selectedCancelFunction: () {
                            setState(() {
                              selectedMedicineType = null;
                            });
                          },
                        );
                      },
                      error: (error, stackTrace) {
                        return Text(error.toString());
                      },
                      loading: () {
                        return const DropdownSkeletonWidget();
                      },
                    ),
                    SizedBox(height: 29),

                    ///-------------Strength---------------------------
                    TextFormField(
                      controller: strengthController,
                      decoration: InputDecoration(labelText: lang.strength, hintText: lang.enterStrength),
                    ),
                    SizedBox(height: 29),

                    ///--------------------------Genetic name----------------
                    TextFormField(
                      controller: geneticController,
                      decoration: InputDecoration(labelText: lang.genericName, hintText: lang.enterGenericName),
                    ),
                    SizedBox(height: 29),

                    ///-----------Box Size dropdown-And Unit-----------------------------
                    Row(
                      children: [
                        Expanded(
                          child: boxSize.when(
                            data: (dataList) {
                              if (widget.productModel != null && widget.productModel?.boxSizeId != null) {
                                selectedBoxSize = dataList.firstWhere(
                                  (element) => element.id == widget.productModel?.boxSizeId,
                                );
                              }
                              return GenericDropdown<BoxSizeModel>(
                                dataAsyncValue: dataList,
                                label: lang.boxSize,
                                hint: lang.selectOne,
                                ref: ref,
                                labelProvider: (item) => item.name.toString(),
                                addNewScreenBuilder: () => AddBoxScreen(),
                                onChanged: (value) {
                                  setState(() {
                                    selectedBoxSize = value;
                                  });
                                },
                                onNewItemAdded: (value) async {
                                  setState(() {
                                    dataList.insert(0, value!);
                                    selectedBoxSize = value;
                                  });
                                },
                                selectedValue: selectedBoxSize,
                                clearSelectedButton: selectedBoxSize != null,
                                selectedCancelFunction: () {
                                  setState(() {
                                    selectedBoxSize = null;
                                  });
                                },
                              );
                            },
                            error: (error, stackTrace) {
                              return Text(error.toString());
                            },
                            loading: () {
                              return const DropdownSkeletonWidget();
                            },
                          ),
                        ),
                        SizedBox(
                          width: 10,
                        ),
                        Expanded(
                          child: unitData.when(
                            data: (dataList) {
                              if (widget.productModel != null && widget.productModel?.unitId != null) {
                                selectedUnit = dataList.firstWhere(
                                  (element) => element.id == widget.productModel?.unitId,
                                );
                              }
                              return GenericDropdown<UnitModel>(
                                // provider: medicineTypeProvider,
                                dataAsyncValue: dataList,
                                label: lang.unit,
                                hint: lang.selectProductUnit,
                                ref: ref,
                                labelProvider: (item) => item.unitName.toString(),
                                addNewScreenBuilder: () => AddUnitsScreen(),
                                onChanged: (value) {
                                  setState(() {
                                    selectedUnit = value;
                                  });
                                },
                                onNewItemAdded: (value) async {
                                  setState(() {
                                    dataList.insert(0, value!);
                                    selectedUnit = value;
                                  });
                                },
                                selectedValue: selectedUnit,
                                clearSelectedButton: selectedUnit != null,
                                selectedCancelFunction: () {
                                  setState(() {
                                    selectedUnit = null;
                                  });
                                },
                              );
                            },
                            error: (error, stackTrace) {
                              return Text(error.toString());
                            },
                            loading: () {
                              return const DropdownSkeletonWidget();
                            },
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 29),

                    ///-------------------Manufacturer-------------------------------
                    Row(
                      children: [
                        Expanded(
                          child: manufacturerData.when(
                            data: (dataList) {
                              if (widget.productModel != null && widget.productModel?.manufacturerId != null) {
                                selectedManufacturer = dataList.firstWhere(
                                  (element) => element.id == widget.productModel?.manufacturerId,
                                );
                              }
                              return GenericDropdown<ManufacturerModel>(
                                // provider: medicineTypeProvider,
                                dataAsyncValue: dataList,
                                label: lang.manufacturer,
                                hint: lang.selectOne,
                                ref: ref,
                                labelProvider: (item) => item.name.toString(),
                                addNewScreenBuilder: () => AddManufacturerScreen(fromDropdown: true),
                                onChanged: (value) {
                                  setState(() {
                                    selectedManufacturer = value;
                                  });
                                },
                                onNewItemAdded: (value) async {
                                  setState(() {
                                    dataList.insert(0, value!);
                                    selectedManufacturer = value;
                                  });
                                },
                                selectedValue: selectedManufacturer,
                                showAddButton: true,
                                clearSelectedButton: selectedManufacturer != null,
                                selectedCancelFunction: () {
                                  setState(() {
                                    selectedManufacturer = null;
                                  });
                                },
                              );
                            },
                            error: (error, stackTrace) {
                              return Text(error.toString());
                            },
                            loading: () {
                              return const DropdownSkeletonWidget();
                            },
                          ),
                        ),
                        SizedBox(width: 10),
                        Expanded(
                          child: TextFormField(
                            controller: shelfController,
                            decoration: InputDecoration(labelText: lang.shelf, hintText: lang.enterShelf),
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 29),

                    ///---------------------Stock_&_Shelf--------------------
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            readOnly: widget.productModel != null ? true : false,
                            controller: productStockController,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                //return 'Enter a valid stock';
                                return l.S.of(context).enterAValidStock;
                              }
                              return null;
                            },
                            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              label: getFieldLabelText(label: '${l.S.of(context).stock}*', context: context),
                              hintText: l.S.of(context).enterStock,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ),
                        SizedBox(width: 10),
                        Expanded(
                          child: TextFormField(
                            controller: stockAlertController,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                //return 'Enter a valid stock';
                                return lang.enterAlertQuantity;
                              }
                              return null;
                            },
                            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              label: getFieldLabelText(label: '${'Low Stock'}*', context: context),
                              hintText: lang.enterQty,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ),
                      ],
                    ),

                    ///____________Expire_Data_&_Batch_________________________________
                    SizedBox(height: 29),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            keyboardType: TextInputType.name,
                            readOnly: true,
                            controller: fromDateTextEditingController,
                            decoration: InputDecoration(
                              labelText: lang.expireDate,
                              hintText: lang.enterDate,
                              border: const OutlineInputBorder(),
                              suffixIcon: IconButton(
                                padding: EdgeInsets.zero,
                                visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                                onPressed: () async {
                                  final DateTime? picked = await showDatePicker(
                                    initialDate: DateTime.now(),
                                    firstDate: DateTime(2015, 8),
                                    lastDate: DateTime(2101),
                                    context: context,
                                  );
                                  setState(() {
                                    if (picked != null) {
                                      fromDateTextEditingController.text = DateFormat.yMd().format(picked);
                                      selectedDate = picked.toString();
                                    } else {
                                      fromDateTextEditingController.text = fromDateTextEditingController.text;
                                    }
                                  });
                                },
                                icon: Icon(
                                  IconlyLight.calendar,
                                  color: kNutral600,
                                  size: 22,
                                ),
                              ),
                            ),
                          ),
                        ),
                        SizedBox(
                          width: 10,
                        ),
                        Expanded(
                          child: TextFormField(
                            controller: batchController,
                            decoration: InputDecoration(
                              labelText: lang.batch,
                              hintText: lang.enterBatchNumber,
                            ),
                          ),
                        ),
                      ],
                    ),

                    SizedBox(height: 17),

                    Text(
                      lang.defaultPricingPlan,
                      style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
                    ),
                    SizedBox(height: 20),

                    ///-----------Applicable tax and Type-----------------------------
                    Row(
                      children: [
                        Expanded(
                          child: taxesData.when(
                            data: (dataList) {
                              List<TaxModel> data = dataList.where((tax) => tax.status == true).toList();
                              if (widget.productModel != null && widget.productModel?.taxId != null) {
                                TaxModel matched = data.firstWhere(
                                  (element) => element.id == widget.productModel?.taxId,
                                  orElse: () => TaxModel(),
                                );

                                if (matched.id != null) {
                                  selectedTax = matched;
                                }
                              }
                              return GenericDropdown<TaxModel>(
                                showAddButton: false,
                                dataAsyncValue: data,
                                label: lang.applicableTax,
                                hint: lang.selectOne,
                                ref: ref,
                                labelProvider: (item) => item.name.toString(),
                                addNewScreenBuilder: () => CreateSingleTax(),
                                onChanged: (value) {
                                  selectedTax = value;
                                  calculatePurchaseAndMrp();
                                },
                                onNewItemAdded: (value) async {
                                  setState(() {
                                    data.insert(0, value!);
                                    selectedTax = value;
                                  });
                                  calculatePurchaseAndMrp();
                                },
                                selectedValue: selectedTax,
                                clearSelectedButton: selectedTax != null,
                                selectedCancelFunction: () {
                                  setState(() {
                                    selectedTax = null;
                                  });
                                  calculatePurchaseAndMrp();
                                },
                              );
                            },
                            error: (error, stackTrace) {
                              return Text(error.toString());
                            },
                            loading: () {
                              return const DropdownSkeletonWidget();
                            },
                          ),
                        ),
                        SizedBox(width: 10),

                        // Tax type
                        Expanded(
                          child: DropdownButtonFormField<String?>(
                            hint: Text('Select Type'),
                            decoration: InputDecoration(labelText: lang.taxType),
                            initialValue: selectedTaxType,
                            items: [
                              "Inclusive",
                              "Exclusive",
                            ]
                                .map((type) => DropdownMenuItem<String?>(
                                      value: type,
                                      child: Text(
                                        type,
                                        style: theme.textTheme.bodyMedium,
                                      ),
                                    ))
                                .toList(),
                            onChanged: (value) {
                              setState(() {
                                selectedTaxType = value!;
                                // updatePrices();
                              });
                              calculatePurchaseAndMrp();
                            },
                            // validator: (value) {
                            //   if (value == null || value.trim().isEmpty) {
                            //     return 'Please select tax type';
                            //   }
                            //   return null;
                            // },
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 29),

                    //  Purchase Include & Exclude
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: purchaseWithoutTaxController,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                //return 'Please enter a valid purchase price';
                                return l.S.of(context).pleaseEnterAValidProductName;
                              }
                              // You can add more validation logic as needed
                              return null;
                            },
                            onChanged: (value) => calculatePurchaseAndMrp(),
                            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              label: getFieldLabelText(label: '${lang.purchaseExcPrice}*', context: context),
                              hintText: l.S.of(context).enterPurchasePrice,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ),
                        SizedBox(
                          width: 10,
                        ),
                        Expanded(
                          child: TextFormField(
                            controller: purchaseWithTaxController,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                //return 'Please enter a valid Sale price';
                                return l.S.of(context).pleaseEnterAValidSalePrice;
                              }
                              // You can add more validation logic as needed
                              return null;
                            },
                            onChanged: (value) => calculatePurchaseAndMrp(from: "purchase_inc"),
                            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              label: getFieldLabelText(label: '${lang.purchaseIncPrice}*', context: context),
                              hintText: l.S.of(context).enterSaltingPrice,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 29),

                    ///_________Purchase_price__&&__MRP_____________________
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: profitPercentController,
                            onChanged: (value) => calculatePurchaseAndMrp(),
                            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              label: getFieldLabelText(label: lang.profitPercent, context: context),
                              hintText: lang.enterProfitPercentage,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ),
                        SizedBox(
                          width: 10,
                        ),
                        Expanded(
                          child: TextFormField(
                            onChanged: (value) => calculatePurchaseAndMrp(from: 'mrp'),
                            controller: salePriceController,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return l.S.of(context).pleaseEnterAValidSalePrice;
                              }
                              return null;
                            },
                            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              floatingLabelBehavior: FloatingLabelBehavior.always,
                              label: getFieldLabelText(label: '${lang.salePrice}*', context: context),
                              hintText: l.S.of(context).enterSaltingPrice,
                              border: const OutlineInputBorder(),
                            ),
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 29),

                    ///_______wholesalePrice_dealer_price_________________
                    TextFormField(
                      controller: wholeSalePriceController,
                      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        floatingLabelBehavior: FloatingLabelBehavior.always,
                        labelText: l.S.of(context).wholeSalePrice,
                        //hintText: 'Enter wholesale price',
                        hintText: l.S.of(context).enterWholesalePrice,
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    SizedBox(height: 29),

                    ///_______________Medicine Details________________________-
                    TextFormField(
                      maxLines: 3,
                      controller: medicineDetailsController,
                      decoration: InputDecoration(
                        labelText: lang.medicineDetails,
                        hintText: '${lang.enterMedicineDetails}...',
                        contentPadding: EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 8,
                        ),
                      ),
                    ),
                    SizedBox(height: 32),
                    Padding(
                      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
                      child: NewPrimaryButton(
                        buttonText: l.S.of(context).saveNPublish,
                        onPressed: () async {
                          if (isClicked) {
                            return;
                          }
                          if (!(key.currentState?.validate() ?? false)) {
                            return;
                          }

                          isClicked = true;
                          EasyLoading.show();
                          if (widget.productModel != null) {
                            ///____________Update_product___________________________________
                            List<File> imageFiles = [];
                            for (File imagePath in _imageFiles) {
                              File file = File(imagePath.path);
                              imageFiles.add(file);
                            }
                            print('remove image list ${removedImg.toString()}');
                            AddProductModel data = AddProductModel(
                              productCode: productCodeController.text,
                              productName: nameController.text,
                              categoryId: selectedCategory?.id ?? 0,
                              typeId: selectedMedicineType?.id,
                              strength: strengthController.text,
                              genericName: geneticController.text,
                              manufacturerId: selectedManufacturer?.id ?? 0,
                              boxSizeId: selectedBoxSize?.id ?? 0,
                              unitId: selectedUnit?.id ?? 0,
                              productStock: productStockController.text,
                              alertStock: stockAlertController.text,
                              shelf: shelfController.text,
                              batchNo: batchController.text,
                              expireDate: selectedDate,
                              purchaseWithoutTax: num.tryParse(purchaseWithoutTaxController.text.toString()) ?? 0,
                              purchaseWithTax: num.tryParse(purchaseWithTaxController.text.toString()) ?? 0,
                              profitPercent: num.tryParse(profitPercentController.text) ?? 0,
                              salePrice: num.tryParse(salePriceController.text) ?? 0,
                              taxType: selectedTaxType.toString(),
                              wholeSalePrice: wholeSalePriceController.text,
                              images: imageFiles,
                              medicineDetails: medicineDetailsController.text,
                              taxId: selectedTax?.id,
                              removedImageList: removedImg,
                            );
                            ProductRepo repo = ProductRepo();
                            bool success = await repo.updateProduct(
                              data: data,
                              context: context,
                              id: widget.productModel?.id.toString() ?? '',
                            );

                            EasyLoading.dismiss();

                            if (success) {
                              widget.pagingController?.refresh();
                              ref.refresh(productDetailsProvider(widget.productModel?.id.toString() ?? ''));
                              if (widget.isFromHome != true) {
                                Navigator.pop(context);
                              }
                            } else {
                              // Reset flag
                              isClicked = false;
                            }
                          } else {
                            List<File> imageFiles = [];
                            for (File imagePath in _imageFiles) {
                              File file = File(imagePath.path);
                              imageFiles.add(file);
                            }
                            AddProductModel data = AddProductModel(
                              productCode: productCodeController.text,
                              productName: nameController.text,
                              categoryId: selectedCategory?.id ?? 0,
                              typeId: selectedMedicineType?.id,
                              strength: strengthController.text,
                              genericName: geneticController.text,
                              manufacturerId: selectedManufacturer?.id,
                              boxSizeId: selectedBoxSize?.id,
                              unitId: selectedUnit?.id,
                              productStock: productStockController.text,
                              alertStock: stockAlertController.text,
                              shelf: shelfController.text,
                              batchNo: batchController.text,
                              expireDate: selectedDate,
                              purchaseWithoutTax: num.tryParse(purchaseWithoutTaxController.text.toString()) ?? 0,
                              purchaseWithTax: num.tryParse(purchaseWithTaxController.text.toString()) ?? 0,
                              profitPercent: num.tryParse(profitPercentController.text) ?? 0,
                              salePrice: num.tryParse(salePriceController.text) ?? 0,
                              taxType: selectedTaxType.toString() ?? '',
                              wholeSalePrice: wholeSalePriceController.text,
                              images: imageFiles,
                              medicineDetails: medicineDetailsController.text,
                              taxId: selectedTax?.id,
                            );
                            ProductRepo repo = ProductRepo();
                            bool success = await repo.addProduct(
                              data: data,
                              context: context,
                            );

                            EasyLoading.dismiss();

                            if (success) {
                              widget.pagingController?.refresh();
                              _clearFormFields();
                              if (widget.isFromHome != true) {
                                Navigator.pop(context);
                              }
                            } else {
                              // Reset flag
                              isClicked = false;
                            }
                          }
                        },
                      ),
                    ),
                    SizedBox(height: 10),
                  ],
                ),
              ),
            ),
          ),
        );
      }),
    );
  }

  // Picker Dialog
  Future<dynamic> imagePickerDialog(BuildContext context) {
    return showDialog(
      context: context,
      builder: (BuildContext context) {
        return ImagePickerDialog(
          onCameraTap: () async {
            await _pickImages(ImageSource.camera);
            Future.delayed(const Duration(milliseconds: 100), () {
              Navigator.pop(context);
            });
          },
          onGalleryTap: () async {
            _pickImages(ImageSource.gallery);
            Future.delayed(const Duration(milliseconds: 100), () {
              Navigator.pop(context);
            });
          },
        );
      },
    );
  }

  // Image
  Widget buildImageList() {
    List<String> allImages = List.from(oldImageList);
    allImages.addAll(_imageFiles.map((file) => file.path).toList());

    bool showAddButton = allImages.length < 3; // Show add button if total images < 3.

    return SizedBox(
      height: 64,
      child: ListView.builder(
        padding: EdgeInsets.zero,
        scrollDirection: Axis.horizontal,
        itemCount: allImages.length < 3 ? allImages.length + 1 : allImages.length,
        itemBuilder: (_, i) {
          if (i == allImages.length && showAddButton) {
            return InkWell(
              onTap: () {
                imagePickerDialog(context);
              },
              child: Container(
                height: 64,
                width: 64,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(5),
                  border: Border.all(color: kMainColor),
                ),
                child: const Icon(
                  Icons.add,
                  color: kMainColor,
                ),
              ),
            );
          } else {
            bool isOldImage = i < oldImageList.length; // Check if the image is from the old list.

            return Stack(
              children: [
                Padding(
                  padding: const EdgeInsets.only(right: 8.0),
                  child: Container(
                    height: 64,
                    width: 64,
                    decoration: BoxDecoration(
                      borderRadius: const BorderRadius.all(Radius.circular(8.0)),
                      border: Border.all(color: kOutlineColor),
                      image: DecorationImage(
                        image: isOldImage ? NetworkImage('${APIConfig.domain}${oldImageList[i]}') : FileImage(_imageFiles[i - oldImageList.length]),
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(5.0),
                  child: GestureDetector(
                    onTap: () {
                      setState(() {
                        if (isOldImage) {
                          if (removedImg.length < 3) {
                            String removedImage = oldImageList.removeAt(i);
                            removedImg.add(removedImage.toString());
                          }
                        } else {
                          _imageFiles.removeAt(i - oldImageList.length);
                        }
                      });
                    },
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.6),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(
                        Icons.close,
                        color: Colors.white,
                        size: 18,
                      ),
                    ),
                  ),
                ),
              ],
            );
          }
        },
      ),
    );
  }

  void _clearFormFields() {
    productCodeController.clear();
    nameController.clear();
    strengthController.clear();
    geneticController.clear();
    productStockController.clear();
    stockAlertController.clear();
    shelfController.clear();
    batchController.clear();
    purchaseWithoutTaxController.clear();
    purchaseWithTaxController.clear();
    profitPercentController.clear();
    salePriceController.clear();
    wholeSalePriceController.clear();
    medicineDetailsController.clear();
    selectedCategory = null;
    selectedMedicineType = null;
    selectedManufacturer = null;
    selectedBoxSize = null;
    selectedUnit = null;
    selectedTax = null;
    selectedDate = null;
    _imageFiles.clear(); // Clear image files if necessary
    removedImg.clear(); // Clear removed image list if necessary
    isClicked = false;
  }
}
