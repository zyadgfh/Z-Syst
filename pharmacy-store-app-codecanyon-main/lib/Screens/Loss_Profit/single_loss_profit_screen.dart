import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../currency.dart';
import '../Sales List/provider/sales_details_provider.dart';

class SingleLossProfitScreen extends StatefulWidget {
  const SingleLossProfitScreen({super.key, required this.id});

  final num id;

  @override
  State<SingleLossProfitScreen> createState() => _SingleLossProfitScreenState();
}

class _SingleLossProfitScreenState extends State<SingleLossProfitScreen> {
  @override
  Widget build(BuildContext context) {
    return Consumer(
      builder: (BuildContext context, WidgetRef ref, Widget? child) {
        final theme = Theme.of(context);
        final lang = l.S.of(context);
        final data = ref.watch(salesDetailsProvider(widget.id));

        return data.when(
          data: (snapshot) {
            num totalProfit = 0;
            num totalLoss = 0;
            if (snapshot?.data?.details != null) {
              for (var item in snapshot!.data!.details!) {
                num purchasePriceStr = item.purchasePrice ?? 0;
                num quantityStr = item.quantities ?? 0;
                num salePriceStr = item.price ?? 0;

                num purchasePrice = purchasePriceStr * quantityStr;
                num salePrice = salePriceStr * quantityStr;
                num profit = salePrice - purchasePrice;
                num loss = 0;
                if (purchasePrice > salePrice) {
                  loss = purchasePrice - salePrice;
                  profit = 0;
                }
                totalProfit += profit;
                totalLoss += loss;
              }
            }

            return AcnooScafoldWidget(
              appBar: AppBar(
                backgroundColor: Colors.transparent,
                title: Text(
                  snapshot?.data?.party?.name ?? 'Guest',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 20,
                  ),
                ),
                iconTheme: const IconThemeData(color: Colors.white),
                centerTitle: false,
                elevation: 0.0,
                titleSpacing: 0,
              ),
              body: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                lang.billTO,
                                style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500),
                              ),
                              Text(
                                '${l.S.of(context).invoice} #${snapshot?.data?.invoiceNumber}',
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Flexible(
                                child: Text(
                                  snapshot?.data?.party?.name ?? (snapshot?.data?.party?.phone ?? lang.guest),
                                  overflow: TextOverflow.ellipsis,
                                  maxLines: 1,
                                  style: theme.textTheme.bodyMedium?.copyWith(
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                              SizedBox(
                                width: 10,
                              ),
                              Text(
                                  "${l.S.of(context).dates} ${DateFormat.yMMMd().format(
                                    DateTime.parse(
                                      snapshot?.data?.saleDate ?? '',
                                    ),
                                  )}",
                                  style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500)),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                "${l.S.of(context).mobile} ${snapshot?.data?.party?.phone ?? ''}",
                                style: const TextStyle(color: Colors.grey),
                              ),
                              Text(
                                DateFormat.jm().format(DateTime.parse(snapshot?.data?.saleDate ?? '')),
                                style: const TextStyle(color: Colors.grey),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
                      decoration: BoxDecoration(
                        color: Color(0xffE7F7EF),
                        borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(30),
                          topRight: Radius.circular(30),
                        ),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            flex: 2,
                            child: Text(
                              l.S.of(context).product,
                              style: const TextStyle(fontWeight: FontWeight.bold),
                              textAlign: TextAlign.start,
                            ),
                          ),
                          Expanded(
                            flex: 2,
                            child: Text(
                              l.S.of(context).quantity,
                              style: const TextStyle(fontWeight: FontWeight.bold),
                              textAlign: TextAlign.center,
                            ),
                          ),
                          Expanded(
                            flex: 2,
                            child: Text(
                              l.S.of(context).profit,
                              style: const TextStyle(fontWeight: FontWeight.bold),
                              textAlign: TextAlign.center,
                            ),
                          ),
                          Expanded(
                            flex: 2,
                            child: Text(
                              l.S.of(context).lossTitle,
                              style: const TextStyle(fontWeight: FontWeight.bold),
                              textAlign: TextAlign.end,
                            ),
                          ),
                        ],
                      ),
                    ),
                    ListView.builder(
                      padding: EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                      itemCount: snapshot?.data?.details?.length ?? 0,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemBuilder: (context, index) {
                        var item = snapshot?.data?.details?[index];
                        num purchasePriceStr = item?.purchasePrice ?? 0;
                        num quantityStr = item?.quantities ?? 0;
                        num salePriceStr = item?.price ?? 0;

                        num purchasePrice = purchasePriceStr * quantityStr;
                        num salePrice = salePriceStr * quantityStr;
                        num profit = salePrice - purchasePrice;
                        num loss = 0;

                        if (purchasePrice > salePrice) {
                          loss = purchasePrice - salePrice;
                          profit = 0;
                        }
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 10.0),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                flex: 2,
                                child: Text(
                                  item?.product?.productName ?? 'N/A',
                                  textAlign: TextAlign.start,
                                  maxLines: 2,
                                  style: theme.textTheme.bodyMedium,
                                ),
                              ),
                              Expanded(
                                flex: 2,
                                child: Text(
                                  item?.quantities.toString() ?? '',
                                  style: theme.textTheme.bodyMedium,
                                  textAlign: TextAlign.center,
                                ),
                              ),
                              Expanded(
                                flex: 2,
                                child: Text(
                                  '$currency${profit.toStringAsFixed(2)}',
                                  style: theme.textTheme.bodyMedium,
                                  textAlign: TextAlign.center,
                                ),
                              ),
                              Expanded(
                                flex: 2,
                                child: Text(
                                  '$currency${loss.toStringAsFixed(2)}',
                                  style: theme.textTheme.bodyMedium,
                                  textAlign: TextAlign.end,
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
              bottomNavigationBar: Container(
                color: Colors.white,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      decoration: BoxDecoration(color: Color(0xffE7F7EF)),
                      padding: const EdgeInsets.all(10),
                      child: Padding(
                        padding: const EdgeInsets.only(left: 15, right: 15),
                        child: Row(
                          children: [
                            Expanded(
                              flex: 2,
                              child: Text(
                                l.S.of(context).total,
                                textAlign: TextAlign.start,
                                style: GoogleFonts.poppins(color: Colors.black, fontSize: 14.0, fontWeight: FontWeight.w500),
                              ),
                            ),
                            // Quantity
                            Expanded(
                              flex: 2,
                              child: Text(
                                "${snapshot?.data?.details?.fold<num>(0, (previousValue, element) => previousValue + (element.quantities ?? 0))}",
                                style: GoogleFonts.poppins(color: Colors.black),
                                textAlign: TextAlign.center,
                              ),
                            ),
                            // Total profit
                            Expanded(
                              flex: 2,
                              child: Text(
                                "$currency${totalProfit.toStringAsFixed(2)}", // totalProfit is now displayed correctly
                                style: GoogleFonts.poppins(color: Colors.black),
                                textAlign: TextAlign.center,
                              ),
                            ),

                            // total loss
                            Expanded(
                              flex: 2,
                              child: Text(
                                "$currency${totalLoss.toStringAsFixed(2)}", // totalLoss is now displayed correctly
                                overflow: TextOverflow.ellipsis,
                                style: GoogleFonts.poppins(color: Colors.black),
                                textAlign: TextAlign.end,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    // Discount
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: Color(0xffE7F7EF)),
                      child: Padding(
                        padding: const EdgeInsets.only(left: 15, right: 15),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              l.S.of(context).discount,
                              textAlign: TextAlign.start,
                              style: GoogleFonts.poppins(color: Colors.black, fontSize: 14.0, fontWeight: FontWeight.w500),
                            ),
                            Text(
                              "$currency${snapshot?.data?.discountAmount ?? 0}",
                              overflow: TextOverflow.ellipsis,
                              style: GoogleFonts.poppins(color: Colors.black),
                            ),
                          ],
                        ),
                      ),
                    ),
                    // Total Profit or Loss
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: Color(0xffE7F7EF), border: Border(top: BorderSide(color: Colors.white, width: 2))),
                      child: Padding(
                        padding: const EdgeInsets.only(left: 15, right: 15),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              snapshot?.data?.lossProfit?.isNegative ?? false ? l.S.of(context).totalLoss : l.S.of(context).totalProfit,
                              textAlign: TextAlign.start,
                              style: GoogleFonts.poppins(color: Colors.black, fontSize: 14.0, fontWeight: FontWeight.w500),
                            ),
                            Text(
                              snapshot?.data?.lossProfit?.isNegative ?? false
                                  ? "$currency${snapshot?.data?.lossProfit!.abs().toStringAsFixed(2)}"
                                  : "$currency${snapshot?.data?.lossProfit!.toStringAsFixed(2)}",
                              overflow: TextOverflow.ellipsis,
                              style: GoogleFonts.poppins(color: Colors.black),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, stack) => Center(child: Text('Error: $error')),
        );
      },
    );
  }
}
