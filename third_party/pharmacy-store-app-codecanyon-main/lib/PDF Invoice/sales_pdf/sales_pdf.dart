// ignore_for_file: use_build_context_synchronously
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:number_to_words/number_to_words.dart';
import 'package:path_provider/path_provider.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:http/http.dart' as http;
import '../../app_config/api_config.dart';
import '../../Screens/PDF/pdf.dart';
import '../../Screens/Report/model/sales_report_model.dart';
import '../../Screens/Sales/Model/sales_details_model.dart';
import '../../app_config/app_config.dart';
import '../../model/business_info_model.dart';

class GenerateSalesPdf {
  //-------------------sales details pdf---------------------------
  Future<void> generateSaleDetailsInvoice(BuildContext context, SalesDetailsData? sale, BusinessInformation? shopInfo) async {
    print(' sales data : ${sale?.returns?.length}');
    final pdf = pw.Document();
    int serialNumber = 1;
    final interFont = await PdfGoogleFonts.interRegular();

    // Sub-Total
    // num subTotal = 0.0;
    // if (sale?.details != null) {
    //   for (var detail in sale!.details!) {
    //     subTotal += (detail.price ?? 0) * (detail.quantities ?? 0);
    //   }
    // }

    //-------------------image------------
    Future<dynamic> getNetworkImage(String imageURL) async {
      if (imageURL.isEmpty) return null;
      try {
        final Uri uri = Uri.parse(imageURL);
        final String fileExtension = uri.path.split('.').last.toLowerCase();
        if (fileExtension == 'png' || fileExtension == 'jpg' || fileExtension == 'jpeg') {
          final List<int> responseBytes = await http.readBytes(uri);
          return Uint8List.fromList(responseBytes);
        } else if (fileExtension == 'svg') {
          final response = await http.get(uri);
          return response.body;
        } else {
          print('Unsupported image type: $fileExtension');
          return null;
        }
      } catch (e) {
        print('Error loading image: $e');
        return null;
      }
    }

    Future<Uint8List?> loadAssetImage(String path) async {
      try {
        final ByteData data = await rootBundle.load(path);
        return data.buffer.asUint8List();
      } catch (e) {
        print('Error loading local image: $e');
        return null;
      }
    }

    num totalReturned = 0;
    final String imageUrl = APIConfig.domain;
    dynamic imageData = await getNetworkImage(imageUrl);
    imageData ??= await loadAssetImage('images/parties_2.png');
    // Create a page with the layout
    pdf.addPage(pw.MultiPage(
        pageFormat: PdfPageFormat.letter.copyWith(marginBottom: 1.5 * PdfPageFormat.cm),
        margin: pw.EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        //----------------pdf header--------------
        header: (pw.Context context) {
          return pw.Column(
            children: [
              pw.Row(
                children: [
                  if (imageData is Uint8List)
                    pw.Container(
                      height: 50.0,
                      width: 50.0,
                      child: pw.Image(pw.MemoryImage(imageData)),
                    )
                  else if (imageData is String)
                    pw.Container(
                      height: 50.0,
                      width: 50.0,
                      child: pw.SvgImage(svg: imageData),
                    )
                  else
                    pw.Container(
                      height: 50.0,
                      width: 50.0,
                      child: pw.Image(pw.MemoryImage(imageData)),
                    ),
                  pw.SizedBox(width: 10.0),
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(
                        shopInfo?.companyName ?? 'n/a',
                        style: pw.TextStyle(
                          font: interFont,
                          fontWeight: pw.FontWeight.bold,
                          fontSize: 24,
                        ),
                      ),
                      pw.Text(
                        'Email: ${shopInfo?.user?.email ?? 'n/a'}',
                        style: pw.TextStyle(
                          color: PdfColors.black,
                          font: interFont,
                        ),
                      ),
                    ],
                  ),
                  pw.Spacer(),
                  pw.Container(
                    alignment: pw.Alignment.center,
                    height: 52,
                    width: 231,
                    decoration: const pw.BoxDecoration(
                      color: PdfColor.fromInt(0xff00987F),
                      borderRadius: pw.BorderRadius.only(
                        topLeft: pw.Radius.circular(25),
                        bottomLeft: pw.Radius.circular(25),
                      ),
                    ),
                    child: pw.Text(
                      'INVOICE',
                      style: pw.TextStyle(
                        font: interFont,
                        color: PdfColors.white,
                        fontWeight: pw.FontWeight.bold,
                        fontSize: 35,
                      ),
                    ),
                  ),
                ],
              ),
              pw.SizedBox(height: 36.0),
            ],
          );
        },
        //-----------------pdf footer-------------
        footer: (pw.Context context) {
          return pw.Column(children: [
            pw.Padding(
              padding: const pw.EdgeInsets.all(10.0),
              child: pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
                pw.Container(
                  alignment: pw.Alignment.centerRight,
                  margin: const pw.EdgeInsets.only(bottom: 3.0 * PdfPageFormat.mm),
                  padding: const pw.EdgeInsets.only(bottom: 3.0 * PdfPageFormat.mm),
                  child: pw.Column(children: [
                    pw.Container(
                      width: 120.0,
                      height: 2.0,
                      color: PdfColors.black,
                    ),
                    pw.SizedBox(height: 4.0),
                    pw.Text(
                      'Customer Signature',
                      style: pw.Theme.of(context).defaultTextStyle.copyWith(color: PdfColors.black),
                    )
                  ]),
                ),
                pw.Container(
                  alignment: pw.Alignment.centerRight,
                  margin: const pw.EdgeInsets.only(bottom: 3.0 * PdfPageFormat.mm),
                  padding: const pw.EdgeInsets.only(bottom: 3.0 * PdfPageFormat.mm),
                  child: pw.Column(children: [
                    pw.Container(
                      width: 120.0,
                      height: 2.0,
                      color: PdfColors.black,
                    ),
                    pw.SizedBox(height: 4.0),
                    pw.Text(
                      'Authorized Signature',
                      style: pw.Theme.of(context).defaultTextStyle.copyWith(color: PdfColors.black),
                    )
                  ]),
                ),
              ]),
            ),
            pw.Container(
              width: double.infinity,
              color: const PdfColor.fromInt(0xff00987F),
              padding: const pw.EdgeInsets.all(10.0),
              child: pw.Center(child: pw.Text('Powered By ${AppConfig.companyName}', style: pw.TextStyle(color: PdfColors.white, fontWeight: pw.FontWeight.bold))),
            ),
          ]);
        },
        //-----------------pdf body---------------
        build: (pw.Context context) {
          final SalesDetailsData? showAbleSalesDetails = (sale?.returns?.isEmpty ?? true) ? sale : sale?.salesData;
          String getDetailsName({required num detailsId}) {
            if (sale?.details != null && (sale?.details?.isNotEmpty ?? false)) {
              return sale!.details!
                      .where(
                        (element) => element.id == detailsId,
                      )
                      .first
                      .product
                      ?.productName ??
                  '';
            }
            return 'N/A';
          }

          num subTotal() {
            num totalSum = 0;
            for (var product in showAbleSalesDetails?.details ?? []) {
              totalSum += (product?.price ?? 0) * (product?.quantities ?? 1);
            }
            if (showAbleSalesDetails?.returns?.isNotEmpty ?? false) {
              for (var product in showAbleSalesDetails?.returns ?? []) {
                totalSum += (product?.price ?? 0) * (product?.quantities ?? 1);
              }
            }

            return totalSum;
          }

          num calculateTotalReturnAmount() {
            num totalReturnAmount = 0;
            if (sale?.returns != null) {
              for (var returnItem in sale!.returns!) {
                if (returnItem.details != null) {
                  for (var detail in returnItem.details!) {
                    totalReturnAmount += detail.returnAmount ?? 0;
                  }
                }
              }
            }
            return totalReturnAmount;
          }

          return [
            pw.Column(
              children: [
                pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
                  pw.Column(children: [
                    _buildHeaderData(
                      key: 'Bill To',
                      keyWidth: 70,
                      value: sale?.party?.name ?? 'Guest',
                      valueWidth: 100,
                      font: interFont,
                    ),
                    _buildHeaderData(
                      key: 'Mobile',
                      keyWidth: 70,
                      value: sale?.party?.phone ?? 'n/a',
                      valueWidth: 100,
                      font: interFont,
                    ),
                    _buildHeaderData(
                      key: 'Sales By',
                      keyWidth: 70,
                      value: sale?.salesBy?.name ?? 'n/a',
                      valueWidth: 100,
                      font: interFont,
                    ),
                  ]),
                  pw.Column(children: [
                    _buildHeaderData(
                      key: 'Invoice Number',
                      keyWidth: 100,
                      value: '#${sale?.invoiceNumber}',
                      valueWidth: 70,
                      font: interFont,
                    ),
                    _buildHeaderData(
                      key: 'Date',
                      keyWidth: 100,
                      value: DateFormat('d MMM, yyyy').format(
                        DateTime.parse(sale?.saleDate.toString() ?? ''),
                      ),
                      valueWidth: 70,
                      font: interFont,
                    ),
                    _buildHeaderData(
                      key: 'Payment Type',
                      keyWidth: 100,
                      value: sale?.paymentType ?? 'n/a',
                      valueWidth: 70,
                      font: interFont,
                    ),
                  ]),
                ]),
                pw.SizedBox(height: 13),
                //Product table section
                pw.Table(
                  border: const pw.TableBorder(
                    verticalInside: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                    left: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                    right: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                    bottom: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                  ),
                  columnWidths: <int, pw.TableColumnWidth>{
                    0: const pw.FlexColumnWidth(1),
                    1: const pw.FlexColumnWidth(5),
                    2: const pw.FlexColumnWidth(2),
                    3: const pw.FlexColumnWidth(2),
                    4: const pw.FlexColumnWidth(2),
                  },
                  children: [
                    // PDF header
                    pw.TableRow(
                      children: [
                        _buildTableRowHeader('SL', Color(0xffFF5F00), pw.TextAlign.center),
                        _buildTableRowHeader('Item', Color(0xffFF5F00), pw.TextAlign.left),
                        _buildTableRowHeader('Quantity', Color(0xff00987F), pw.TextAlign.center),
                        _buildTableRowHeader('Unit Price', Color(0xff00987F), pw.TextAlign.right),
                        _buildTableRowHeader('Total Price', Color(0xff00987F), pw.TextAlign.right),
                      ],
                    ),
                    for (int i = 0; i < sale!.details!.length; i++)
                      pw.TableRow(
                        decoration: i % 2 == 0
                            ? const pw.BoxDecoration(
                                color: PdfColors.white,
                              )
                            : pw.BoxDecoration(
                                color: PdfColors.teal50,
                              ),
                        children: [
                          // SL Column
                          pw.Padding(
                            padding: const pw.EdgeInsets.all(8.0),
                            child: pw.Text(
                              (i + 1).toString(),
                              textAlign: pw.TextAlign.center,
                            ),
                          ),
                          // Item Column
                          pw.Padding(
                            padding: const pw.EdgeInsets.all(8.0),
                            child: pw.Text(
                              (showAbleSalesDetails?.returns?.isNotEmpty ?? false)
                                  ? (showAbleSalesDetails?.salesData?.details?[i].product?.productName ?? 'n/a')
                                  : (showAbleSalesDetails?.details?[i].product?.productName ?? 'n/a'),
                              textAlign: pw.TextAlign.left,
                            ),
                          ),
                          // Quantity Column
                          pw.Padding(
                            padding: const pw.EdgeInsets.all(8.0),
                            child: pw.Text(
                              (showAbleSalesDetails?.returns?.isNotEmpty ?? false)
                                  ? (showAbleSalesDetails?.salesData?.details?[i].quantities?.toString() ?? '0')
                                  : (showAbleSalesDetails?.details?[i].quantities?.toString() ?? '0'),
                              textAlign: pw.TextAlign.center,
                            ),
                          ),
                          // Unit Price Column
                          pw.Padding(
                            padding: const pw.EdgeInsets.all(8.0),
                            child: pw.Text(
                              (showAbleSalesDetails?.returns?.isNotEmpty ?? false)
                                  ? (showAbleSalesDetails?.salesData?.details?[i].price?.toString() ?? '0')
                                  : (showAbleSalesDetails?.details?[i].price?.toString() ?? '0'),
                              textAlign: pw.TextAlign.right,
                            ),
                          ),
                          // Total Price Column
                          pw.Padding(
                            padding: const pw.EdgeInsets.all(8.0),
                            child: pw.Text(
                              (showAbleSalesDetails?.returns?.isNotEmpty ?? false)
                                  ? ((showAbleSalesDetails?.salesData?.details?[i].price ?? 0) * (showAbleSalesDetails?.salesData?.details?[i].quantities ?? 0)).toString()
                                  : ((showAbleSalesDetails?.details?[i].price ?? 0) * (showAbleSalesDetails?.details?[i].quantities ?? 0)).toString(),
                              textAlign: pw.TextAlign.right,
                            ),
                          ),
                        ],
                      ),
                  ],
                ),
                pw.SizedBox(height: 8),
                // Summary Section
                pw.Column(
                  children: [
                    //subtotal
                    _buildAmountWidget(title: 'Sub-Total', value: subTotal().toStringAsFixed(2), font: interFont),
                    pw.SizedBox(height: 5),
                    //discount
                    _buildAmountWidget(title: 'Discount', value: sale.discountAmount!.toStringAsFixed(2), font: interFont),
                    pw.SizedBox(height: 5),
                    //vat
                    _buildAmountWidget(title: sale.tax?.name.toString() ?? 'Vat', value: sale.taxAmount?.toStringAsFixed(2) ?? '0.0', font: interFont),
                    pw.SizedBox(height: 5),
                    //total Amount
                    pw.Row(
                      children: [
                        pw.Spacer(flex: 4),
                        pw.Expanded(
                          flex: 2,
                          child: pw.Container(
                            padding: pw.EdgeInsets.all(2),
                            decoration: pw.BoxDecoration(
                              color: PdfColor.fromInt(0xff00987F),
                            ),
                            child: pw.Row(
                              children: [
                                pw.Expanded(
                                  flex: 1,
                                  child: pw.Text('Total Amount :',
                                      style: pw.TextStyle(font: interFont, fontWeight: pw.FontWeight.bold, color: PdfColors.white), textAlign: pw.TextAlign.end),
                                ),
                                pw.Expanded(
                                  flex: 1,
                                  child: pw.Padding(
                                    padding: pw.EdgeInsets.only(right: 5),
                                    child: pw.Text(
                                      sale.totalAmount!.toStringAsFixed(2),
                                      style: pw.TextStyle(font: interFont, fontWeight: pw.FontWeight.bold, color: PdfColors.white),
                                      textAlign: pw.TextAlign.end,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    // pw.SizedBox(height: 24),
                  ],
                ),
                //-----------------------sales returns------------------------------
                if (sale.returns?.isNotEmpty ?? false)
                  pw.Column(children: [
                    pw.Align(
                      alignment: pw.Alignment.centerLeft,
                      child: pw.Text(
                        'Returned',
                        textAlign: pw.TextAlign.start,
                        style: pw.TextStyle(
                          fontWeight: pw.FontWeight.bold,
                          font: interFont,
                        ),
                      ),
                    ),
                    pw.SizedBox(height: 12),
                    pw.Table(
                      border: const pw.TableBorder(
                        verticalInside: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                        left: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                        right: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                        bottom: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                      ),
                      columnWidths: <int, pw.TableColumnWidth>{
                        0: const pw.FlexColumnWidth(1),
                        1: const pw.FlexColumnWidth(3),
                        2: const pw.FlexColumnWidth(4),
                        3: const pw.FlexColumnWidth(2),
                        4: const pw.FlexColumnWidth(3),
                      },
                      children: [
                        //table header
                        pw.TableRow(
                          children: [
                            _buildTableRowHeader('SL', Color(0xffFF5F00), pw.TextAlign.center),
                            _buildTableRowHeader('Date', Color(0xffFF5F00), pw.TextAlign.left),
                            _buildTableRowHeader('Returned Item', Color(0xff00987F), pw.TextAlign.center),
                            _buildTableRowHeader('Quantity', Color(0xff00987F), pw.TextAlign.right),
                            _buildTableRowHeader('Total Return', Color(0xff00987F), pw.TextAlign.right),
                          ],
                        ),

                        // Data rows for returns
                        for (int i = 0; i < (sale.returns?.length ?? 0); i++)
                          for (int j = 0; j < (sale.returns?[i].details?.length ?? 0); j++)
                            pw.TableRow(
                              decoration: serialNumber.isOdd
                                  ? const pw.BoxDecoration(color: PdfColors.white) // Odd row color
                                  : const pw.BoxDecoration(color: PdfColors.teal50),
                              children: [
                                pw.Padding(
                                  padding: const pw.EdgeInsets.all(8.0),
                                  child: pw.Text('${serialNumber++}', textAlign: pw.TextAlign.center),
                                ),
                                pw.Padding(
                                  padding: const pw.EdgeInsets.all(8.0),
                                  child: pw.Text(DateFormat.yMMMd().format(DateTime.parse(sale.returns?[i].returnDate ?? '0')), textAlign: pw.TextAlign.left),
                                ),
                                pw.Padding(
                                  padding: const pw.EdgeInsets.all(8.0),
                                  child: pw.Text(getDetailsName(detailsId: sale.returns?[i].details?[j].saleDetailId ?? 0), textAlign: pw.TextAlign.left),
                                ),
                                pw.Padding(
                                  padding: const pw.EdgeInsets.all(8.0),
                                  child: pw.Text(sale.returns?[i].details?[j].returnQty?.toString() ?? '0', textAlign: pw.TextAlign.center),
                                ),
                                pw.Padding(
                                  padding: const pw.EdgeInsets.all(8.0),
                                  child: pw.Text(sale.returns?[i].details?[j].returnAmount?.toString() ?? '0', textAlign: pw.TextAlign.right),
                                ),
                              ],
                            ),
                      ],
                    ),
                    pw.SizedBox(height: 12),
                    //total return
                    _buildAmountWidget(title: 'Total Returned', value: calculateTotalReturnAmount().toString(), font: interFont),
                    pw.SizedBox(height: 4),
                  ]),

                // total payable
                _buildAmountWidget(title: 'Total Payable', value: sale.totalAmount?.toStringAsFixed(2) ?? '0.0', font: interFont),
                pw.SizedBox(height: 5),
                //paid
                _buildAmountWidget(title: 'Paid', value: sale.paidAmount!.toStringAsFixed(2), font: interFont),
                pw.SizedBox(height: 5),
                //due
                _buildAmountWidget(title: 'Due', value: sale.dueAmount!.toStringAsFixed(2), font: interFont),
                pw.SizedBox(height: 20),
                pw.Align(
                  alignment: pw.AlignmentDirectional.centerStart,
                  child: pw.RichText(
                    text: pw.TextSpan(
                      text: 'In Text: ',
                      style: pw.TextStyle(font: interFont),
                      children: [
                        pw.TextSpan(
                          text: NumberToWord().convert('en-in', sale.totalAmount?.toInt() ?? 0),
                          style: pw.TextStyle(
                            font: interFont,
                            fontWeight: pw.FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                pw.SizedBox(height: 60),
                // Footer Section
                pw.Center(
                  child: pw.Column(
                    children: [
                      pw.Text(
                        'Thank you for choosing us!',
                        style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 20, font: interFont),
                      ),
                      pw.SizedBox(height: 8),
                      pw.Center(
                        child: pw.BarcodeWidget(
                          data: AppConfig.companySite,
                          width: 60,
                          height: 60,
                          barcode: pw.Barcode.qrCode(),
                          drawText: false,
                        ),
                      ),
                      pw.SizedBox(height: 8),
                      pw.Text('Developed by: ${AppConfig.companyName} ', style: pw.TextStyle(font: interFont)),
                    ],
                  ),
                )
              ],
            ),
          ];
        }));
    //
    final byteData = await pdf.save();
    final dir = await getApplicationDocumentsDirectory();
    final file = File('${dir.path}/${AppConfig.appName}-${sale?.invoiceNumber}.pdf');
    await file.writeAsBytes(byteData.buffer.asUint8List(byteData.offsetInBytes, byteData.lengthInBytes));
    EasyLoading.showSuccess('Generate Complete');
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PDFViewerPage(path: file.path),
      ),
    );
    // final byteData = await pdf.save();
    // final dir = await getApplicationDocumentsDirectory();
    // final file = File('${dir.path}/PosPro-2.pdf');
    // await file.writeAsBytes(byteData.buffer.asUint8List(byteData.offsetInBytes, byteData.lengthInBytes));
    // EasyLoading.showSuccess('Generate Complete');
    // Navigator.push(
    //   context,
    //   MaterialPageRoute(
    //     builder: (context) => PDFViewerPage(path: file.path),
    //   ),
    // );
    // await Printing.layoutPdf(onLayout: (format) async => pdf.save());
  }

  //------------------sales report pdf--------------------
  Future<void> generateSaleReportPdf(BuildContext context, List<SaleData>? data, BusinessInformation? business, DateTime? fromDate, DateTime? toDate) async {
    final pw.Document pdf = pw.Document();
    final interFont = await PdfGoogleFonts.notoSansRegular();

    // Show loading indicator
    EasyLoading.show(status: 'Generating PDF');
    final fontDataBengali = await rootBundle.load('fonts/NotoSans/NotoSansBengali-Regular.ttf');
    final fontBengali = pw.Font.ttf(fontDataBengali);

    // final ByteData pdfData = await rootBundle.load('fonts/NotoSans/NotoSans-Regular.ttf');
    // final List<int> bytes = pdfData.buffer.asUint8List();
    // final ByteData byteData = ByteData.sublistView(Uint8List.fromList(bytes));
    // // Create a font object from the byte data
    // final ttf = pw.Font.ttf(byteData.buffer.asByteData());

    // Initialize totals
    double totalAmount = 0;
    double paidAmount = 0;
    double dueAmount = 0;

    // Calculate totals from data
    if (data != null) {
      for (var item in data) {
        totalAmount += item.totalAmount ?? 0;
        paidAmount += item.paidAmount ?? 0;
        dueAmount += item.dueAmount ?? 0;
      }
    }
    // print('--font family---$ttf---------------');

    try {
      pdf.addPage(pw.MultiPage(
        pageFormat: PdfPageFormat.letter.copyWith(marginBottom: 1.5 * PdfPageFormat.cm),
        margin: pw.EdgeInsets.symmetric(horizontal: 16),
        //----------------pdf header--------------
        header: (pw.Context context) {
          return pw.Center(
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.center,
              children: [
                pw.Text(
                  business!.companyName.toString(),
                  style: pw.TextStyle(
                    font: interFont,
                    fontWeight: pw.FontWeight.bold,
                    fontSize: 20,
                  ),
                ),
                pw.Text(
                  // 'বিক্রয় প্রতিবেদন',
                  'Sales Report',
                  style: pw.TextStyle(
                    fontSize: 16,
                    fontWeight: pw.FontWeight.bold,
                    // font: ttf,
                  ),
                ),
                pw.SizedBox(height: 4),
                pw.Text(
                  fromDate != null ? 'Duration: ${DateFormat('dd-MM-yyyy').format(fromDate)} to ${DateFormat('dd-MM-yyyy').format(toDate!)}' : '',
                  style: pw.TextStyle(
                    font: interFont,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          );
        },
        //-----------------pdf footer-------------
        footer: (pw.Context context) {
          return pw.Row(
            mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
            children: [
              pw.Text('Development By:${AppConfig.companySite}'),
              pw.Text('Page-${context.pageNumber}'),
            ],
          );
        },
        //----------------pdf body----------------
        build: (pw.Context context) => [
          pw.Column(
            children: [
              pw.SizedBox(height: 16),
              // Report table
              pw.Table(
                border: const pw.TableBorder(
                  verticalInside: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                  left: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                  right: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                  bottom: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                ),
                columnWidths: <int, pw.TableColumnWidth>{
                  0: const pw.FlexColumnWidth(1),
                  1: const pw.FlexColumnWidth(2),
                  2: const pw.FlexColumnWidth(3),
                  3: const pw.FlexColumnWidth(3),
                  4: const pw.FlexColumnWidth(2),
                  5: const pw.FlexColumnWidth(2),
                  6: const pw.FlexColumnWidth(2),
                  7: const pw.FlexColumnWidth(2),
                },
                children: [
                  // PDF header
                  pw.TableRow(
                    children: [
                      for (var text in ['SL', 'Invoice', 'Date', 'Phone', 'Total', 'Paid', 'Due', 'Status'])
                        _buildTableRowHeader(
                          text,
                          Color(0xff00987F),
                          pw.TextAlign.center,
                        ),
                    ],
                  ),
                  for (int i = 0; i < data!.length; i++)
                    pw.TableRow(
                      decoration: i % 2 == 0
                          ? const pw.BoxDecoration(
                              color: PdfColors.white,
                            ) // Odd row color
                          : pw.BoxDecoration(
                              color: PdfColor.fromInt(0xffF7F7F7),
                            ),
                      children: [
                        for (var cell in [
                          {'text': '${i + 1}', 'align': pw.TextAlign.center},
                          {'text': data[i].invoiceNumber ?? 'n/a', 'align': pw.TextAlign.center},
                          {'text': DateFormat('dd-MM-yyyy').format(DateTime.parse(data[i].saleDate.toString())), 'align': pw.TextAlign.center},
                          {'text': data[i].party?.phone ?? 'n/a', 'align': pw.TextAlign.center},
                          {'text': data[i].totalAmount.toString(), 'align': pw.TextAlign.center},
                          {'text': data[i].paidAmount.toString(), 'align': pw.TextAlign.center},
                          {'text': data[i].dueAmount.toString(), 'align': pw.TextAlign.center},
                          {
                            'text': data[i].paidAmount == 0
                                ? 'Due'
                                : data[i].dueAmount == 0
                                    ? 'Paid'
                                    : 'Partial',
                            'align': pw.TextAlign.center
                          },
                        ])
                          pw.Padding(
                            padding: const pw.EdgeInsets.all(8.0),
                            child: pw.Text(cell['text'] as String, textAlign: cell['align'] as pw.TextAlign),
                          ),
                      ],
                    ),
                ],
              ),
              // Totals row
              pw.Table(
                columnWidths: <int, pw.TableColumnWidth>{
                  0: const pw.FlexColumnWidth(1),
                  1: const pw.FlexColumnWidth(2),
                  2: const pw.FlexColumnWidth(3),
                  3: const pw.FlexColumnWidth(3),
                  4: const pw.FlexColumnWidth(2),
                  5: const pw.FlexColumnWidth(2),
                  6: const pw.FlexColumnWidth(2),
                  7: const pw.FlexColumnWidth(2),
                },
                border: const pw.TableBorder(
                  left: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                  right: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                  bottom: pw.BorderSide(color: PdfColor.fromInt(0xffD9D9D9)),
                ),
                children: [
                  pw.TableRow(
                    decoration: pw.BoxDecoration(
                      color: PdfColor.fromInt(0xff00987F),
                    ),
                    children: [
                      for (var cell in [
                        '',
                        '',
                        '',
                        '',
                        totalAmount.toStringAsFixed(2),
                        paidAmount.toStringAsFixed(2),
                        dueAmount.toStringAsFixed(2),
                        '',
                      ])
                        pw.Padding(
                          padding: const pw.EdgeInsets.all(8.0),
                          child: pw.Text(
                            cell,
                            textAlign: pw.TextAlign.center,
                            style: pw.TextStyle(fontWeight: pw.FontWeight.bold, color: PdfColors.white),
                          ),
                        ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ],
      ));

      final byteData = await pdf.save();
      final dir = await getApplicationDocumentsDirectory();
      final file = File('${dir.path}/${AppConfig.appName}-sales report.pdf');
      await file.writeAsBytes(byteData.buffer.asUint8List(byteData.offsetInBytes, byteData.lengthInBytes));
      EasyLoading.showSuccess('Generate Complete');
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PDFViewerPage(path: file.path),
        ),
      );
    } catch (e) {
      EasyLoading.showError('Error: $e');
      print('Error during PDF generation: $e');
    }
  }
}

//header data widget
pw.Widget _buildHeaderData({required String key, required double keyWidth, required String value, required double valueWidth, required pw.Font font}) {
  return pw.Row(children: [
    pw.SizedBox(
      width: keyWidth,
      child: pw.Text(
        key,
        style: pw.TextStyle(font: font),
      ),
    ),
    pw.SizedBox(
      width: 10.0,
      child: pw.Text(
        ':',
        style: pw.TextStyle(font: font),
      ),
    ),
    pw.SizedBox(
      width: valueWidth,
      child: pw.Text(
        value,
        style: pw.TextStyle(font: font),
      ),
    ),
  ]);
}

//Table Row header
pw.Widget _buildTableRowHeader(String title, Color color, pw.TextAlign alignment) {
  return pw.Container(
    decoration: pw.BoxDecoration(
      color: PdfColor.fromInt(color.value),
    ),
    padding: const pw.EdgeInsets.all(8.0),
    child: pw.Text(
      title,
      textAlign: alignment,
      style: pw.TextStyle(color: PdfColors.white, fontWeight: pw.FontWeight.bold),
    ),
  );
}

//product calculation widget
pw.Widget _buildAmountWidget({required String title, required String value, required pw.Font font, pw.Widget? amountText}) {
  return pw.Row(children: [
    amountText ?? pw.Spacer(flex: 4),
    pw.Expanded(
      flex: 2,
      child: pw.Row(
        children: [
          pw.Expanded(
            flex: 1,
            child: pw.Text('$title :', style: pw.TextStyle(font: font, fontWeight: pw.FontWeight.bold, color: PdfColors.black), textAlign: pw.TextAlign.end),
          ),
          pw.Expanded(
            flex: 1,
            child: pw.Text(
              value.toString(),
              style: pw.TextStyle(font: font, fontWeight: pw.FontWeight.bold, color: PdfColors.black),
              textAlign: pw.TextAlign.end,
            ),
          ),
        ],
      ),
    ),
  ]);
}
