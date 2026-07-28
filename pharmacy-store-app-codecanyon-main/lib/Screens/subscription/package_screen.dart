import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Provider/profile_provider.dart';
import 'package:mobile_pos/Screens/subscription/purchase_premium_plan_screen.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:nb_utils/nb_utils.dart';

import '../widget/key_value_widget.dart';

class PackageScreen extends StatefulWidget {
  const PackageScreen({super.key});

  @override
  State<PackageScreen> createState() => _PackageScreenState();
}

class _PackageScreenState extends State<PackageScreen> {
  Duration? remainTime;
  List<String> imageList = [
    'images/sales_2.png',
    'images/purchase_2.png',
    'images/due_collection_2.png',
    'images/parties_2.png',
    'images/product1.png',
  ];

  bool _isExpanded = false;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    List<String> nameList = [
      l.S.of(context).sales,
      l.S.of(context).purchase,
      l.S.of(context).dueCollection,
      l.S.of(context).parties,
      l.S.of(context).products,
    ];
    return Consumer(builder: (context, ref, __) {
      final profileInfo = ref.watch(businessInfoProvider);
      return profileInfo.when(
        data: (info) {
          return AcnooScafoldWidget(
            appBar: AppBar(
              backgroundColor: Colors.transparent,
              title: Text(l.S.of(context).yourPack, style: theme.textTheme.titleLarge?.copyWith(color: Colors.white)),
              centerTitle: true,
              iconTheme: const IconThemeData(color: Colors.white),
              elevation: 0.0,
            ),
            bottomNavigationBar: Container(
              padding: EdgeInsets.symmetric(horizontal: 24, vertical: 24),
              child: Visibility(
                visible: info.user?.role != 'staff',
                child: NewPrimaryButton(
                  buttonText: l.S.of(context).updateNow,
                  onPressed: () {
                    const PurchasePremiumPlanScreen(
                      isCameBack: true,
                    ).launch(context);
                  },
                ),
              ),
            ),
            body: SingleChildScrollView(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header Section
                    InkWell(
                      onTap: () {
                        setState(() {
                          _isExpanded = !_isExpanded;
                          int daysLeft =
                              DateTime.parse(info.subscriptionDate ?? '').add(Duration(days: info.enrolledPlan?.duration?.toInt() ?? 0)).difference(DateTime.now()).inDays;
                          if (daysLeft >= 0) {
                            print('$daysLeft Days Left');
                          } else {
                            print('Expired');
                          }
                        });
                      },
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 300), // Duration of animation
                        curve: Curves.easeInOut, // Smooth animation curve
                        padding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                        width: double.infinity,
                        decoration: BoxDecoration(color: kMainColor.withValues(alpha: 0.1), borderRadius: const BorderRadius.all(Radius.circular(10))),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.start,
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Text(
                                        (info.enrolledPlan?.price ?? 0) > 0 ? l.S.of(context).premiumPlan : l.S.of(context).freePlan,
                                        style: const TextStyle(fontSize: 18),
                                      ),
                                      const SizedBox(height: 8),
                                      Text.rich(TextSpan(text: l.S.of(context).youRUsing, children: [
                                        TextSpan(
                                          text: '${info.enrolledPlan?.plan?.subscriptionName} ${lang.package}',
                                          style: TextStyle(fontWeight: FontWeight.bold, color: kMainColor),
                                        )
                                      ])),
                                    ],
                                  ),
                                ),
                                SizedBox(width: 8),
                                Container(
                                  padding: EdgeInsets.all(2),
                                  height: 65,
                                  width: 65,
                                  decoration: const BoxDecoration(
                                    color: kMainColor,
                                    borderRadius: BorderRadius.all(
                                      Radius.circular(50),
                                    ),
                                  ),
                                  child: Center(
                                    child: Text(
                                      (() {
                                        // '${(DateTime.parse(info.subscriptionDate ?? '').difference(
                                        //   DateTime.now(),
                                        // ).inDays.abs() - (info.enrolledPlan?.duration ?? 0)).abs()} \nDays Left',
                                        DateTime subscriptionDate = DateTime.parse(info.subscriptionDate ?? '');
                                        num duration = info.enrolledPlan?.duration ?? 0;
                                        DateTime expirationDate = subscriptionDate.add(Duration(days: duration.toInt()));
                                        num daysLeft = expirationDate.difference(DateTime.now()).inDays;
                                        return daysLeft >= 0 ? '$daysLeft ${lang.daysLeft}' : lang.expired;
                                      })(),
                                      textAlign: TextAlign.center,
                                      style: theme.textTheme.bodySmall?.copyWith(
                                        color: Colors.white,
                                        fontSize: 10,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            AnimatedSize(
                              duration: const Duration(milliseconds: 300),
                              curve: Curves.easeInOut,
                              child: ConstrainedBox(
                                constraints: BoxConstraints(
                                  maxHeight: _isExpanded ? double.infinity : 0,
                                ),
                                child: AnimatedOpacity(
                                  duration: const Duration(milliseconds: 300),
                                  opacity: _isExpanded ? 1.0 : 0.0, // Fade in and out
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Divider(
                                        color: kOutlineColor,
                                      ),
                                      ...{
                                        lang.subscribedOn: '${DateFormat.yMMMd().format(
                                          DateTime.parse(
                                            info.subscriptionDate.toString(),
                                          ),
                                        )}, ${DateFormat.jms().format(
                                          DateTime.parse(
                                            info.subscriptionDate.toString(),
                                          ),
                                        )}',
                                        lang.endDate: '${DateFormat.yMMMd().format(
                                          DateTime.parse(
                                            info.willExpire.toString(),
                                          ),
                                        )}, ${DateFormat.jms().format(
                                          DateTime.parse(
                                            info.willExpire.toString(),
                                          ),
                                        )}',
                                        lang.duration: '${info.enrolledPlan?.duration} ${lang.days}'
                                      }.entries.map(
                                        (entry) {
                                          return KeyValueRow(
                                            title: entry.key,
                                            titleFlex: 6,
                                            description: entry.value.toString(),
                                            descriptionFlex: 8,
                                          );
                                        },
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      l.S.of(context).packFeatures,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                        fontSize: 16,
                      ),
                    ),
                    ListView.builder(
                      padding: EdgeInsets.only(top: 16),
                      itemCount: nameList.length,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemBuilder: (_, i) {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: GestureDetector(
                            onTap: () {},
                            child: Container(
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(6),
                                color: kWhite,
                                boxShadow: [
                                  BoxShadow(color: const Color(0xff0C1A4B).withValues(alpha: 0.24), blurRadius: 1),
                                  BoxShadow(color: const Color(0xff473232).withValues(alpha: 0.05), offset: const Offset(0, 3), spreadRadius: -1, blurRadius: 8)
                                ],
                              ),
                              child: ListTile(
                                visualDensity: const VisualDensity(vertical: -4),
                                horizontalTitleGap: 10,
                                contentPadding: const EdgeInsets.only(left: 6, top: 6, bottom: 6, right: 12),
                                leading: SizedBox(
                                  height: 40,
                                  width: 40,
                                  child: Image(
                                    image: AssetImage(imageList[i]),
                                  ),
                                ),
                                title: Text(nameList[i], style: theme.textTheme.titleMedium),
                                trailing: Text(
                                  l.S.of(context).unlimited,
                                  style: const TextStyle(color: Colors.grey),
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
          );
        },
        error: (error, stackTrace) {
          return Text(error.toString());
        },
        loading: () {
          return const CircularProgressIndicator();
        },
      );
    });
  }
}
