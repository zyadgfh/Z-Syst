import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Authentication/forgot_password.dart';
import 'package:mobile_pos/Screens/Authentication/login_form.dart';
import 'package:mobile_pos/Screens/Authentication/register_screen.dart';
import 'package:mobile_pos/Screens/Authentication/sign_in.dart';
import 'package:mobile_pos/Screens/Customers/parties_list.dart';
import 'package:mobile_pos/Screens/Expense/expense_list.dart';
import 'package:mobile_pos/Screens/Home/home.dart';
import 'package:mobile_pos/Screens/Products/add%20product/add_product.dart';
import 'package:mobile_pos/Screens/Products/product_list_screen.dart';
import 'package:mobile_pos/Screens/Report/reports.dart';
import 'package:mobile_pos/Screens/SplashScreen/on_board.dart';
import 'package:mobile_pos/Screens/SplashScreen/splash_screen.dart';
import 'package:mobile_pos/Screens/stock_list/stock_list.dart';
import 'package:mobile_pos/Theme/theme.dart';
import 'package:provider/provider.dart' as pro;
import 'Screens/Income/income_list.dart';
import 'Screens/Loss_Profit/loss_profit_screen.dart';
import 'Screens/Purchase List/purchase_list_screen.dart';
import 'Screens/Purchase/add_purchase.dart';
import 'Screens/Sales List/sales_list_screen.dart';
import 'Screens/Sales/add_sales.dart';
import 'Screens/due_list/due_list_screen.dart';
import 'Screens/language/language_provider.dart';
import 'Screens/ledger/ledger_list.dart';
import 'Screens/tax rates/tax_rates_screen.dart';
import 'generated/l10n.dart';
import 'internet checker/Internet_check_provider/controller/network_provider_controller.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    const ProviderScope(
      child: MyApp(),
    ),
  );
}

class MyApp extends ConsumerStatefulWidget {
  const MyApp({super.key});

  @override
  ConsumerState<MyApp> createState() => _MyAppState();
}

class _MyAppState extends ConsumerState<MyApp> {
  final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  // @override
  // void initState() {
  //   // TODO: implement initState
  //   super.initState();
  //   if (context.mounted) {
  //     WidgetsBinding.instance.addPostFrameCallback((_) {
  //       ref.read(globalOverlayService(navigatorKey.currentContext!));
  //     });
  //   }
  // }

  @override
  Widget build(BuildContext context) {
    return pro.MultiProvider(
      providers: [
        pro.ChangeNotifierProvider<NetworkProviderController>(
          create: (_) => NetworkProviderController(),
        ),
      ],
      child: pro.ChangeNotifierProvider<LanguageChangeProvider>(
        create: (context) => LanguageChangeProvider(),
        child: Builder(
            builder: (context) => MaterialApp(
                  navigatorKey: navigatorKey,
                  debugShowCheckedModeBanner: false,
                  locale: pro.Provider.of<LanguageChangeProvider>(context, listen: true).currentLocale,
                  localizationsDelegates: const [
                    S.delegate,
                    GlobalMaterialLocalizations.delegate,
                    GlobalWidgetsLocalizations.delegate,
                    GlobalCupertinoLocalizations.delegate,
                    AppLocalizationDelegate(),
                  ],
                  supportedLocales: S.delegate.supportedLocales,
                  title: 'Pharmacy',
                  theme: AcnooTheme.kLightTheme(context),
                  initialRoute: '/',
                  builder: EasyLoading.init(),
                  routes: {
                    '/': (context) => const SplashScreen(),
                    '/onBoard': (context) => const OnBoard(),
                    '/signIn': (context) => const SignInScreen(),
                    '/loginForm': (context) => const LoginForm(isEmailLogin: true),
                    '/signup': (context) => const RegisterScreen(),
                    '/forgotPassword': (context) => const ForgotPassword(),
                    '/home': (context) => const Home(),
                    '/AddProducts': (context) => AddProduct(isFromHome: false),
                    '/Products': (context) => const ProductList(isFromExpired: false),
                    '/Sales': (context) => const AddSalesScreen(),
                    '/Parties': (context) => const PartyList(),
                    '/Expense': (context) => const ExpenseList(),
                    '/Income': (context) => const IncomeList(),
                    '/Stock': (context) => const StockList(isFromReport: false),
                    '/Purchase': (context) => const AddPurchaseScreen(),
                    '/Reports': (context) => const Reports(),
                    '/Due List': (context) => const DueListScreen(),
                    // '/PaymentOptions': (context) => const PaymentOptions(),
                    '/Sales List': (context) => const SalesListScreen(),
                    '/Purchase List': (context) => const PurchaseListScreen(),
                    '/Loss/Profit': (context) => const LossProfitScreen(),
                    '/Expiring': (context) => const ProductList(isFromExpired: true),
                    '/taxReport': (context) => const TaxReport(),
                    '/ledger_details': (context) => const LedgerList(),
                  },
                )),
      ),
    );
  }
}
