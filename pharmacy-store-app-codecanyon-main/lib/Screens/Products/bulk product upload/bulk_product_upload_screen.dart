import 'dart:io';
import 'package:excel/excel.dart' as e;
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Provider/product_provider.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:mobile_pos/Screens/Products/bulk%20product%20upload/product_fuction.dart';
import 'package:mobile_pos/constant.dart';

import '../Model/product_model.dart';
import 'create_excl_sheet.dart';

class BulkUploader extends StatefulWidget {
  const BulkUploader({super.key, required this.previousProductName, required this.previousProductCode});

  final List<String> previousProductName;
  final List<String> previousProductCode;

  @override
  State<BulkUploader> createState() => _BulkUploaderState();
}

class _BulkUploaderState extends State<BulkUploader> {
  String? filePat;
  File? file;
  String getFileExtension(String fileName) {
    return fileName.split('/').last;
  }

  Future<void> uploadProducts({
    required File file,
    required WidgetRef ref,
    required BuildContext context,
  }) async {
    try {
      e.Excel excel = e.Excel.decodeBytes(file.readAsBytesSync());
      var sheet = excel.sheets.keys.first;
      var table = excel.tables[sheet]!;
      for (var row in table.rows) {
        ProductModel? data = await createProductModelFromExcelData(row: row, ref: ref);

        // print("Name Of Product: ${data?.productName}");
        // print("code: ${data?.productCode}");
        // print("Stock: ${data?.productStock}");
        // print("Purchasse: ${data?.productPurchasePrice}");
        // print("Sales: ${data?.productSalePrice}");
        // print("categoryId: ${data?.categoryId}");
        if (data != null) {
          final result = await productRepo.addProductForBulk(
            productName: data.productName!,
            categoryId: data.categoryId.toString(),
            productCode: data.productCode.toString(),
            productStock: data.stockSum.toString(),
            productSalePrice: data.productSalePrice.toString(),
            productPurchasePrice: data.productPurchasePriceWithoutTax.toString(),
            productWholeSalePrice: data.productWholeSalePrice.toString(),
            boxSizeId: data.boxSizeId,
            typeId: data.typeId,
            strength: data.meta?.strength.toString(),
            shelf: data.meta?.shelf.toString(),
            productDealerPrice: data.productDealerPrice.toString(),
            expireDate: DateTime.tryParse(data.stocks?.first.expireDate ?? '') == null ? null : DateTime.parse(data.stocks?.first.expireDate ?? '').toString(),
            manufacturerId: data.manufacturerId,
            batchNo: data.stocks?.first.batchNo.toString() ?? '',
            genericName: data.meta?.genericName.toString(),
            medicineDetails: data.meta?.medicineDetails.toString(),
            unitId: data.unitId,
          );
          print('product Add Result of ${data.productName}: $result');
        }
      }
      ref.refresh(productProvider);

      Future.delayed(const Duration(seconds: 1), () {
        EasyLoading.showSuccess(l.S.current.uploadDone);
        int count = 0;
        Navigator.popUntil(context, (route) {
          return count++ == 2;
        });
      });
    } catch (e) {
      EasyLoading.showError(e.toString());
      return;
      throw UnsupportedError('Excel format unsupported. Only .xlsx files are supported');
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(lang.excelUploader),
      ),
      body: Consumer(builder: (context, ref, __) {
        return Center(
          child: Padding(
            padding: const EdgeInsets.all(8.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              mainAxisSize: MainAxisSize.min,
              children: [
                Visibility(
                  visible: file != null,
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 20),
                    child: Card(
                        child: ListTile(
                            leading: Container(
                                height: 40,
                                width: 40,
                                padding: const EdgeInsets.all(2),
                                decoration: BoxDecoration(
                                  border: Border.all(color: Colors.grey),
                                  borderRadius: const BorderRadius.all(Radius.circular(10)),
                                ),
                                child: const Image(image: AssetImage('images/excel.png'))),
                            title: Text(
                              getFileExtension(file?.path ?? ''),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            trailing: GestureDetector(
                                onTap: () {
                                  setState(() {
                                    file = null;
                                  });
                                },
                                child: Text(lang.remove)))),
                  ),
                ),
                Visibility(
                  visible: file == null,
                  child: const Padding(
                      padding: EdgeInsets.only(bottom: 20),
                      child: Image(
                        height: 100,
                        width: 100,
                        image: AssetImage('images/file-upload.png'),
                      )),
                ),
                ElevatedButton(
                  style: const ButtonStyle(backgroundColor: WidgetStatePropertyAll(kMainColor)),
                  onPressed: () async {
                    if (file == null) {
                      await pickAndUploadFile(ref: ref);
                    } else {
                      EasyLoading.show(status: '${lang.uploading}...');
                      await uploadProducts(ref: ref, file: file!, context: context);
                      EasyLoading.dismiss();
                    }
                  },
                  child: Text(file == null ? lang.pickAndUploadFile : lang.upload, style: const TextStyle(color: Colors.white)),
                ),
                TextButton(
                  onPressed: () async {
                    await createExcelFile();
                  },
                  child: Text(lang.downloadExcelFormat),
                ),
              ],
            ),
          ),
        );
      }),
    );
  }

  Future<void> pickAndUploadFile({required WidgetRef ref}) async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['xlsx'],
    );
    if (result != null) {
      setState(() {
        file = File(result.files.single.path!);
      });
    }
  }
}
