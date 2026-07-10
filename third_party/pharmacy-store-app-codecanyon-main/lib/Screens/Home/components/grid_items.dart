import 'package:flutter/material.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

class GridItems {
  final String title, icon, route;

  GridItems({required this.title, required this.icon, required this.route});
}

List<GridItems> getFreeIcons({required BuildContext context}) {
  List<GridItems> freeIcons = [
    //1st row
    GridItems(title: lang.S.of(context).parties, icon: 'assets/parties.svg', route: 'Parties'),
    GridItems(title: lang.S.of(context).sale, icon: 'assets/sale.svg', route: 'Sales'),
    GridItems(title: lang.S.of(context).purchase, icon: 'assets/purchase.svg', route: 'Purchase'),
    GridItems(title: lang.S.of(context).product, icon: 'assets/product.svg', route: 'Products'),
    //2nd row
    GridItems(title: lang.S.of(context).dueList, icon: 'assets/dueList.svg', route: 'Due List'),
    GridItems(title: lang.S.of(context).saleList, icon: 'assets/salesList.svg', route: 'Sales List'),
    GridItems(
      title: lang.S.of(context).purchaseList,
      icon: 'assets/purchaseList.svg',
      route: 'Purchase List',
    ),
    GridItems(title: lang.S.of(context).stockList, icon: 'assets/stock.svg', route: 'Stock'),
    //3rd row
    GridItems(
      title: lang.S.of(context).ledger,
      icon: 'assets/ledger.svg',
      route: 'ledger_details',
    ),
    GridItems(
      title: lang.S.of(context).lossOrProfit,
      icon: 'assets/lossProfit.svg',
      route: 'Loss/Profit',
    ),
    GridItems(
      title: lang.S.of(context).expiring,
      icon: 'assets/expiring.svg',
      route: 'Expiring',
    ),
    GridItems(title: lang.S.of(context).reports, icon: 'assets/reports.svg', route: 'Reports'),
    //4th row
    GridItems(
      title: lang.S.of(context).income,
      icon: 'assets/income.svg',
      route: 'Income',
    ),
    GridItems(
      title: lang.S.of(context).expense,
      icon: 'assets/expense.svg',
      route: 'Expense',
    ),
    GridItems(
      title: lang.S.of(context).tax,
      icon: 'assets/tax.svg',
      route: 'taxReport',
    ),
  ];
  return freeIcons;
}
