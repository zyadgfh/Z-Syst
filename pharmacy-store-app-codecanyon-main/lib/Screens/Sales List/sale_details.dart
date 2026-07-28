import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/PDF%20Invoice/sales_pdf/sales_pdf.dart';
import '../../app_config/app_config.dart';
import '../Sales/Model/sales_details_model.dart';
import 'provider/sales_details_provider.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:number_to_words/number_to_words.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../../thermal priting invoices/provider/print_thermal_invoice_provider.dart';
import '../../Provider/profile_provider.dart';
import '../../constant.dart';
import '../../currency.dart';
import '../../thermal priting invoices/model/print_transaction_model.dart';
import '../widget/about_comapny.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;

class SalesDetailsScreen extends StatefulWidget {
  const SalesDetailsScreen({super.key, required this.id});
  final num id;

  @override
  State<SalesDetailsScreen> createState() => _SalesDetailsScreenState();
}

class _SalesDetailsScreenState extends State<SalesDetailsScreen> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(
      builder: (BuildContext context, WidgetRef ref, Widget? child) {
        final data = ref.watch(salesDetailsProvider(widget.id));
        final printerData = ref.watch(thermalPrinterProvider);

        final personalData = ref.watch(businessInfoProvider);
        return data.when(data: (snapshot) {
          return personalData.when(data: (shopInfo) {
            String getDetailsName({required num detailsId}) {
              if (snapshot?.data?.details != null && (snapshot?.data?.details?.isNotEmpty ?? false)) {
                return snapshot!.data!.details!
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

            final SalesDetailsData? showableSalesDetails = (snapshot?.data?.returns?.isEmpty ?? true) ? snapshot?.data : snapshot?.data?.salesData;

            // sub total
            num subTotal() {
              num totalSum = 0;
              for (var product in showableSalesDetails?.details ?? []) {
                totalSum += (product?.price ?? 0) * (product?.quantities ?? 1);
              }
              if (showableSalesDetails?.returns?.isNotEmpty ?? false) {
                for (var product in showableSalesDetails?.returns ?? []) {
                  totalSum += (product?.price ?? 0) * (product?.quantities ?? 1);
                }
              }

              return totalSum;
            }

            // Total Return
            num totalReturned = 0;

            return AcnooScafoldWidget(
              appBar: AppBar(
                backgroundColor: Colors.transparent,
                title: Text(
                  lang.invoice,
                  style: theme.textTheme.titleLarge?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 20,
                  ),
                ),
                centerTitle: true,
                iconTheme: const IconThemeData(color: Colors.white),
                elevation: 0.0,
                actions: [
                  GlobalIconButton(onPressed: () => GenerateSalesPdf().generateSaleDetailsInvoice(context, snapshot?.data, shopInfo), icon: pdfIcon),
                  SizedBox(
                    width: 10,
                  ),
                  GlobalIconButton(
                      onPressed: () async {
                        PrintThermalSalesInvoiceModel model = PrintThermalSalesInvoiceModel(salesDetails: snapshot, personalInformationModel: shopInfo);

                        await printerData.printThermalNow(context: context, transaction: model);
                      },
                      icon: printIcon),
                  SizedBox(width: 16),
                ],
              ),
              body: Padding(
                padding: const EdgeInsets.all(16.0),
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.start,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      // Heading
                      Center(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              height: 100,
                              width: 100,
                              decoration: BoxDecoration(
                                image: DecorationImage(
                                  image: AssetImage(AppConfig.logo),
                                  fit: BoxFit.cover,
                                ),
                              ),
                            ),
                            Text(
                              shopInfo.companyName ?? 'n/a',
                              style: theme.textTheme.titleLarge?.copyWith(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 25.0),
                              child: Text(
                                '${lang.address}: ${shopInfo.companyName ?? 'n/a'}',
                                style: theme.textTheme.bodyMedium,
                                maxLines: 2,
                                textAlign: TextAlign.center,
                              ),
                            ),
                            Text(
                              '${lang.mobile} ${shopInfo.phoneNumber.toString() ?? 'n/a'}',
                              style: theme.textTheme.bodyMedium,
                              maxLines: 2,
                            ),
                            Text(
                              '${lang.emailText}: ${shopInfo.user?.email.toString() ?? 'n/a'}',
                              style: theme.textTheme.bodyMedium,
                              maxLines: 2,
                            ),
                          ],
                        ),
                      ),
                      SizedBox.square(dimension: 40),
                      // Info Section
                      ...{
                        "${lang.dateAndTime}:": DateFormat('dd/MM/yyyy, h:mm a').format(
                          DateTime.parse(
                            showableSalesDetails?.saleDate.toString() ?? '',
                          ),
                        ),
                        "${lang.invoiceNumber}:": snapshot?.data?.invoiceNumber ?? 'n/a',
                        "${lang.partyName}:": snapshot?.data?.party?.name ?? (snapshot?.data?.party?.phone) ?? 'Guest',
                        "${lang.partyPhone}:": snapshot?.data?.party?.phone ?? 'Guest',
                        "${lang.paymentTypes}:": snapshot?.data?.paymentType ?? 'n/a',
                        lang.salesBy: snapshot?.data?.salesBy?.name ?? 'n/a',
                      }.entries.map(
                        (entry) {
                          return Padding(
                            padding: EdgeInsets.only(bottom: 4),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Flexible(
                                  child: Text(
                                    entry.key,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
                                  ),
                                ),
                                SizedBox(width: 4),
                                Text(
                                  entry.value,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: theme.textTheme.bodyMedium?.copyWith(color: kTitleColor),
                                )
                              ],
                            ),
                          );
                        },
                      ),
                      SizedBox(height: 20),
                      GlobalDottedBorder(),
                      // Data Table Section
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          children: [
                            Flexible(
                              flex: 2,
                              fit: FlexFit.tight,
                              child: Text(
                                lang.name,
                                textAlign: TextAlign.start,
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ),
                            Flexible(
                              flex: 1,
                              fit: FlexFit.tight,
                              child: Text(
                                lang.qty,
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ),
                            Flexible(
                              flex: 1,
                              fit: FlexFit.tight,
                              child: Text(
                                lang.price,
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ),
                            Flexible(
                              flex: 1,
                              fit: FlexFit.tight,
                              child: Text(
                                lang.amount,
                                textAlign: TextAlign.end,
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                      ),
                      GlobalDottedBorder(),
                      Column(
                        children: List.generate(
                          (showableSalesDetails?.returns?.isNotEmpty ?? false)
                              ? (showableSalesDetails?.salesData?.details?.length ?? 0)
                              : showableSalesDetails?.details?.length ?? 0,
                          (index) {
                            var product = (showableSalesDetails?.salesData != null && (showableSalesDetails?.returns?.isNotEmpty ?? false))
                                ? (showableSalesDetails?.salesData?.details?[index])
                                : showableSalesDetails?.details?[index];

                            return Column(
                              children: [
                                Padding(
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  child: Row(
                                    children: [
                                      Flexible(
                                        flex: 2,
                                        fit: FlexFit.tight,
                                        child: Text(
                                          product?.product?.productName ?? 'n/a',
                                          textAlign: TextAlign.start,
                                        ),
                                      ),
                                      Flexible(
                                        flex: 1,
                                        fit: FlexFit.tight,
                                        child: Text(product?.quantities.toString() ?? 'n/a'),
                                      ),
                                      Flexible(
                                        flex: 1,
                                        fit: FlexFit.tight,
                                        child: Text('$currency${product?.price.toString() ?? 'n/a'}'),
                                      ),
                                      Flexible(
                                        flex: 1,
                                        fit: FlexFit.tight,
                                        child: Text(
                                          '${currency ?? ''}${((product?.price ?? 0) * (product?.quantities ?? 1)).toStringAsFixed(2)}',
                                          textAlign: TextAlign.end,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                GlobalDottedBorder(),
                              ],
                            );
                          },
                        ),
                      ),
                      SizedBox(height: 8),
                      // Summary Section
                      Column(
                        spacing: 4,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.start,
                            children: [
                              Spacer(),
                              Expanded(
                                child: Text(
                                  lang.subDtotal,
                                  style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                                ),
                              ),
                              Flexible(
                                child: Align(
                                  alignment: AlignmentDirectional.centerEnd,
                                  child: Text(
                                    '$currency${subTotal().toStringAsFixed(2)}',
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.start,
                            children: [
                              Spacer(),
                              Expanded(
                                child: Text(
                                  lang.discount,
                                  style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                                ),
                              ),
                              Flexible(
                                child: Align(
                                  alignment: AlignmentDirectional.centerEnd,
                                  child: Text(
                                    "$currency${showableSalesDetails?.discountAmount?.toStringAsFixed(2) ?? '0.0'}",
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.start,
                            children: [
                              Spacer(),
                              Expanded(
                                child: Text(
                                  showableSalesDetails?.tax?.name ?? lang.vat,
                                  style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                                ),
                              ),
                              Flexible(
                                child: Align(
                                  alignment: AlignmentDirectional.centerEnd,
                                  child: Text(
                                    "$currency${showableSalesDetails?.taxAmount?.toStringAsFixed(2) ?? '0.0'}",
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          Divider(
                            thickness: 1,
                            height: 8,
                            indent: 115,
                          ),
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.start,
                            children: [
                              Spacer(),
                              Expanded(
                                child: Text(
                                  lang.totalAmount,
                                  style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700, fontSize: 14, fontWeight: FontWeight.w600),
                                ),
                              ),
                              Flexible(
                                child: Align(
                                  alignment: AlignmentDirectional.centerEnd,
                                  child: Text(
                                    // snapshot?.data?.totalAmount?.toStringAsFixed(2) ?? '0.0',
                                    "$currency${showableSalesDetails?.totalAmount?.toStringAsFixed(2) ?? '0.0'}",
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700, fontSize: 14, fontWeight: FontWeight.w600),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),

                      // Return Data Table Section
                      if (snapshot?.data?.returns?.isNotEmpty ?? false)
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            SizedBox(height: 24),
                            Text(
                              lang.retur,
                              style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            SizedBox(height: 12),
                            GlobalDottedBorder(),
                            // Data Table Section
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              child: Row(
                                children: [
                                  Flexible(
                                    flex: 2,
                                    fit: FlexFit.tight,
                                    child: Text(
                                      lang.name,
                                      textAlign: TextAlign.start,
                                      style: TextStyle(fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                  Flexible(
                                    flex: 1,
                                    fit: FlexFit.tight,
                                    child: Text(
                                      lang.qty,
                                      style: TextStyle(fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                  Flexible(
                                    flex: 1,
                                    fit: FlexFit.tight,
                                    child: Text(
                                      lang.amount,
                                      textAlign: TextAlign.end,
                                      style: TextStyle(fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            GlobalDottedBorder(),
                            ListView.builder(
                              padding: EdgeInsets.zero,
                              shrinkWrap: true,
                              physics: NeverScrollableScrollPhysics(),
                              itemCount: snapshot?.data?.returns?.length ?? 0,
                              itemBuilder: (context, index) {
                                var saleReturn = snapshot?.data?.returns?[index];
                                return Column(
                                  children: List.generate(
                                    saleReturn?.details?.length ?? 0,
                                    (detailIndex) {
                                      var returnDetail = saleReturn?.details?[detailIndex];
                                      totalReturned += returnDetail?.returnAmount ?? 0;
                                      return Column(
                                        children: [
                                          Padding(
                                            padding: const EdgeInsets.symmetric(vertical: 8),
                                            child: Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                              children: [
                                                Flexible(
                                                  flex: 2,
                                                  fit: FlexFit.tight,
                                                  child: Column(
                                                    crossAxisAlignment: CrossAxisAlignment.start,
                                                    children: [
                                                      Text(
                                                        getDetailsName(
                                                          detailsId: returnDetail?.saleDetailId ?? 0,
                                                        ),
                                                        maxLines: 1,
                                                      ),
                                                      Text(
                                                        DateFormat('dd/MM/yyyy').format(
                                                          DateTime.parse(
                                                            saleReturn?.returnDate.toString() ?? '',
                                                          ),
                                                        ),
                                                        maxLines: 1,
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                                // Quantity text
                                                Flexible(flex: 1, fit: FlexFit.tight, child: Text(returnDetail?.returnQty?.toString() ?? 'n/a')),
                                                // Amount text
                                                Flexible(
                                                  flex: 1,
                                                  fit: FlexFit.tight,
                                                  child: Text(
                                                    '$currency${returnDetail?.returnAmount?.toString() ?? 'n/a'}',
                                                    textAlign: TextAlign.end,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                          GlobalDottedBorder(),
                                        ],
                                      );
                                    },
                                  ),
                                );
                              },
                            ),
                            // Theme(
                            //   data: ThemeData(
                            //     dividerColor: kBorderColorTextField,
                            //     dividerTheme: DividerThemeData(thickness: 1, color: kBorderColorTextField),
                            //   ),
                            //   child: SizedBox(
                            //     width: MediaQuery.of(context).size.width,
                            //     child: DataTable(
                            //       horizontalMargin: 0,
                            //       showBottomBorder: true,
                            //       dividerThickness: 0,
                            //       decoration: BoxDecoration(
                            //         border: Border.symmetric(horizontal: BorderSide(color: kBorderColorTextField, width: 0)),
                            //       ),
                            //       columns: [
                            //         DataColumn(
                            //           label: Text(
                            //             lang.name,
                            //             textAlign: TextAlign.start,
                            //             style: TextStyle(fontWeight: FontWeight.bold),
                            //           ),
                            //         ),
                            //         DataColumn(
                            //           label: Text(
                            //             lang.qty,
                            //             style: TextStyle(fontWeight: FontWeight.bold),
                            //           ),
                            //         ),
                            //         DataColumn(
                            //           headingRowAlignment: MainAxisAlignment.end,
                            //           label: Text(
                            //             lang.amount,
                            //             textAlign: TextAlign.end,
                            //             style: TextStyle(fontWeight: FontWeight.bold),
                            //           ),
                            //         ),
                            //       ],
                            //       rows: List<DataRow>.from(
                            //         List.generate(
                            //           snapshot?.data?.returns?.length ?? 0,
                            //           (index) {
                            //             var saleReturn = snapshot?.data?.returns?[index];
                            //             return List<DataRow>.generate(
                            //               saleReturn?.details?.length ?? 0,
                            //               (detailIndex) {
                            //                 var returnDetail = saleReturn?.details?[detailIndex];
                            //                 totalReturned += returnDetail?.returnAmount ?? 0;
                            //                 return DataRow(
                            //                   cells: [
                            //                     DataCell(
                            //                       Column(
                            //                         crossAxisAlignment: CrossAxisAlignment.start,
                            //                         mainAxisAlignment: MainAxisAlignment.center,
                            //                         children: [
                            //                           Text(
                            //                             getDetailsName(
                            //                               detailsId: returnDetail?.saleDetailId ?? 0,
                            //                             ),
                            //                             maxLines: 1,
                            //                           ),
                            //                           Text(
                            //                             DateFormat('dd/MM/yyyy').format(
                            //                               DateTime.parse(
                            //                                 saleReturn?.returnDate.toString() ?? '',
                            //                               ),
                            //                             ),
                            //                             maxLines: 1,
                            //                           ),
                            //                         ],
                            //                       ),
                            //                     ),
                            //                     DataCell(Text(returnDetail?.returnQty?.toString() ?? 'n/a')),
                            //                     DataCell(
                            //                       Align(
                            //                         alignment: Alignment.centerRight,
                            //                         child: Text(
                            //                           '$currency${returnDetail?.returnAmount?.toString() ?? 'n/a'}',
                            //                         ),
                            //                       ),
                            //                     ),
                            //                   ],
                            //                 );
                            //               },
                            //             );
                            //           },
                            //         ).expand((row) => row),
                            //       ),
                            //     ),
                            //   ),
                            // ),
                            SizedBox(height: 8),
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.start,
                              children: [
                                Spacer(),
                                Expanded(
                                  child: Text(
                                    lang.totalReturn,
                                    style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                                  ),
                                ),
                                Flexible(
                                  child: Align(
                                    alignment: AlignmentDirectional.centerEnd,
                                    child: Text(
                                      '$currency${totalReturned.toStringAsFixed(2)}',
                                      style: theme.textTheme.bodyMedium?.copyWith(
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.start,
                        children: [
                          Spacer(),
                          Expanded(
                            child: Text(
                              lang.totalPayable,
                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                            ),
                          ),
                          Flexible(
                            child: Align(
                              alignment: AlignmentDirectional.centerEnd,
                              child: Text(
                                '$currency${((snapshot?.data?.totalAmount ?? 0)).toStringAsFixed(2)}',
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.start,
                        children: [
                          Spacer(),
                          Expanded(
                            child: Text(
                              lang.paid,
                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                            ),
                          ),
                          Flexible(
                            child: Align(
                              alignment: AlignmentDirectional.centerEnd,
                              child: Text(
                                "$currency${snapshot?.data?.paidAmount?.toStringAsFixed(2) ?? '0.0'}",
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.start,
                        children: [
                          Spacer(),
                          Expanded(
                            child: Text(
                              lang.due,
                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700),
                            ),
                          ),
                          Flexible(
                            child: Align(
                              alignment: AlignmentDirectional.centerEnd,
                              child: Text(
                                "$currency${snapshot?.data?.dueAmount?.toStringAsFixed(2) ?? '0.0'}",
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      SizedBox(height: 50),
                      RichText(
                        maxLines: 2,
                        text: TextSpan(
                          text: '${lang.inText}: ',
                          style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                          children: [
                            TextSpan(
                              text: NumberToWord().convert('en-in', snapshot?.data?.totalAmount?.toInt() ?? 0),
                              style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w400, color: kGreyTextColor),
                            ),
                          ],
                        ),
                      ),
                      SizedBox(height: 50),
                      Center(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Text(
                              lang.thankYouForChoosingUs,
                              style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700, fontSize: 20),
                            ),
                            QrImageView(
                              data: companyWebstiteUrl,
                              version: QrVersions.auto,
                              size: 80.0,
                            ),
                            Text(
                              '${lang.developedBy} $companyWebstiteUrl',
                              style: theme.textTheme.bodyLarge?.copyWith(color: inNutral700),
                            )
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }, error: (error, stackTrace) {
            return Text(error.toString());
          }, loading: () {
            return Center(child: CircularProgressIndicator());
          });
        }, error: (error, stackTrace) {
          return Text(error.toString());
        }, loading: () {
          return Center(
            child: const CircularProgressIndicator(),
          );
        });
      },
    );
  }
}
