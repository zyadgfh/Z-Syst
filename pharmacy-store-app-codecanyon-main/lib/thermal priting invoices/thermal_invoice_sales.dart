import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/thermal%20priting%20invoices/widget/wave_printer_functions.dart';
import 'package:nb_utils/nb_utils.dart';
import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';
import '../Screens/Sales/Model/sales_details_model.dart';
import '../app_config/app_config.dart';
import 'model/print_transaction_model.dart';

class SalesThermalPrinterInvoice {
  Future<void> printSalesThermalTicket({required PrintThermalSalesInvoiceModel printTransactionModel}) async {
    bool? isConnected = await PrintBluetoothThermal.connectionStatus;
    if (isConnected == true) {
      List<int> bytes = await getSalesTicket(printTransactionModel: printTransactionModel);
      if (printTransactionModel.salesDetails?.data?.details?.isNotEmpty ?? false) {
        await PrintBluetoothThermal.writeBytes(bytes);
        EasyLoading.showSuccess('Successfully Printed');
      } else {
        toast('No Product Found');
      }
    } else {
      EasyLoading.showError('Unable to connect with printer');
    }
  }

  Future<List<int>> getSalesTicket({required PrintThermalSalesInvoiceModel printTransactionModel}) async {
    List<DateTime> returnedDates = [];
    String productName({required num detailsId}) {
      return printTransactionModel.salesDetails!.data!.details!.where((element) => element.id == detailsId).first.product?.productName ?? '';
    }

    num getSubTotal({required SalesDetailsData data}) {
      num total = 0;
      for (var element in data.details!) {
        total += (element.quantities ?? 0) * (element.price ?? 0);
      }
      return total;
    }

    num getTotalReturndAmount() {
      num totalReturn = 0;
      if (printTransactionModel.salesDetails?.data?.returns?.isNotEmpty ?? false) {
        for (var returns in printTransactionModel.salesDetails!.data!.returns!) {
          if (returns.details?.isNotEmpty ?? false) {
            for (var details in returns.details!) {
              totalReturn += details.returnAmount ?? 0;
            }
          }
        }
      }
      return totalReturn;
    }

    SalesDetailsData? shoableSalesDetails =
        (printTransactionModel.salesDetails?.data?.returns?.isEmpty ?? true) ? printTransactionModel.salesDetails?.data : printTransactionModel.salesDetails?.data?.salesData;

    List<SalesDetails> productList = shoableSalesDetails?.details ?? [];

    List<int> bytes = [];
    CapabilityProfile profile = await CapabilityProfile.load();
    final generator = Generator(PaperSize.mm58, profile);
    bytes += generator.text(printTransactionModel.personalInformationModel.companyName ?? '',
        styles: const PosStyles(
          align: PosAlign.center,
          height: PosTextSize.size2,
          width: PosTextSize.size2,
        ),
        linesAfter: 1);

    bytes += generator.text('Seller :${printTransactionModel.salesDetails?.data?.salesBy?.name}', styles: const PosStyles(align: PosAlign.center));
    bytes += generator.text(printTransactionModel.personalInformationModel.address ?? '', styles: const PosStyles(align: PosAlign.center));
    bytes += generator.text('Tel: ${printTransactionModel.personalInformationModel.phoneNumber ?? ''}', styles: const PosStyles(align: PosAlign.center), linesAfter: 1);
    bytes += generator.text('Name: ${printTransactionModel.salesDetails?.data?.party == null ? 'Guest' : printTransactionModel.salesDetails?.data?.party?.name ?? 'N/A'}',
        styles: const PosStyles(align: PosAlign.left));
    bytes += generator.text('mobile: ${printTransactionModel.salesDetails?.data?.party?.phone ?? 'Guest'}', styles: const PosStyles(align: PosAlign.left));
    // bytes += generator.text('Sales By: ${printTransactionModel.transitionModel?.user?.name ?? 'Not Provided'}', styles: const PosStyles(align: PosAlign.left));
    bytes += generator.text('Invoice: ${printTransactionModel.salesDetails?.data?.invoiceNumber ?? 'Not Provided'}', styles: const PosStyles(align: PosAlign.left), linesAfter: 1);

    ///__________________Product_table_title______________________________________
    bytes += generator.text('Items        Rate   Qty    Total', styles: const PosStyles(bold: true));
    bytes += generator.hr();

    ///__________________Product_table_value______________________________________
    for (var element in productList) {
      bytes += await tableItem(
        items: [
          element.product?.productName ?? '',
          '${element.price?.toStringAsFixed(1)}',
          element.quantities.toString(),
          ((element.price ?? 0) * (element.quantities ?? 0)).toStringAsFixed(1),
        ],
        isFour: true,
      );
    }
    bytes += generator.hr();

    bytes += generator.row([
      PosColumn(
          text: 'Subtotal',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: getSubTotal(data: shoableSalesDetails!).toStringAsFixed(2),
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);
    bytes += generator.row([
      PosColumn(
          text: shoableSalesDetails.tax?.name ?? 'VAT',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: '${shoableSalesDetails.taxAmount?.toStringAsFixed(2) ?? 0}',
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);
    bytes += generator.row([
      PosColumn(
          text: 'Discount',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: ((shoableSalesDetails.discountAmount ?? 0)).toStringAsFixed(2) ?? '',
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);
    bytes += generator.row([
      PosColumn(
          text: 'Total',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: ((shoableSalesDetails.totalAmount ?? 0)).toStringAsFixed(2),
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);

    ///_____Return_table_______________________________
    if (printTransactionModel.salesDetails?.data?.returns?.isNotEmpty ?? false) {
      List.generate(printTransactionModel.salesDetails?.data?.returns?.length ?? 0, (i) {
        bytes += generator.hr();
        if (!returnedDates.any(
            (element) => element.isAtSameMomentAs(DateTime.tryParse(printTransactionModel.salesDetails?.data?.returns?[i].returnDate?.substring(0, 10) ?? '') ?? DateTime.now()))) {
          bytes += generator.row([
            PosColumn(
                text: 'Return-${DateFormat.yMd().format(DateTime.parse(printTransactionModel.salesDetails?.data?.returns?[i].returnDate ?? DateTime.now().toString()))}',
                width: 7,
                styles: const PosStyles(align: PosAlign.left, bold: true)),
            PosColumn(text: 'Qty', width: 2, styles: const PosStyles(align: PosAlign.center, bold: true)),
            PosColumn(text: 'Total', width: 3, styles: const PosStyles(align: PosAlign.right, bold: true)),
          ]);
          bytes += generator.hr();
        }

        List.generate(printTransactionModel.salesDetails?.data?.returns?[i].details?.length ?? 0, (index) {
          returnedDates.add(DateTime.tryParse(printTransactionModel.salesDetails?.data?.returns?[i].returnDate?.substring(0, 10) ?? '') ?? DateTime.now());
          final product = printTransactionModel.salesDetails?.data?.returns?[i].details?[index];
          return bytes += generator.row([
            PosColumn(text: productName(detailsId: product?.saleDetailId ?? 0), width: 7, styles: const PosStyles(align: PosAlign.left)),
            PosColumn(text: product?.returnQty.toString() ?? 'Not Defined', width: 2, styles: const PosStyles(align: PosAlign.center)),
            PosColumn(text: "${(product?.returnAmount ?? 0)}", width: 3, styles: const PosStyles(align: PosAlign.right)),
          ]);
        });
        //
      });
    }
    bytes += generator.hr();

    ///_____Total Returned Amount_______________________________
    if (printTransactionModel.salesDetails?.data?.returns?.isNotEmpty ?? false) {
      bytes += generator.row([
        PosColumn(
            text: 'Returned Amount',
            width: 8,
            styles: const PosStyles(
              align: PosAlign.left,
            )),
        PosColumn(
            text: '${getTotalReturndAmount()}',
            width: 4,
            styles: const PosStyles(
              align: PosAlign.right,
            )),
      ]);
    }
    bytes += generator.row([
      PosColumn(text: 'Total Payable', width: 8, styles: const PosStyles(align: PosAlign.left, bold: true)),
      PosColumn(text: printTransactionModel.salesDetails?.data?.totalAmount.toString() ?? '', width: 4, styles: const PosStyles(align: PosAlign.right, bold: true)),
    ]);

    bytes += generator.row([
      PosColumn(
          text: 'Payment Type',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: printTransactionModel.salesDetails?.data?.paymentType ?? 'Cash',
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);
    bytes += generator.row([
      PosColumn(
          text: 'Paid Amount',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: (printTransactionModel.salesDetails!.data!.totalAmount!.toDouble() - printTransactionModel.salesDetails!.data!.dueAmount!.toDouble()).toStringAsFixed(2),
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);
    bytes += generator.row([
      PosColumn(
          text: 'Due Amount',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: printTransactionModel.salesDetails!.data!.dueAmount.toString(),
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);
    bytes += generator.hr(ch: '=', linesAfter: 1);

    // ticket.feed(2);
    bytes += generator.text('Thank you!', styles: const PosStyles(align: PosAlign.center, bold: true));

    bytes += generator.text(printTransactionModel.salesDetails!.data!.saleDate ?? '', styles: const PosStyles(align: PosAlign.center), linesAfter: 1);

    bytes += generator.text('Note: Goods once sold will not be taken back or exchanged.', styles: const PosStyles(align: PosAlign.center, bold: false), linesAfter: 1);

    bytes += generator.qrcode(AppConfig.companySite);
    bytes += generator.text('');
    bytes += generator.text('Developed By: ${AppConfig.companyName}', styles: const PosStyles(align: PosAlign.center), linesAfter: 1);
    bytes += generator.cut();
    return bytes;
  }
}
