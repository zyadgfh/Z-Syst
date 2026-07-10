import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Currency/Provider/currency_provider.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../constant.dart';
import '../../currency.dart';
import 'Model/currency_model.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

class CurrencyScreen extends StatefulWidget {
  const CurrencyScreen({super.key});

  @override
  State<CurrencyScreen> createState() => _CurrencyScreenState();
}

class _CurrencyScreenState extends State<CurrencyScreen> {
  CurrencyModel selectedCurrency = CurrencyModel(name: currencyName, symbol: currency);

  @override
  Widget build(BuildContext context) {
    print(selectedCurrency.name);
    print(selectedCurrency.symbol);
    final theme = Theme.of(context);
    return Consumer(builder: (context, ref, __) {
      final currencyData = ref.watch(currencyProvider);
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: Text(
            lang.S.of(context).currency,
            //'Currency',
            style: theme.textTheme.titleMedium?.copyWith(color: Colors.white),
          ),
          centerTitle: true,
          iconTheme: const IconThemeData(color: Colors.white),
          elevation: 0.0,
        ),
        body: currencyData.when(
          data: (data) {
            return SingleChildScrollView(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
                child: ListView.builder(
                  padding: EdgeInsets.zero,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: data.length,
                  shrinkWrap: true,
                  itemBuilder: (BuildContext context, int index) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 15),
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(6),
                          color: selectedCurrency.name == data[index].name ? kMainColor : kWhite,
                          boxShadow: [
                            BoxShadow(color: const Color(0xff0C1A4B).withValues(alpha: 0.24), blurRadius: 1),
                            BoxShadow(color: const Color(0xff473232).withValues(alpha: 0.05), offset: const Offset(0, 3), spreadRadius: -1, blurRadius: 8)
                          ],
                        ),
                        child: ListTile(
                          selected: selectedCurrency.name == data[index].name,
                          selectedColor: Colors.white,
                          // selectedTileColor: kMainColor.withOpacity(.7),
                          onTap: () {
                            setState(() {
                              selectedCurrency = data[index];
                            });
                          },
                          title: Text('${data[index].name} - ${data[index].symbol}'),
                          trailing: Icon(
                            Icons.keyboard_arrow_right_rounded,
                            color: selectedCurrency.name == data[index].name ? Colors.white : kGreyTextColor,
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            );
          },
          error: (error, stackTrace) {
            return Container();
          },
          loading: () => const Center(child: CircularProgressIndicator()),
        ),
        bottomNavigationBar: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: NewPrimaryButton(
            buttonText: lang.S.of(context).save,
            onPressed: () async {
              final prefs = await SharedPreferences.getInstance();
              await prefs.setString('currency', selectedCurrency.symbol ?? '\$');
              await prefs.setString('currencyName', selectedCurrency.name ?? 'US Dollar');
              setState(
                () {
                  currency = selectedCurrency.symbol ?? '\$';
                  currencyName = selectedCurrency.name ?? 'US Dollar';
                  Navigator.pop(context);
                },
              );
            },
          ),
        ),
      );
    });
  }
}
