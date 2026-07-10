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
import '../../Screens/Report/model/due_report_model.dart';
import '../../Screens/due_list/Model/collected_due_list_model.dart';
import '../../app_config/app_config.dart';
import '../../model/business_info_model.dart';

class GenerateDuePdf {
  //-----------------due details pdf--------------------------------
  Future<void> generateDueDetailsInvoice(BuildContext context, CollectedDueData? sale, BusinessInformation? shopInfo) async {
    final pdf = pw.Document();
    final interFont = await PdfGoogleFonts.robotoRegular();

    //-------------------image
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
                  width: 270,
                  decoration: const pw.BoxDecoration(
                    color: PdfColor.fromInt(0xff00987F),
                    borderRadius: pw.BorderRadius.only(
                      topLeft: pw.Radius.circular(25),
                      bottomLeft: pw.Radius.circular(25),
                    ),
                  ),
                  child: pw.Text(
                    'Money Receipt',
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
                  key: 'Collected By',
                  keyWidth: 70,
                  value: sale?.collectedBy?.name ?? 'n/a',
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
                    DateTime.parse(sale?.paymentDate.toString() ?? ''),
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
            pw.SizedBox(height: 13)
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
      //-----------------pdf body-----------------
      build: (pw.Context context) => [
        pw.Column(
          children: [
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
                1: const pw.FlexColumnWidth(3),
                2: const pw.FlexColumnWidth(3),
                3: const pw.FlexColumnWidth(3),
              },
              children: [
                //pdf header
                pw.TableRow(
                  children: [
                    _buildTableRowHeader('SL', Color(0xffFF5F00), pw.TextAlign.center),
                    _buildTableRowHeader('Total Due', Color(0xffFF5F00), pw.TextAlign.left),
                    _buildTableRowHeader('Received Amount', Color(0xff00987F), pw.TextAlign.center),
                    _buildTableRowHeader('Remaining Due', Color(0xff00987F), pw.TextAlign.right),
                  ],
                ),
                pw.TableRow(
                  children: [
                    for (var cell in [
                      {'text': '1', 'align': pw.TextAlign.center},
                      {'text': sale?.totalDue?.toStringAsFixed(2) ?? 'n/a', 'align': pw.TextAlign.left},
                      {'text': sale?.payDueAmount?.toStringAsFixed(2) ?? 'n/a', 'align': pw.TextAlign.center},
                      {'text': sale?.dueAmountAfterPay?.toStringAsFixed(2) ?? 'n/a', 'align': pw.TextAlign.right},
                    ])
                      pw.Padding(
                        padding: const pw.EdgeInsets.all(8.0),
                        child: pw.Text(cell['text'] as String, textAlign: cell['align'] as pw.TextAlign),
                      ),
                  ],
                ),
              ],
            ),
            pw.SizedBox(height: 8),
            // Summary Section
            pw.Column(
              children: [
                ...[
                  {'label': 'Payable Amount', 'value': sale?.totalDue?.toStringAsFixed(2) ?? 'n/a'},
                  {'label': 'Received Amount', 'value': sale?.payDueAmount?.toStringAsFixed(2) ?? 'n/a'},
                  {'label': 'Remaining Due', 'value': sale?.dueAmountAfterPay?.toStringAsFixed(2) ?? 'n/a'},
                ].map((entry) {
                  bool isShowText = entry['label'] == 'Payment Amount';
                  return pw.Padding(
                    padding: pw.EdgeInsets.only(bottom: 4),
                    child: pw.Row(
                      mainAxisAlignment: pw.MainAxisAlignment.end,
                      children: [
                        // In Text
                        if (isShowText)
                          pw.Expanded(
                            flex: 3,
                            child: pw.Align(
                              alignment: pw.AlignmentDirectional.centerStart,
                              child: pw.RichText(
                                text: pw.TextSpan(
                                  text: 'In Text: ',
                                  style: pw.TextStyle(font: interFont),
                                  children: [
                                    pw.TextSpan(
                                      text: NumberToWord().convert('en-in', sale?.payDueAmount?.toInt() ?? 0),
                                      style: pw.TextStyle(
                                        font: interFont,
                                        fontWeight: pw.FontWeight.bold,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        if (!isShowText) pw.Spacer(flex: 3),
                        pw.Expanded(
                          flex: 2,
                          child: pw.Container(
                            padding: pw.EdgeInsets.all(2),
                            child: pw.Row(
                              children: [
                                pw.Expanded(
                                  flex: 1,
                                  child: pw.Text('${entry['label']!} :', style: pw.TextStyle(font: interFont, fontWeight: pw.FontWeight.bold), textAlign: pw.TextAlign.end),
                                ),
                                pw.Expanded(
                                  flex: 1,
                                  child: pw.Padding(
                                    padding: pw.EdgeInsets.only(right: isShowText ? 5 : 0),
                                    child: pw.Text(
                                      entry['value']!,
                                      style: pw.TextStyle(font: interFont, fontWeight: pw.FontWeight.bold),
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
                  );
                }),
              ],
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
      ],
    ));
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
    // await Printing.layoutPdf(onLayout: (format) async => pdf.save());
  }

  //------------------due report pdf--------------------
  Future<void> generateDueReportPdf(BuildContext context, List<DueReportData>? data, BusinessInformation? business, DateTime? fromDate, DateTime? toDate) async {
    final pw.Document pdf = pw.Document();
    final interFont = await PdfGoogleFonts.interRegular();

    // Show loading indicator
    EasyLoading.show(status: 'Generating PDF');

    // Initialize totals
    double totalAmount = 0;
    double paidAmount = 0;
    double dueAmount = 0;

    // Calculate totals from data
    if (data != null) {
      for (var item in data) {
        totalAmount += item.totalDue ?? 0;
        paidAmount += item.dueAmountAfterPay ?? 0;
        dueAmount += item.payDueAmount ?? 0;
      }
    }

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
                  'Due Collection',
                  style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold, font: interFont),
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
                  6: const pw.FlexColumnWidth(3),
                },
                children: [
                  // PDF header
                  pw.TableRow(
                    children: [
                      for (var text in [
                        'SL',
                        'Invoice',
                        'Date',
                        'Phone',
                        'Total Due',
                        'After pay',
                        'Pay Amount',
                      ])
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
                          {'text': DateFormat('dd-MM-yyyy').format(DateTime.parse(data[i].paymentDate.toString())), 'align': pw.TextAlign.center},
                          {'text': data[i].party?.phone ?? 'n/a', 'align': pw.TextAlign.center},
                          {'text': data[i].totalDue.toString(), 'align': pw.TextAlign.center},
                          {'text': data[i].dueAmountAfterPay.toString(), 'align': pw.TextAlign.center},
                          {'text': data[i].payDueAmount.toString(), 'align': pw.TextAlign.center},
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
                  6: const pw.FlexColumnWidth(3),
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
      final file = File('${dir.path}/PosPro-1.pdf');
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
