import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../../PDF Invoice/due_pdf/due_pdf.dart';
import '../../Provider/profile_provider.dart';
import '../../app_config/app_config.dart';
import '../../constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../currency.dart';
import '../../thermal priting invoices/model/print_transaction_model.dart';
import '../../thermal priting invoices/provider/print_thermal_invoice_provider.dart';
import '../widget/about_comapny.dart';
import 'Model/collected_due_list_model.dart';

class CollectedDueDetails extends StatefulWidget {
  const CollectedDueDetails({super.key, required this.details});
  final CollectedDueData details;

  @override
  State<CollectedDueDetails> createState() => _CollectedDueDetailsState();
}

class _CollectedDueDetailsState extends State<CollectedDueDetails> {
  num totalSum = 0;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(
      builder: (BuildContext context, WidgetRef ref, Widget? child) {
        final personalData = ref.watch(businessInfoProvider);
        return personalData.when(data: (shopInfo) {
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
                GlobalIconButton(
                  onPressed: () => GenerateDuePdf().generateDueDetailsInvoice(context, widget.details, shopInfo),
                  icon: pdfIcon,
                ),
                SizedBox(width: 10),
                GlobalIconButton(
                  onPressed: () async {
                    PrintDueTransactionModel model = PrintDueTransactionModel(
                      dueTransactionModel: widget.details,
                      personalInformationModel: shopInfo,
                    );
                    await ref.watch(thermalPrinterProvider).printThermalNow(context: context, transaction: model);
                  },
                  icon: printIcon,
                ),
                SizedBox(width: 16)
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
                            '${lang.mobile} ${shopInfo.phoneNumber.toString()}',
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

                    SizedBox(height: 40),

                    // Info Section
                    ...{
                      "${lang.dateAndTime}:": DateFormat('dd/MM/yyyy, h:mm a').format(
                        DateTime.parse(
                          widget.details.paymentDate.toString(),
                        ),
                      ),
                      "${lang.invoiceNumber}:": widget.details.invoiceNumber?.toString() ?? 'n/a',
                      "${lang.billTO}:": widget.details.party?.name?.toString() ?? 'n/a',
                      lang.mobile: widget.details.party?.phone?.toString() ?? 'n/a',
                      "${lang.paymentTypes}:": widget.details.paymentType?.toString() ?? 'n/a',
                      lang.collectedBy: widget.details.collectedBy?.name?.toString() ?? 'n/a',
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
                              lang.total,
                              textAlign: TextAlign.start,
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                          Flexible(
                            flex: 2,
                            fit: FlexFit.tight,
                            child: Text(
                              lang.paid,
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                          Flexible(
                            // flex: 1,
                            fit: FlexFit.tight,
                            child: Text(
                              lang.due,
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                    ),
                    GlobalDottedBorder(),
                    Column(
                      children: List.generate(
                        1,
                        (index) {
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
                                        '$currency${widget.details.totalDue?.toStringAsFixed(2) ?? 'n/a'}',
                                        textAlign: TextAlign.start,
                                      ),
                                    ),
                                    Flexible(
                                      flex: 2,
                                      fit: FlexFit.tight,
                                      child: Text('$currency${widget.details.payDueAmount?.toStringAsFixed(2) ?? 'n/a'}'),
                                    ),
                                    Flexible(
                                      // flex: 2,
                                      fit: FlexFit.tight,
                                      child: Text('$currency${widget.details.dueAmountAfterPay?.toStringAsFixed(2) ?? 'n/a'}'),
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
                    // Data Table Section
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
                    //         // DataColumn(
                    //         //   label: Text(
                    //         //     'Invoice',
                    //         //     textAlign: TextAlign.start,
                    //         //     style: TextStyle(fontWeight: FontWeight.bold),
                    //         //   ),
                    //         // ),
                    //         DataColumn(
                    //           label: Text(
                    //             lang.total,
                    //             style: TextStyle(fontWeight: FontWeight.bold),
                    //           ),
                    //         ),
                    //         DataColumn(
                    //           label: Text(
                    //             lang.paid,
                    //             style: TextStyle(fontWeight: FontWeight.bold),
                    //           ),
                    //         ),
                    //         DataColumn(
                    //           headingRowAlignment: MainAxisAlignment.end,
                    //           label: Text(
                    //             lang.remaining,
                    //             textAlign: TextAlign.end,
                    //             style: TextStyle(fontWeight: FontWeight.bold),
                    //           ),
                    //         ),
                    //       ],
                    //       rows: List.generate(
                    //         1,
                    //         (index) {
                    //           // var product = snapshot?.data?.details?[index];
                    //           // totalSum += (product?.price ?? 0) * (product?.quantities ?? 1);
                    //           return DataRow(
                    //             cells: [
                    //               // DataCell(Text(
                    //               //   widget.details.invoiceNumber?.toString() ?? 'n/a',
                    //               // )),
                    //               DataCell(Text(
                    //                 widget.details.totalDue?.toStringAsFixed(2) ?? 'n/a',
                    //               )),
                    //               DataCell(Text(
                    //                 widget.details.payDueAmount?.toStringAsFixed(2) ?? 'n/a',
                    //               )),
                    //               DataCell(
                    //                 Align(
                    //                   alignment: Alignment.centerRight,
                    //                   child: Text(
                    //                     widget.details.dueAmountAfterPay?.toStringAsFixed(2) ?? 'n/a',
                    //                   ),
                    //                 ),
                    //               ),
                    //             ],
                    //           );
                    //         },
                    //       ),
                    //     ),
                    //   ),
                    // ),
                    SizedBox(height: 30),
                    RichText(
                      maxLines: 2,
                      text: TextSpan(
                        text: '${lang.inText}: ',
                        style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                        children: [
                          TextSpan(
                            text: numberToWords(widget.details.dueAmountAfterPay ?? 0),
                            style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w400, color: kGreyTextColor),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 40),
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
      },
    );
  }
}
