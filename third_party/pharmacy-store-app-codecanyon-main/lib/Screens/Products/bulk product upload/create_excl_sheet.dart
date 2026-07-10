import 'dart:io';
import 'package:excel/excel.dart' as e;
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:permission_handler/permission_handler.dart';
import '../../../app_config/app_config.dart';

Future<void> createExcelFile() async {
  if (!await Permission.storage.request().isDenied) {
    EasyLoading.showError('Storage permission is required to create Excel file!');
    return;
  }
  EasyLoading.show();
  final List<e.CellValue> excelData = [
    e.TextCellValue('SL'),
    e.TextCellValue('Product Name*'),
    e.TextCellValue('Product Code*'),
    e.TextCellValue('Product Stock*'),
    e.TextCellValue('Purchase Price*'),
    e.TextCellValue('MRP*'),
    e.TextCellValue('Wholesale Price'),
    e.TextCellValue('Dealer Price'),
    e.TextCellValue('Category*'),
    e.TextCellValue('Leaf/Box Size'),
    e.TextCellValue('Manufacturer'),
    e.TextCellValue('Units'),
    e.TextCellValue('Medicine Type'),
    e.TextCellValue('Expire Date'),
    e.TextCellValue('Strength'),
    e.TextCellValue('Generic Name'),
    e.TextCellValue('Shelf'),
    e.TextCellValue('Batch No'),
    e.TextCellValue('Medicine Details'),
  ];
  e.CellStyle cellStyle = e.CellStyle(
    bold: true,
    textWrapping: e.TextWrapping.WrapText,
    rotation: 0,
  );
  var excel = e.Excel.createExcel();
  var sheet = excel['Sheet1'];

  sheet.appendRow(excelData);

  for (int i = 0; i < excelData.length; i++) {
    var cell = sheet.cell(e.CellIndex.indexByColumnRow(columnIndex: i, rowIndex: 0));
    cell.cellStyle = cellStyle;
  }
  const downloadsFolderPath = '/storage/emulated/0/Download/';
  Directory dir = Directory(downloadsFolderPath);
  final file = File('${dir.path}/${AppConfig.appName}_bulk_product_upload.xlsx');
  if (await file.exists()) {
    EasyLoading.showSuccess('The Excel file has already been downloaded');
  } else {
    await file.writeAsBytes(excel.encode()!);

    EasyLoading.showSuccess('Downloaded successfully in download folder');
  }
}
