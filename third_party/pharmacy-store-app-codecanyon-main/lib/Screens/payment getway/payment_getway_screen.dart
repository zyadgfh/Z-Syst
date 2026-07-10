import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:webview_flutter_android/webview_flutter_android.dart';

import '../../app_config/api_config.dart';

class PaymentScreen extends StatefulWidget {
  const PaymentScreen({super.key, required this.planId, required this.businessId});

  final String planId;
  final String businessId;

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

String paymentUrl = '${APIConfig.domain}payments-gateways/plan_id/business_id';
const String successUrl = 'order-status?status=success';
const String failureUrl = 'order-status?status=failed';

class _PaymentScreenState extends State<PaymentScreen> {
  late WebViewController controller;
  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    paymentUrl = paymentUrl.replaceAll('plan_id', widget.planId).replaceAll('business_id', widget.businessId);

    controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0x00000000))
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            print('Hasina:'+url);
            if (url.contains(successUrl)) {
              print('This is susses');
              Navigator.pop(context,true);
              // Navigator.pushReplacement(
              //     context,
              //     MaterialPageRoute(
              //       builder: (context) => const SuccessScreen(),
              //     ));
              return;
            }
            if (url.contains(failureUrl)) {
              print('This is Field');
              Navigator.pop(context,false);
              // Navigator.pushReplacement(
              //     context,
              //     MaterialPageRoute(
              //       builder: (context) => const FailureScreen(),
              //     ));
              return;
            }
          },
        ),
      )
      ..loadRequest(Uri.parse(paymentUrl));
    // For image picker
    if (Platform.isAndroid) {
      final androidController = controller.platform as AndroidWebViewController;
      androidController.setOnShowFileSelector(_androidImagePicker);
    }
  }

  // image picker
  Future<List<String>> _androidImagePicker(FileSelectorParams params) async {
    final XFile? pickedFile = await _imagePicker.pickImage(
      source: ImageSource.gallery,
    );
    if (pickedFile != null) {
      String filePath = pickedFile.path;
      final fileUri = Uri.file(filePath);
      return [fileUri.toString()];
    }
    return [];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title:  Text(
          lang.S.of(context).paymentGateway,
           // 'Payment Gateway'
        ),
      ),
      body: WebViewWidget(
        controller: controller,

        // initialUrl: paymentUrl,
        // javascriptMode: JavascriptMode.unrestricted,
        // onWebViewCreated: (WebViewController webViewController) {
        //   _controller = webViewController;
        // },
        // navigationDelegate: (NavigationRequest request) {
        //   if (request.url == successUrl) {
        //     // Handle success
        //     Navigator.pushReplacement(
        //       context,
        //       MaterialPageRoute(builder: (context) => SuccessScreen()),
        //     );
        //     return NavigationDecision.prevent;
        //   } else if (request.url == failureUrl) {
        //     // Handle failure
        //     Navigator.pushReplacement(
        //       context,
        //       MaterialPageRoute(builder: (context) => FailureScreen()),
        //     );
        //     return NavigationDecision.prevent;
        //   }
        //   return NavigationDecision.navigate;
        // },
      ),
    );
  }
}

class SuccessScreen extends StatelessWidget {
  const SuccessScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title:  Text(
          lang.S.of(context).paymentSuccess,
           // 'Payment Success'
        ),
      ),
      body:  Center(
        child: Text(
            lang.S.of(context).paymentWasSuccessful,
           // 'Payment was successful!'
        ),
      ),
    );
  }
}

class FailureScreen extends StatelessWidget {
  const FailureScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title:  Text(
          lang.S.of(context).paymentFailed,
           // 'Payment Failed'
        ),
      ),
      body:  Center(
        child: Text(
          lang.S.of(context).paymentFailedPleaseTryAgain,
            //'Payment failed. Please try again.'
        ),
      ),
    );
  }
}
