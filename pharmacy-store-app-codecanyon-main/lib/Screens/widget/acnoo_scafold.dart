import 'package:flutter/material.dart';
import '../../constant.dart';
import '../../internet checker/Internet_check_provider/util/network_observer_provider.dart';

class AcnooScafoldWidget extends StatelessWidget {
  const AcnooScafoldWidget({super.key, this.appBar, required this.body, this.bottomNavigationBar, this.flotingActionButton});
  final Widget? appBar;
  final Widget body;
  final Widget? bottomNavigationBar;
  final Widget? flotingActionButton;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final heightOffset = appBar == null ? 80.0 : 8.0;
    return ProviderNetworkObserver(
      child: Scaffold(
        // resizeToAvoidBottomInset: false,
        backgroundColor: theme.colorScheme.surface,
        body: Stack(
          alignment: Alignment.bottomRight,
          children: [
            DecoratedBox(
              decoration: const BoxDecoration(gradient: kGradiant),
              child: Column(
                children: [
                  if (appBar != null) appBar!,
                  SizedBox(height: heightOffset),
                  Expanded(
                      child: Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: theme.colorScheme.primaryContainer,
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(35)),
                    ),
                    child: body,
                  ))
                ],
              ),
            ),
            if (flotingActionButton != null) flotingActionButton!
          ],
        ),
        bottomNavigationBar: bottomNavigationBar,
      ),
    );
  }
}
