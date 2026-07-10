import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/thermal%20priting%20invoices/widget/wave_printer_functions.dart';
import 'package:nb_utils/nb_utils.dart';
import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';
import '../Screens/Purchase List/model/purchase_details_model.dart';
import '../app_config/app_config.dart';
import '../constant.dart';
import 'model/print_transaction_model.dart';

class PurchaseThermalPrinterInvoice {
  Future<void> printPurchaseThermalInvoice({required PrintThermalPurchaseInvoiceModel printTransactionModel}) async {
    bool isConnected = await PrintBluetoothThermal.connectionStatus;
    if (isConnected == true) {
      List<int> bytes = await getPurchaseTicket(printTransactionModel: printTransactionModel);
      if (printTransactionModel.purchaseTransitionModel?.data?.details?.isNotEmpty ?? false) {
        await PrintBluetoothThermal.writeBytes(bytes);
      } else {
        toast('No Product Found');
      }
    } else {
      EasyLoading.showError('Unable to connect with printer');
    }
  }

  Future<List<int>> getPurchaseTicket({required PrintThermalPurchaseInvoiceModel printTransactionModel}) async {
    List<DateTime> returnedDates = [];
    String productName({required num detailsId}) {
      return printTransactionModel.purchaseTransitionModel!.data!.details!.where((element) => element.id == detailsId).first.product?.productName ?? '';
    }

    num getSubTotal({required PurchaseDetailsData data}) {
      num total = 0;
      for (var element in data.details!) {
        total += (element.quantities ?? 0) * getPurchasePrice(product: element);
      }
      return total;
    }

    num getTotalReturndAmount() {
      num totalReturn = 0;
      if (printTransactionModel.purchaseTransitionModel?.data?.returns?.isNotEmpty ?? false) {
        for (var returns in printTransactionModel.purchaseTransitionModel!.data!.returns!) {
          if (returns.details?.isNotEmpty ?? false) {
            for (var details in returns.details!) {
              totalReturn += details.returnAmount ?? 0;
            }
          }
        }
      }
      return totalReturn;
    }

    PurchaseDetailsData? shoableSalesDetails = (printTransactionModel.purchaseTransitionModel?.data?.returns?.isEmpty ?? true)
        ? printTransactionModel.purchaseTransitionModel?.data
        : printTransactionModel.purchaseTransitionModel?.data?.purchaseData;

    List<Details> productList = shoableSalesDetails?.details ?? [];

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

    bytes += generator.text('Seller :${printTransactionModel.purchaseTransitionModel?.data?.purchaseBy?.name}', styles: const PosStyles(align: PosAlign.center));
    bytes += generator.text(printTransactionModel.personalInformationModel.address ?? '', styles: const PosStyles(align: PosAlign.center));
    bytes += generator.text('Tel: ${printTransactionModel.personalInformationModel.phoneNumber ?? ''}', styles: const PosStyles(align: PosAlign.center), linesAfter: 1);
    bytes += generator.text(
        'Name: ${printTransactionModel.purchaseTransitionModel?.data?.party == null ? "Guest" : printTransactionModel.purchaseTransitionModel?.data?.party?.name ?? 'N/A'}',
        styles: const PosStyles(align: PosAlign.left));
    bytes += generator.text('mobile: ${printTransactionModel.purchaseTransitionModel?.data?.party?.phone ?? 'Guest'}', styles: const PosStyles(align: PosAlign.left));
    // bytes += generator.text('Sales By: ${printTransactionModel.transitionModel?.user?.name ?? 'Not Provided'}', styles: const PosStyles(align: PosAlign.left));
    bytes += generator.text('Invoice: ${printTransactionModel.purchaseTransitionModel?.data?.invoiceNumber ?? 'Not Provided'}',
        styles: const PosStyles(align: PosAlign.left), linesAfter: 1);

    ///__________________Product_table_title______________________________________
    bytes += generator.text('Items        Rate   Qty    Total', styles: const PosStyles(bold: true));
    bytes += generator.hr();

    ///__________________Product_table_value______________________________________
    for (var element in productList) {
      bytes += await tableItem(
        items: [
          element.product?.productName ?? '',
          getPurchasePrice(product: element).toStringAsFixed(1),
          element.quantities.toString(),
          (getPurchasePrice(product: element) * (element.quantities ?? 0)).toStringAsFixed(1),
        ],
        isFour: true,
      );
    }

    // bytes += generator.row([
    //   PosColumn(text: 'Item', width: 4, styles: const PosStyles(align: PosAlign.left, bold: true)),
    //   PosColumn(text: 'Price', width: 3, styles: const PosStyles(align: PosAlign.center, bold: true)),
    //   PosColumn(text: 'Qty', width: 2, styles: const PosStyles(align: PosAlign.center, bold: true)),
    //   PosColumn(text: 'Amount', width: 3, styles: const PosStyles(align: PosAlign.right, bold: true)),
    // ]);
    // bytes += generator.hr();
    // List.generate(productList.length, (index) {
    //   return bytes += generator.row([
    //     PosColumn(
    //         text: productList[index].product?.productName ?? '',
    //         width: 4,
    //         styles: const PosStyles(
    //           align: PosAlign.left,
    //         )),
    //     PosColumn(
    //         text: getPurchasePrice(product: productList[index]).toStringAsFixed(1),
    //         width: 3,
    //         styles: const PosStyles(
    //           align: PosAlign.center,
    //         )),
    //     PosColumn(text: productList[index].quantities.toString(), width: 2, styles: const PosStyles(align: PosAlign.center)),
    //     PosColumn(
    //         text: (getPurchasePrice(product: productList[index]) * (productList[index].quantities ?? 0)).toStringAsFixed(1),
    //         width: 3,
    //         styles: const PosStyles(align: PosAlign.right)),
    //   ]);
    // });
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
          text: ((shoableSalesDetails?.discountAmount ?? 0)).toStringAsFixed(2) ?? '',
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
          text: ((shoableSalesDetails?.totalAmount ?? 0)).toStringAsFixed(2),
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);

    ///_____Return_table_______________________________
    if (printTransactionModel.purchaseTransitionModel?.data?.returns?.isNotEmpty ?? false) {
      List.generate(printTransactionModel.purchaseTransitionModel?.data?.returns?.length ?? 0, (i) {
        bytes += generator.hr();
        if (!returnedDates.any((element) =>
            element.isAtSameMomentAs(DateTime.tryParse(printTransactionModel.purchaseTransitionModel?.data?.returns?[i].returnDate?.substring(0, 10) ?? '') ?? DateTime.now()))) {
          bytes += generator.row([
            PosColumn(
                text: 'Return-${DateFormat.yMd().format(DateTime.parse(printTransactionModel.purchaseTransitionModel?.data?.returns?[i].returnDate ?? DateTime.now().toString()))}',
                width: 7,
                styles: const PosStyles(align: PosAlign.left, bold: true)),
            PosColumn(text: 'Qty', width: 2, styles: const PosStyles(align: PosAlign.center, bold: true)),
            PosColumn(text: 'Total', width: 3, styles: const PosStyles(align: PosAlign.right, bold: true)),
          ]);
          bytes += generator.hr();
        }

        List.generate(printTransactionModel.purchaseTransitionModel?.data?.returns?[i].details?.length ?? 0, (index) {
          returnedDates.add(DateTime.tryParse(printTransactionModel.purchaseTransitionModel?.data?.returns?[i].returnDate?.substring(0, 10) ?? '') ?? DateTime.now());
          final product = printTransactionModel.purchaseTransitionModel?.data?.returns?[i].details?[index];
          return bytes += generator.row([
            PosColumn(text: productName(detailsId: product?.purchaseDetailId ?? 0), width: 7, styles: const PosStyles(align: PosAlign.left)),
            PosColumn(text: product?.returnQty.toString() ?? 'Not Defined', width: 2, styles: const PosStyles(align: PosAlign.center)),
            PosColumn(text: "${(product?.returnAmount ?? 0)}", width: 3, styles: const PosStyles(align: PosAlign.right)),
          ]);
        });
        //
      });
    }
    bytes += generator.hr();

    ///_____Total Returned Amount_______________________________
    if (printTransactionModel.purchaseTransitionModel?.data?.returns?.isNotEmpty ?? false) {
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
      PosColumn(text: printTransactionModel.purchaseTransitionModel?.data?.totalAmount.toString() ?? '', width: 4, styles: const PosStyles(align: PosAlign.right, bold: true)),
    ]);

    bytes += generator.row([
      PosColumn(
          text: 'Payment Type',
          width: 8,
          styles: const PosStyles(
            align: PosAlign.left,
          )),
      PosColumn(
          text: printTransactionModel.purchaseTransitionModel?.data?.paymentType ?? 'Cash',
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
          text: (printTransactionModel.purchaseTransitionModel!.data!.totalAmount!.toDouble() - printTransactionModel.purchaseTransitionModel!.data!.dueAmount!.toDouble())
              .toStringAsFixed(2),
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
          text: printTransactionModel.purchaseTransitionModel!.data!.dueAmount.toString(),
          width: 4,
          styles: const PosStyles(
            align: PosAlign.right,
          )),
    ]);
    bytes += generator.hr(ch: '=', linesAfter: 1);

    // ticket.feed(2);
    bytes += generator.text('Thank you!', styles: const PosStyles(align: PosAlign.center, bold: true));

    bytes += generator.text(printTransactionModel.purchaseTransitionModel!.data!.purchaseDate ?? '', styles: const PosStyles(align: PosAlign.center), linesAfter: 1);

    bytes += generator.text('Note: Goods once sold will not be taken back or exchanged.', styles: const PosStyles(align: PosAlign.center, bold: false), linesAfter: 1);

    bytes += generator.qrcode(
      AppConfig.companySite,
    );
    bytes += generator.text('');
    bytes += generator.text('Developed By: ${AppConfig.companyName}', styles: const PosStyles(align: PosAlign.center), linesAfter: 1);
    bytes += generator.cut();
    return bytes;
  }
}
