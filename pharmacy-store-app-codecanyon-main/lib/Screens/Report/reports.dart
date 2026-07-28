import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/Report/Screens/due_report_screen.dart';
import 'package:mobile_pos/Screens/Report/Screens/expense_report.dart';
import 'package:mobile_pos/Screens/Report/Screens/purchase_report.dart';
import 'package:mobile_pos/Screens/Report/Screens/sales_report_screen.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import '../stock_list/stock_list.dart';
import '../widget/acnoo_scafold.dart';
import 'Screens/income_report.dart';
import 'Screens/loss_profit_report.dart';
import 'Screens/purchase_return_report.dart';
import 'Screens/sales_return_report.dart';

class Reports extends StatefulWidget {
  const Reports({super.key});

  @override
  // ignore: library_private_types_in_public_api
  _ReportsState createState() => _ReportsState();
}

class _ReportsState extends State<Reports> {
  @override
  Widget build(BuildContext context) {
    return AcnooScafoldWidget(
      // backgroundColor: kWhite,
      appBar: AppBar(
        title: Text(
          lang.S.of(context).reports,
          style: GoogleFonts.poppins(color: Colors.white, fontSize: 20.0, fontWeight: FontWeight.w600),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0.0,
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: SingleChildScrollView(
          child: Column(
            children: [
              ///-------------------Sales report--------------------------------
              ReportCard(
                  pressed: () {
                    const SalesReportScreen().launch(context);
                  },
                  iconPath: 'assets/saleReport.svg',
                  title: lang.S.of(context).salesReport),

              ///--------------------purchase report----------------------
              ReportCard(
                  pressed: () {
                    const PurchaseReportScreen().launch(context);
                  },
                  iconPath: 'assets/purchaseReport.svg',
                  title: lang.S.of(context).purchaseReport),

              ///------------------------Due report----------------------------------
              ReportCard(
                  pressed: () {
                    const DueReportScreen().launch(context);
                  },
                  iconPath: 'assets/due_report.svg',
                  title: lang.S.of(context).dueReport),

              ///_______________Stock_report________________________________________________________________
              ReportCard(
                  pressed: () {
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => const StockList(
                                  isFromReport: true,
                                )));
                  },
                  iconPath: 'assets/stockReport.svg',
                  //title: 'Stock Report'
                  title: lang.S.of(context).stockReport),

              ///_______________Loss/Profit________________________________________________________________
              ReportCard(
                  pressed: () {
                    Navigator.push(context, MaterialPageRoute(builder: (context) => const LossProfitReportScreen()));
                  },
                  iconPath: 'assets/lpReport.svg',
                  //title: 'Loss/Profit Report'
                  title: lang.S.of(context).lossProfitReport),

              ///_______________Income_report________________________________________________________________
              ReportCard(
                  pressed: () {
                    Navigator.push(context, MaterialPageRoute(builder: (context) => const IncomeReport()));
                  },
                  iconPath: 'assets/incomeReport.svg',
                  title: lang.S.of(context).incomeReport),

              ///__________________Expense Report____________________________________________________________
              ReportCard(
                  pressed: () {
                    Navigator.push(context, MaterialPageRoute(builder: (context) => const ExpenseReport()));
                  },
                  iconPath: 'assets/expenseReport.svg',
                  //title: 'Expense Report'
                  title: lang.S.of(context).expenseReport),
              // Sale return report
              ReportCard(
                pressed: () {
                  const SalesReturnReport().launch(context);
                },
                iconPath: 'assets/saleReturnReport.svg',
                title: lang.S.of(context).saleReturnedReport,
              ),

              // Purchase return report
              ReportCard(
                pressed: () {
                  const PurchaseReturnReport().launch(context);
                },
                iconPath: 'assets/purchaseReturnReport.svg',
                title: lang.S.of(context).purchaseReturnReport,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ignore: must_be_immutable
class ReportCard extends StatelessWidget {
  ReportCard({
    super.key,
    required this.pressed,
    required this.iconPath,
    required this.title,
  });

  // ignore: prefer_typing_uninitialized_variables
  var pressed;
  String iconPath, title;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        ListTile(
          horizontalTitleGap: 14,
          contentPadding: EdgeInsets.zero,
          visualDensity: VisualDensity(vertical: -4),
          onTap: pressed,
          leading: SvgPicture.asset(
            iconPath,
            height: 35,
            width: 35,
          ),
          title: Text(
            title,
            style: GoogleFonts.poppins(
              color: Colors.black,
            ),
          ),
          trailing: Icon(
            Icons.arrow_forward_ios,
            color: kGreyTextColor,
            size: 18,
          ),
        ),
        Row(
          children: [
            Flexible(flex: 2, fit: FlexFit.tight, child: SizedBox()),
            Flexible(
              fit: FlexFit.tight,
              flex: 11,
              child: Padding(
                padding: EdgeInsets.symmetric(vertical: 10.5),
                child: Divider(
                  color: kLineColor,
                  thickness: 1,
                  height: 1,
                ),
              ),
            ),
          ],
        )
      ],
    );
  }
}
