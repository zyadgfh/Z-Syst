import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/svg.dart';
import 'package:mobile_pos/Provider/profile_provider.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';

import '../../constant.dart';
import '../Currency/Model/currency_model.dart';
import '../Currency/Provider/currency_provider.dart';
import '../Home/home.dart';
import '../payment getway/payment_getway_screen.dart';
import 'Model/subscription_plan_model.dart';
import 'Provider/subacription_plan_provider.dart';

class PurchasePremiumPlanScreen extends StatefulWidget {
  const PurchasePremiumPlanScreen({super.key, required this.isCameBack});

  final bool isCameBack;

  @override
  State<PurchasePremiumPlanScreen> createState() => _PurchasePremiumPlanScreenState();
}

class _PurchasePremiumPlanScreenState extends State<PurchasePremiumPlanScreen> {
  SubscriptionPlanModel? selectedPlan;

  List<String> imageList = [
    'images/sp1.png',
    'images/sp2.png',
    'images/sp3.png',
    'images/sp4.png',
    'images/sp5.png',
    'images/sp6.png',
  ];

  List<String> planDetailsImages = [
    'images/plan_details_1.png',
    'images/plan_details_2.png',
    'images/plan_details_3.png',
    'images/plan_details_4.png',
    'images/plan_details_5.png',
    'images/plan_details_6.png',
  ];

  @override
  void initState() {
    // TODO: implement initState
    super.initState();
  }

  String discount = '';
  CurrencyModel? getDefaultCurrency({required List<CurrencyModel> currencies}) {
    for (var element in currencies) {
      if (element.isDefault ?? false) {
        return element;
      }
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    List<String> planDetailsText = [
      lang.S.of(context).freeLifetimeUpdate,
      lang.S.of(context).android,
      lang.S.of(context).premiumCustomerSupport,
      lang.S.of(context).customInvoiceBranding,
      lang.S.of(context).unlimitedUsage,
      lang.S.of(context).freeDataBackup,
    ];
    List<String> titleListData = [
      lang.S.of(context).freeLifetimeUpdate,
      lang.S.of(context).android,
      lang.S.of(context).premiumCustomerSupport,
      lang.S.of(context).customInvoiceBranding,
      lang.S.of(context).unlimitedUsage,
      lang.S.of(context).freeDataBackup,
    ];

    return Consumer(
      builder: (context, ref, __) {
        print(selectedPlan?.subscriptionName ?? '');
        final theme = Theme.of(context);
        final subscriptionPlanData = ref.watch(subscriptionPlanProvider);
        final businessInfo = ref.watch(businessInfoProvider);
        final currencyData = ref.watch(currencyProvider);
        return AcnooScafoldWidget(
          appBar: AppBar(
            automaticallyImplyLeading: false,
            backgroundColor: Colors.transparent,
            title: Text(
              lang.S.of(context).purchasePremium,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w500,
                color: Colors.white,
              ),
            ),
            actions: [
              IconButton(
                padding: EdgeInsets.only(right: 24),
                onPressed: () {
                  widget.isCameBack ? Navigator.pop(context) : const Home().launch(context);
                },
                icon: Icon(
                  Icons.cancel_outlined,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          body: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.all(20.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header
                  Text.rich(
                    TextSpan(
                      text: 'Get Access To All ',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontSize: 26, fontWeight: FontWeight.w700),
                      children: [
                        WidgetSpan(
                          child: GradientText(
                            'Features',
                          ),
                        ),
                        TextSpan(
                          text: 'With ',
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontSize: 26, fontWeight: FontWeight.w700),
                          children: [
                            WidgetSpan(
                              child: GradientText(
                                '$discount OFF',
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 8),

                  // Feature List
                  ListView.builder(
                    padding: EdgeInsets.zero,
                    itemCount: imageList.length,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemBuilder: (_, i) {
                      return ListTile(
                        contentPadding: EdgeInsets.zero,
                        horizontalTitleGap: 10,
                        leading: SvgPicture.asset(
                          'assets/check_icon.svg',
                          height: 24,
                          width: 24,
                        ),
                        title: Row(
                          children: [
                            Flexible(
                              child: Text(
                                titleListData[i],
                                style: theme.textTheme.bodyLarge,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            SizedBox(width: 3),
                            Transform.flip(
                              flipY: true,
                              child: Icon(
                                Icons.info,
                                size: 16,
                                color: Color(0xff404040),
                              ),
                            ),
                          ],
                        ),
                        onTap: () {
                          showDialog(
                            context: context,
                            builder: (BuildContext context) {
                              return Dialog(
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    const SizedBox(height: 20),
                                    Row(
                                      mainAxisSize: MainAxisSize.max,
                                      mainAxisAlignment: MainAxisAlignment.end,
                                      children: [
                                        GestureDetector(
                                          child: const Icon(Icons.cancel),
                                          onTap: () {
                                            Navigator.pop(context);
                                          },
                                        ),
                                        const SizedBox(width: 20),
                                      ],
                                    ),
                                    const SizedBox(height: 20),
                                    Image(
                                      height: 200,
                                      width: 200,
                                      image: AssetImage(planDetailsImages[i]),
                                    ),
                                    const SizedBox(height: 20),
                                    Text(
                                      planDetailsText[i],
                                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                                    ),
                                    const SizedBox(height: 15),
                                    Padding(
                                      padding: const EdgeInsets.all(8.0),
                                      child: Text(
                                        lang.S.of(context).loremIpsumDolor,
                                        textAlign: TextAlign.center,
                                        style: const TextStyle(fontSize: 16),
                                      ),
                                    ),
                                    const SizedBox(height: 20),
                                  ],
                                ),
                              );
                            },
                          );
                        },
                      );
                    },
                  ),
                  const SizedBox(height: 30),

                  // Plan List
                  Text(
                    'Select Your Subscription',
                    style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600, fontSize: 20),
                  ),
                  subscriptionPlanData.when(
                    data: (data) {
                      final defaultCurrency = getDefaultCurrency(currencies: currencyData.value ?? []);
                      final currencySymbol = defaultCurrency?.symbol ?? '';
                      if (selectedPlan == null && data.isNotEmpty) {
                        selectedPlan = data[0];
                      }
                      return ListView.builder(
                        padding: EdgeInsets.zero,
                        physics: const ClampingScrollPhysics(),
                        shrinkWrap: true,
                        itemCount: data.length,
                        itemBuilder: (BuildContext context, int index) {
                          final plan = data[index];
                          final isSelected = plan.id == selectedPlan?.id;
                          final offerPrice = plan.offerPrice ?? 0;
                          final subscriptionPrice = plan.subscriptionPrice ?? 0;

                          // Calculate discount if there's an offer price
                          String discount = '';
                          if (offerPrice > 0 && subscriptionPrice > 0) {
                            final savePercentage = (100 - ((offerPrice * 100) / subscriptionPrice)).round();
                            discount = '$savePercentage%';
                          }

                          return Stack(
                            alignment: Alignment.center,
                            children: [
                              Container(
                                margin: const EdgeInsets.symmetric(vertical: 8), // Adds space between items
                                decoration: BoxDecoration(
                                  color: isSelected ? theme.colorScheme.primary.withValues(alpha: 0.08) : Colors.white,
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(
                                    width: isSelected ? 2 : 0.5, // Border width is thicker when selected
                                    color: theme.colorScheme.primary,
                                  ),
                                ),
                                child: ListTile(
                                  onTap: () {
                                    setState(() {
                                      selectedPlan = plan; // Update the selected plan when tapped
                                    });
                                  },
                                  title: Text(
                                    plan.subscriptionName ?? '',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  trailing: offerPrice > 0
                                      ? Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Text(
                                        '$currencySymbol$offerPrice',
                                        style: theme.textTheme.titleMedium?.copyWith(
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                      SizedBox(width: 4),
                                      Text(
                                        '$currencySymbol$subscriptionPrice',
                                        style: const TextStyle(
                                          decoration: TextDecoration.lineThrough,
                                          fontSize: 14,
                                          color: Colors.grey,
                                        ),
                                      ),
                                    ],
                                  )
                                      : Text(
                                    '$currencySymbol$subscriptionPrice',
                                    style: theme.textTheme.titleMedium,
                                  ),
                                ),
                              ),
                              if (offerPrice > 0 && subscriptionPrice > 0)
                                Positioned(
                                  top: 0,
                                  right: 16,
                                  child: Container(
                                    padding: EdgeInsets.symmetric(horizontal: 8),
                                    height: 24,
                                    decoration: BoxDecoration(
                                      gradient: LinearGradient(
                                        colors: [
                                          Color(0xffdc0bfe),
                                          Color(0xff1D4AFC),
                                        ],
                                        tileMode: TileMode.mirror,
                                        begin: Alignment.centerLeft,
                                        end: Alignment.centerRight,
                                      ),
                                      color: kPremiumPlanColor2,
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: Center(
                                      child: Text(
                                        '${lang.S.of(context).save}  $discount',
                                        style: const TextStyle(color: Colors.white),
                                      ),
                                    ),
                                  ),
                                ),
                            ],
                          );
                        },
                      );
                    },
                    error: (Object error, StackTrace? stackTrace) {
                      return Text(error.toString());
                    },
                    loading: () {
                      return const Center(child: CircularProgressIndicator());
                    },
                  ),

                  const SizedBox(height: 20),
                ],
              ),
            ),
          ),
          bottomNavigationBar: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
            child: Visibility(
              visible: (selectedPlan != null && (selectedPlan?.offerPrice != null ? selectedPlan!.offerPrice! > 0 : (selectedPlan?.subscriptionPrice ?? 0) > 0)),
              child: NewPrimaryButton(
                buttonText: 'Buy Now',
                onPressed: () async {
                  if (selectedPlan != null) {
                    bool success = await Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => PaymentScreen(
                            planId: selectedPlan?.id.toString() ?? '',
                            businessId: businessInfo.value?.id.toString() ?? '',
                          ),
                        ));

                    if (success) {
                      EasyLoading.showSuccess(
                        lang.S.of(context).successfullyPaid,
                        // 'successfully paid'
                      );

                      ref.refresh(businessInfoProvider);
                    } else {
                      EasyLoading.showError(
                        lang.S.of(context).field,
                        // 'Field'
                      );
                    }
                  }
                },
              ),
            ),
          ),
        );
      },
    );
  }
}

class GradientText extends StatelessWidget {
  final String text;
  const GradientText(this.text, {super.key});

  @override
  Widget build(BuildContext context) {
    return ShaderMask(
      shaderCallback: (bounds) {
        return LinearGradient(
          colors: [
            Color(0xffdc0bfe),
            Color(0xff1D4AFC),
          ],
          tileMode: TileMode.mirror,
          begin: Alignment.bottomLeft,
          end: Alignment.topRight,
        ).createShader(bounds);
      },
      child: Text(
        text,
        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: kGreyTextColor,
              fontSize: 26,
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }
}
