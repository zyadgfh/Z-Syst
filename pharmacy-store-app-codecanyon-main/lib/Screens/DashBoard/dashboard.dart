import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/DashBoard/provider/dashboard_provider.dart';
import 'package:mobile_pos/Screens/DashBoard/purchase_chart.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/currency.dart';
import 'components.dart';
import 'widgets/dashboard_shimar_wedget.dart';
import 'loss_profit_chart.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  List<String> timeList = [
    'Weekly',
    'Monthly',
    'Yearly',
  ];

  String selectedTimeList = 'Weekly';
  DropdownButton<String> getTime(WidgetRef ref) {
    List<DropdownMenuItem<String>> itemList = [];
    for (var des in timeList) {
      var item = DropdownMenuItem(
          value: des,
          child: Text(
            des,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kNutral700, fontWeight: FontWeight.w500),
          ));
      itemList.add(item);
    }
    return DropdownButton(
        icon: const Icon(
          Icons.keyboard_arrow_down,
          color: kGreyTextColor,
          size: 18,
        ),
        value: selectedTimeList,
        items: itemList,
        onChanged: (value) {
          setState(() {
            selectedTimeList = value!;
            ref.refresh(dashboardInfoProvider(selectedTimeList.toLowerCase()));
          });
        });
  }

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    return Consumer(builder: (_, ref, watch) {
      final theme = Theme.of(context);
      final dashboardInfo = ref.watch(dashboardInfoProvider(selectedTimeList.toLowerCase()));
      return dashboardInfo.when(data: (snapShot) {
        return AcnooScafoldWidget(
          appBar: AppBar(
            iconTheme: IconThemeData(color: Colors.white),
            centerTitle: true,
            backgroundColor: Colors.transparent,
            title: Text(
              l.S.of(context).dashboard,
              style: theme.textTheme.titleLarge?.copyWith(color: kWhite, fontSize: 20),
              //'Dashboard'
            ),
            actions: [
              IconButton(
                onPressed: () => ref.refresh(dashboardInfoProvider(selectedTimeList.toLowerCase())),
                icon: Icon(
                  Icons.refresh,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          body: RefreshIndicator.adaptive(
            backgroundColor: Colors.white,
            onRefresh: () async => await Future.sync(
              () => ref.refresh(
                dashboardInfoProvider(
                  selectedTimeList.toLowerCase(),
                ),
              ),
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 16),
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // title
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          lang.overView,
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                        ),
                        Container(
                          height: 32,
                          padding: const EdgeInsets.symmetric(horizontal: 5),
                          // width: 100,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(5),
                            border: Border.all(
                              color: kOutlineColor,
                            ),
                          ),
                          child: DropdownButtonHideUnderline(child: getTime(ref)),
                        )
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Total customer and supplier
                    Row(
                      children: [
                        Expanded(
                          child: DashBordContainerWidget(
                            title: snapShot.data?.totalCustomers.toString() ?? '0',
                            gradient: LinearGradient(
                              colors: [
                                Color(0xffCDFFD6),
                                Color(0xffE8FFEC),
                              ],
                            ),
                            //'Customer Due',
                            subtitle: lang.totalCustomer,
                          ),
                        ),
                        SizedBox(width: 12),
                        Expanded(
                          child: DashBordContainerWidget(
                              gradient: LinearGradient(
                                colors: [
                                  Color(0xffCEEEFF),
                                  Color(0xffEFF9FF),
                                ],
                              ),
                              title: snapShot.data?.totalSuppliers.toString() ?? '0',
                              subtitle: lang.totalSupplier),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Total customer and supplier
                    Row(
                      children: [
                        Expanded(
                            child: DashBordContainerWidget(
                          title: snapShot.data?.totalMedicine.toString() ?? '0',
                          gradient: LinearGradient(
                            colors: [
                              Color(0xffFFE7CB),
                              Color(0xffFFF6EC),
                            ],
                          ),
                          subtitle: lang.stockManager,
                        )),
                        SizedBox(width: 12),
                        Expanded(
                            child: DashBordContainerWidget(
                                gradient: LinearGradient(colors: [Color(0xffFFDFE6), Color(0xffFFF1F4)]),
                                title: snapShot.data?.expiredMedicine.toString() ?? '0',
                                subtitle: lang.expireMedicine))
                      ],
                    ),
                    const SizedBox(height: 24),
                    Text(
                      lang.purchaseAndSale,
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        RichText(
                          text: TextSpan(
                            children: [
                              WidgetSpan(
                                  alignment: PlaceholderAlignment.middle,
                                  child: Container(
                                    height: 4.61,
                                    width: 8.3,
                                    decoration: BoxDecoration(
                                      color: kSubTitleColor,
                                      borderRadius: BorderRadius.circular(1),
                                    ),
                                  )),
                              TextSpan(text: ' ${lang.purchase}: ', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                              TextSpan(
                                text: "$currency ${snapShot.data?.totalPurchase?.toStringAsFixed(2)}" ?? '${currency}0.0',
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 16),
                        RichText(
                          text: TextSpan(
                            children: [
                              WidgetSpan(
                                  alignment: PlaceholderAlignment.middle,
                                  child: Container(
                                    height: 4.61,
                                    width: 8.3,
                                    decoration: BoxDecoration(
                                      color: kMainColor,
                                      borderRadius: BorderRadius.circular(1),
                                    ),
                                  )),
                              TextSpan(text: ' ${lang.sale}: ', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                              TextSpan(
                                text: "$currency ${snapShot.data?.totalSales?.toStringAsFixed(2)}" ?? '0.0',
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),

                    // Loss profit Chart
                    SizedBox(height: 250, width: double.infinity, child: PurchaseSaleChart(model: snapShot)),
                    const SizedBox(height: 20),
                    Text(
                      lang.lossOrProfitOverView,
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        RichText(
                          text: TextSpan(
                            children: [
                              WidgetSpan(
                                  alignment: PlaceholderAlignment.middle,
                                  child: Container(
                                    height: 4.61,
                                    width: 8.3,
                                    decoration: BoxDecoration(
                                      color: kSubTitleColor,
                                      borderRadius: BorderRadius.circular(1),
                                    ),
                                  )),
                              TextSpan(text: ' ${lang.lossTitle}: ', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                              TextSpan(
                                text: "$currency ${snapShot.data?.totalLoss?.abs() ?? 0.0}",
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w600,
                                ),
                              )
                            ],
                          ),
                        ),
                        const SizedBox(
                          width: 20,
                        ),
                        RichText(
                          text: TextSpan(
                            children: [
                              WidgetSpan(
                                  alignment: PlaceholderAlignment.middle,
                                  child: Container(
                                    height: 4.61,
                                    width: 8.3,
                                    decoration: BoxDecoration(
                                      color: kMainColor,
                                      borderRadius: BorderRadius.circular(1),
                                    ),
                                  )),
                              TextSpan(text: ' ${lang.profit}: ', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                              TextSpan(
                                text: "$currency ${snapShot.data?.totalProfit?.toStringAsFixed(2) ?? 0.0}",
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    SizedBox(
                      height: 10,
                    ),
                    //Loss Profit Chart
                    SizedBox(
                      height: 250,
                      width: double.infinity,
                      child: LossProfitChart(
                        model: snapShot,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      }, error: (e, stack) {
        return Scaffold(
          body: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  //'{No data found} $e',
                  '${l.S.of(context).noDataFound} $e',
                  style: const TextStyle(color: kGreyTextColor, fontSize: 16, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
        );
      }, loading: () {
        return DashBoardShimmer(theme: theme);
      });
    });
  }
}
