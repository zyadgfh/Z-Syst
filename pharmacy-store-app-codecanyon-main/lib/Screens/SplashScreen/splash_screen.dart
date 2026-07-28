// ignore_for_file: use_build_context_synchronously
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_pos/Screens/SplashScreen/on_board.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/model/business_info_model.dart';
import 'package:nb_utils/nb_utils.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:provider/provider.dart';
import '../../Repository/API/business_info_repo.dart';
import '../../app_config/app_config.dart';
import '../../currency.dart';
import '../../internet checker/Internet_check_provider/util/network_observer_provider.dart';
import '../Authentication/Repo/licnese_repo.dart';
import '../Home/home.dart';
import '../language/language_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' as rv;

class SplashScreen extends rv.ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  rv.ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends rv.ConsumerState<SplashScreen> {
  bool isUpdateAvailable = false;
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey();

  void showSnack(String text) {
    if (_scaffoldKey.currentContext != null) {
      ScaffoldMessenger.of(_scaffoldKey.currentContext!).showSnackBar(SnackBar(content: Text(text)));
    }
  }

  void getPermission() async {
    // ignore: unused_local_variable
    Map<Permission, PermissionStatus> statuses = await [
      Permission.bluetoothScan,
      Permission.bluetoothConnect,
    ].request();
  }

  // checkUser() async {
  //   // final customChecker = InternetConnectionChecker.createInstance(
  //   //   requireAllAddressesToRespond: true,
  //   // );
  //   await PurchaseModel().isActiveBuyer().then((value) {
  //     if (!value) {
  //       showDialog(
  //         context: context,
  //         builder: (context) => AlertDialog(
  //           title: const Text("Not Active User"),
  //           content: const Text("Please use the valid purchase code to use the app."),
  //           actions: [
  //             TextButton(
  //               onPressed: () {
  //                 //Exit app
  //                 if (Platform.isAndroid) {
  //                   SystemNavigator.pop();
  //                 } else {
  //                   exit(0);
  //                 }
  //               },
  //               child: const Text("OK"),
  //             ),
  //           ],
  //         ),
  //       );
  //     } else {
  //       nextPage();
  //     }
  //   });
  // }
  Future<void> checkUser() async {
    try {
      final connectivityResult = await Connectivity().checkConnectivity();
      final isConnected = connectivityResult.contains(ConnectivityResult.mobile) ||
          connectivityResult.contains(ConnectivityResult.wifi);
      if (!isConnected) {
        // _showNoInternetDialog();
        return;
      }
      final isActiveBuyer = await PurchaseModel().isActiveBuyer();
      if (!isActiveBuyer) {
        _showInvalidUserDialog();
        return;
      }

      nextPage();
    } catch (e) {
      _showErrorDialog("An error occurred. Please try again later.");
    }
  }
  void _showNoInternetDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("No Internet Connection"),
        content: const Text("Please check your internet connection and try again."),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
  void _showInvalidUserDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Invalid User"),
        content: const Text("Please use a valid purchase code to use the app."),
        actions: [
          TextButton(
            onPressed: () {
              // Exit the app
              if (Platform.isAndroid) {
                SystemNavigator.pop();
              } else {
                exit(0);
              }
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Error"),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  void initState() {
    super.initState();
    getPermission();
    getCurrency();
    // nextPage();
    checkUser();
    setLanguage();
  }

  getCurrency() async {
    final prefs = await SharedPreferences.getInstance();
    currency = prefs.getString('currency') ?? '\$';
    currencyName = prefs.getString('currencyName') ?? 'US Dollar';
  }

  void setLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    String selectedLanguage = prefs.getString('lang') ?? 'English';
    context.read<LanguageChangeProvider>().changeLocale(languageMap[selectedLanguage]!);
  }

  Future<void> nextPage() async {
    final prefs = await SharedPreferences.getInstance();
    await Future.delayed(const Duration(seconds: 1));
    if (prefs.getString('token') != null) {
      BusinessInformation? data;
      data = await BusinessRepository().checkBusinessData();
      if (data == null) {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const OnBoard()));
      } else {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const Home()));
      }
    } else {
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const OnBoard()));
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = lang.S.of(context);
    return ProviderNetworkObserver(
      child: Scaffold(
        backgroundColor: kMainColor,
        body: LayoutBuilder(
          builder: (BuildContext context, BoxConstraints constraints) {
            var windowSize = Size(constraints.maxWidth, constraints.maxHeight);
            return Stack(
              children: [
                Container(
                  height: windowSize.height,
                  width: windowSize.width,
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        Color(0xff14B8A6),
                        Color(0xff00987F),
                      ],
                      begin: Alignment.topLeft,
                    ),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      const Spacer(),
                      const SizedBox(height: 100),
                      // Logo Container
                      Container(
                        height: 232,
                        width: 232,
                        decoration: BoxDecoration(
                          image: DecorationImage(
                            image: AssetImage(AppConfig.logo),
                          ),
                        ),
                      ),

                      const Spacer(),

                      // Footer Text
                      Column(
                        children: [
                          Center(
                            child: Text(
                              '${lang.poweredBy} ${AppConfig.companyName}',
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.w600,
                                    color: kWhite,
                                  ),
                            ),
                          ),
                          Center(
                            child: Text(
                              '${lang.version} ${AppConfig.appVersion}',
                              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.w500,
                                    color: kWhite,
                                  ),
                            ),
                          ),
                          const SizedBox(height: 30),
                        ],
                      ),
                    ],
                  ),
                ),
                // Background Image
                Positioned.fill(
                  child: Image.asset(
                    'assets/logo/bg1.png',
                    fit: BoxFit.cover,
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}
