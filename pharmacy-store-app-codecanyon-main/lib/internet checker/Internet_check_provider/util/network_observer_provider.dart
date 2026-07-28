import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../constant.dart';
import '../controller/network_provider_controller.dart';

class ProviderNetworkObserver extends StatefulWidget {
  final Widget child;

  const ProviderNetworkObserver({super.key, required this.child});

  @override
  State<ProviderNetworkObserver> createState() => _ProviderNetworkObserverState();
}

class _ProviderNetworkObserverState extends State<ProviderNetworkObserver> {
  // Future<void> _retryConnection() async {
  //   var connectivityResult = await Connectivity().checkConnectivity();
  //   if (connectivityResult != ConnectivityResult.none) {
  //     // Navigator.of(context).pop();
  //   }
  // }

  void showSnackBar(String text, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(text),
        backgroundColor: color,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<NetworkProviderController>(
      builder: (context, networkObserver, child) {
        if (networkObserver.status == ConnectivityStatus.offline) {
          return Scaffold(
            backgroundColor: Colors.white,
            body: Center(
              child: Container(
                margin: const EdgeInsets.all(20.0),
                padding: const EdgeInsets.all(20.0),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.9),
                  borderRadius: BorderRadius.circular(24.0),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.shade50,
                      spreadRadius: 5,
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                    BoxShadow(
                      color: Colors.grey.shade100,
                      spreadRadius: 5,
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    AnimatedSwitcher(
                      duration: const Duration(milliseconds: 500),
                      transitionBuilder: (child, animation) => FadeTransition(
                        opacity: animation,
                        child: child,
                      ),
                      child: const Icon(
                        Icons.wifi_off,
                        key: ValueKey<int>(1),
                        size: 70.0,
                        color: Colors.red,
                      ),
                    ),
                    const SizedBox(height: 16.0),
                    Text(
                      'No Internet Connection',
                      style: const TextStyle(
                        fontSize: 22.0,
                        fontWeight: FontWeight.bold,
                        color: Colors.black,
                      ),
                    ),
                    const SizedBox(height: 12.0),
                    Text(
                      'Please check your internet connection and try again.',
                      style: const TextStyle(
                        fontSize: 16.0,
                        color: Colors.grey,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 24.0),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                          minimumSize: Size(double.maxFinite, 48),
                          backgroundColor: kMainColor,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(35),
                          )),
                      onPressed: () {},
                      child: Text(
                        'Retry',
                        style: TextStyle(color: Colors.white),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        } else {
          return widget.child;
        }
      },
    );
  }
}
