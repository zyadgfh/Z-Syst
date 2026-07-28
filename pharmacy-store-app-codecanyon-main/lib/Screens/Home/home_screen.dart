import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:mobile_pos/Screens/ExpiryAlerts/expiry_alert_screen.dart';
import 'package:mobile_pos/Screens/ExpiryAlerts/repo/expiry_alert_repo.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/Home/components/grid_items.dart';
import 'package:mobile_pos/Screens/Profile%20Screen/profile_details.dart';
import 'package:mobile_pos/currency.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import '../../Provider/profile_provider.dart';
import '../../constant.dart';
import '../../model/business_info_model.dart' as business;
import '../DashBoard/components.dart';
import '../banner/provider/banner_provider.dart';
import 'components/home_screen_shemmer.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  @override
  Widget build(BuildContext context) {
    final lan = lang.S.of(context);
    return Consumer(builder: (_, ref, __) {
      final businessInfo = ref.watch(businessInfoProvider);
      final summaryInfo = ref.watch(summaryInfoProvider);
      final banner = ref.watch(bannerProvider);
      return businessInfo.when(data: (details) {
        return Scaffold(
          backgroundColor: Colors.white,
          body: summaryInfo.when(
            data: (summary) {
              return RefreshIndicator.adaptive(
                onRefresh: () async {
                  // ignore: unused_result
                  ref.refresh(summaryInfoProvider);
                  // ignore: unused_result
                  ref.refresh(businessInfoProvider);
                  // ignore: unused_result
                  ref.refresh(bannerProvider);
                  return Future.value();
                },
                child: CustomScrollView(
                  slivers: [
                      // SliverAppBar with notification bell
                      SliverAppBar(
                        surfaceTintColor: kMainColor,
                        backgroundColor: kMainColor,
                        foregroundColor: kMainColor,
                        floating: true,
                        snap: true,
                        pinned: true,
                        leading: Padding(
                          padding: const EdgeInsets.all(10),
                          child: GestureDetector(
                            onTap: () {
                              const ProfileDetails().launch(context);
                            },
                            child: Container(
                              height: 50,
                              width: 50,
                              decoration: details.pictureUrl == null
                                  ? BoxDecoration(
                                      image: const DecorationImage(
                                        image: AssetImage('images/nAvatar.png'),
                                        fit: BoxFit.cover,
                                      ),
                                      borderRadius: BorderRadius.circular(50),
                                    )
                                  : BoxDecoration(
                                      image: DecorationImage(
                                        image: NetworkImage('${APIConfig.domain}${details.pictureUrl}'),
                                        fit: BoxFit.cover,
                                      ),
                                      borderRadius: BorderRadius.circular(50),
                                    ),
                            ),
                          ),
                        ),
                        title: InkWell(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.start,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                details.user?.role == 'staff' ? '${details.companyName ?? ''} [${details.user?.name ?? ''}]' : details.companyName ?? '',
                                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                      color: kWhite,
                                      fontWeight: FontWeight.w600,
                                    ),
                              ),
                              Text(
                                "${details.enrolledPlan?.plan?.subscriptionName ?? ''} Plan",
                                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                      color: kWhite,
                                      fontSize: 13,
                                    ),
                              ),
                            ],
                          ),
                        ),
                        actions: [
                          // Notification Bell Icon with Badge
                          _NotificationBell(),
                        ],
                        elevation: 0,
                        flexibleSpace: FlexibleSpaceBar(
                          background: Container(
                            decoration: const BoxDecoration(
                              gradient: LinearGradient(colors: [
                                Color(0xff00987F),
                                Color(0xff14B8A6),
                              ]),
                            ),
                          ),
                        ),
                      ),

                    // SliverPersistentHeader
                    SliverPersistentHeader(
                      pinned: false,
                      delegate: _SliverAppBarDelegate(
                        minHeight: 130.0,
                        maxHeight: 130.0,
                        child: Stack(
                          alignment: AlignmentDirectional.center,
                          children: [
                            Column(
                              children: [
                                Container(
                                  height: 70,
                                  decoration: BoxDecoration(
                                    gradient: LinearGradient(
                                      colors: [
                                        Color(0xff00987F),
                                        Color(0xff14B8A6),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Stack(
                                      alignment: Alignment.topRight,
                                      children: [
                                        DashBordContainerWidget(
                                          gradient: const LinearGradient(
                                            colors: [
                                              Color(0xffFFFFFF),
                                              Color(0xffCDFFD6),
                                            ],
                                            begin: Alignment.topCenter,
                                            end: Alignment.bottomCenter,
                                          ),
                                          title: '$currency ${summary.data?.sales?.toStringAsFixed(2) ?? 0}',
                                          subtitle: lan.todaySales,
                                        ),
                                        const HugeIcon(
                                          icon: HugeIcons.strokeRoundedArrowUpRight02,
                                          color: kNutral700,
                                          size: 25.0,
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 16),
                                  Expanded(
                                    child: Stack(
                                      alignment: Alignment.topRight,
                                      children: [
                                        DashBordContainerWidget(
                                          gradient: const LinearGradient(
                                            colors: [
                                              Color(0xffFFFFFF),
                                              Color(0xffFFECCD),
                                            ],
                                            begin: Alignment.topCenter,
                                            end: Alignment.bottomCenter,
                                          ),
                                          title: '$currency ${summary.data?.purchase?.toStringAsFixed(2) ?? 0}',
                                          subtitle: lan.todayPurchase,
                                        ),
                                        const HugeIcon(
                                          icon: HugeIcons.strokeRoundedArrowUpRight02,
                                          color: kNutral700,
                                          size: 25.0,
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    // SliverList
                    SliverList(
                      delegate: SliverChildListDelegate(
                        [
                          Padding(
                            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Today's Profit/Loss
                                DashBordContainerWidget(
                                  gradient: LinearGradient(
                                    colors: [
                                      const Color(0xffE1FEFA).withValues(alpha: 0.8),
                                      const Color(0xffC6F2ED).withValues(alpha: 0.8),
                                    ],
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                  ),
                                  title: '$currency ${summary.data?.profit?.abs() ?? 0}',
                                  subtitle: (summary.data?.profit ?? 0) < 0 ? lan.loss : lan.todayProfit,
                                  subTitleStyle: Theme.of(context).textTheme.titleSmall?.copyWith(
                                        color: kNutral700,
                                      ),
                                ),
                                const SizedBox(height: 24),

                                // Quick Actions
                                Text(
                                  lan.quickAction,
                                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                        fontWeight: FontWeight.w600,
                                      ),
                                ),
                                const SizedBox(height: 12),
                                GridView.builder(
                                  padding: EdgeInsets.zero,
                                  physics: const NeverScrollableScrollPhysics(),
                                  shrinkWrap: true,
                                  itemCount: getFreeIcons(context: context).length,
                                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                                      crossAxisCount: 4, crossAxisSpacing: 15, mainAxisSpacing: 15, childAspectRatio: 0.7, mainAxisExtent: 100),
                                  itemBuilder: (context, index) => HomeGridCards(
                                    gridItems: getFreeIcons(context: context)[index],
                                    visibility: businessInfo.value?.user?.visibility,
                                  ),
                                ),

                                const SizedBox(height: 24),

                                // What's New Section
                                Text(
                                  'What\'s new',
                                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                        fontWeight: FontWeight.w600,
                                      ),
                                ),
                                const SizedBox(height: 12),
                                banner.when(
                                  data: (bannerSnap) {
                                    if (bannerSnap.data == null || bannerSnap.data!.isEmpty) {
                                      return Center(
                                        child: Column(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            Icon(
                                              Icons.image_not_supported_outlined,
                                              size: 50,
                                              color: Colors.grey.shade400,
                                            ),
                                            const SizedBox(height: 10),
                                            Text(
                                              'No banners available',
                                              style: TextStyle(
                                                fontSize: 16,
                                                color: Colors.grey.shade600,
                                              ),
                                            ),
                                          ],
                                        ),
                                      );
                                    }
                                    return SizedBox(
                                      height: 150,
                                      // width: 329,
                                      child: ListView.builder(
                                        shrinkWrap: true,
                                        scrollDirection: Axis.horizontal,
                                        // controller: PageController(viewportFraction: 0.9),
                                        itemCount: bannerSnap.data!.length,
                                        itemBuilder: (context, index) {
                                          final banner = bannerSnap.data![index];
                                          return Container(
                                            margin: const EdgeInsets.only(right: 10),
                                            height: 150,
                                            width: 329,
                                            decoration: BoxDecoration(
                                              // gradient: LinearGradient(
                                              //   begin: Alignment.bottomCenter,
                                              //   end: Alignment.topCenter,
                                              //   colors: [
                                              //     Colors.black.withOpacity(0.1),
                                              //     Colors.transparent,
                                              //   ],
                                              // ),
                                              border: Border.all(color: kBorderColorTextField.withValues(alpha: 0.1)),
                                              borderRadius: BorderRadius.circular(12),
                                              image: DecorationImage(
                                                  image: NetworkImage(
                                                    '${APIConfig.domain}${banner.imageUrl}',
                                                  ),
                                                  fit: BoxFit.cover),
                                            ),
                                          );
                                        },
                                      ),
                                    );
                                  },
                                  error: (e, stack) {
                                    return Center(
                                      child: Text(
                                        'Failed to load banners: $e',
                                        style: TextStyle(
                                          color: Colors.red.shade600,
                                          fontSize: 14,
                                        ),
                                      ),
                                    );
                                  },
                                  loading: () {
                                    return const Center(
                                      child: CircularProgressIndicator(),
                                    );
                                  },
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
            error: (e, stack) {
              return Center(child: Text(e.toString()));
            },
            loading: () {
              return const Center(child: CircularProgressIndicator());
            },
          ),
        );
      }, error: (e, stack) {
        return Text(e.toString());
      }, loading: () {
        return const HomeScreenShimmer();
      });
    });
  }
}

class _SliverAppBarDelegate extends SliverPersistentHeaderDelegate {
  _SliverAppBarDelegate({
    required this.minHeight,
    required this.maxHeight,
    required this.child,
  });

  final double minHeight;
  final double maxHeight;
  final Widget child;

  @override
  double get minExtent => minHeight;

  @override
  double get maxExtent => maxHeight;

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return SizedBox.expand(child: child);
  }

  @override
  bool shouldRebuild(_SliverAppBarDelegate oldDelegate) {
    return maxHeight != oldDelegate.maxHeight || minHeight != oldDelegate.minHeight || child != oldDelegate.child;
  }
}

/// Notification Bell Widget with live expiry count badge
class _NotificationBell extends ConsumerStatefulWidget {
  @override
  ConsumerState<_NotificationBell> createState() => _NotificationBellState();
}

class _NotificationBellState extends ConsumerState<_NotificationBell> {
  int _expiryCount = 0;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadExpiryCount();
  }

  Future<void> _loadExpiryCount() async {
    try {
      final repo = ExpiryAlertRepo();
      final stats = await repo.getStats();
      if (mounted) {
        setState(() {
          _expiryCount = stats?.totalCritical ?? 0;
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          icon: const Icon(Icons.notifications_outlined, color: Colors.white, size: 28),
          onPressed: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => const ExpiryAlertScreen(),
              ),
            );
          },
          tooltip: 'Expiry Alerts',
        ),
        if (!_isLoading && _expiryCount > 0)
          Positioned(
            right: 6,
            top: 4,
            child: Container(
              padding: const EdgeInsets.all(4),
              decoration: const BoxDecoration(
                color: Colors.red,
                shape: BoxShape.circle,
              ),
              constraints: const BoxConstraints(minWidth: 20, minHeight: 20),
              child: Text(
                '$_expiryCount',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
            ),
          ),
      ],
    );
  }
}

class HomeGridCards extends StatefulWidget {
  const HomeGridCards({
    super.key,
    required this.gridItems,
    this.visibility,
  });
  final GridItems gridItems;
  final business.Visibility? visibility;

  @override
  State<HomeGridCards> createState() => _HomeGridCardsState();
}

class _HomeGridCardsState extends State<HomeGridCards> {
  bool checkPermission({required String item}) {
    if (item == 'Sales' && (widget.visibility?.salePermission ?? true)) {
      return true;
    } else if (item == 'Parties' && (widget.visibility?.partiesPermission ?? true)) {
      return true;
    } else if (item == 'Purchase' && (widget.visibility?.purchasePermission ?? true)) {
      return true;
    } else if (item == 'Products' && (widget.visibility?.productPermission ?? true)) {
      return true;
    } else if (item == 'Due List' && (widget.visibility?.dueListPermission ?? true)) {
      return true;
    } else if (item == 'Stock' && (widget.visibility?.stockPermission ?? true)) {
      return true;
    } else if (item == 'Reports' && (widget.visibility?.reportsPermission ?? true)) {
      return true;
    } else if (item == 'Sales List' && (widget.visibility?.salesListPermission ?? true)) {
      return true;
    } else if (item == 'Purchase List' && (widget.visibility?.purchaseListPermission ?? true)) {
      return true;
    } else if (item == 'Loss/Profit' && (widget.visibility?.lossProfitPermission ?? true)) {
      return true;
    } else if (item == 'Expense' && (widget.visibility?.addExpensePermission ?? true)) {
      return true;
    } else if (item == 'Income' && (widget.visibility?.addExpensePermission ?? true)) {
      return true;
    } else if (item == 'Expiring') {
      return true;
    } else if (item == 'taxReport') {
      return true;
    } else if (item == 'ledger_details') {
      return true;
    }
    return false;
  }

  @override
  Widget build(BuildContext context) {
    // ignore: avoid_unnecessary_containers
    return Consumer(builder: (context, ref, __) {
      return GestureDetector(
        onTap: () async {
          if (checkPermission(item: widget.gridItems.route)) {
            Navigator.of(context).pushNamed('/${widget.gridItems.route}');
          } else {
            EasyLoading.showError(
              lang.S.of(context).permissionNotGranted,
              // 'Permission not granted!'
            );
          }
        },
        child: Column(
          children: [
            SvgPicture.asset(
              widget.gridItems.icon.toString(),
              height: 66,
              width: 66,
            ),
            const SizedBox(width: 10),
            Flexible(
                child: Text(
              widget.gridItems.title.toString(),
              style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w500, color: kNutrals900, fontSize: 14),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ))
          ],
        ),
      );
    });
  }
}
