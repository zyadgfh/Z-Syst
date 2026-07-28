import 'dart:ui';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_svg/svg.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:material_design_icons_flutter/material_design_icons_flutter.dart';
import 'package:mobile_pos/Screens/DashBoard/dashboard.dart';
import 'package:mobile_pos/Screens/Home/home_screen.dart';
import 'package:mobile_pos/Screens/Home/shape_painter.dart';
import 'package:mobile_pos/Screens/Products/add%20product/add_product.dart';
import 'package:mobile_pos/Screens/Report/reports.dart';
import 'package:mobile_pos/Screens/Settings/settings_screen.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../internet checker/Internet_check_provider/util/network_observer_provider.dart';

class Home extends StatefulWidget {
  const Home({super.key});

  @override
  _HomeState createState() => _HomeState();
}

class _HomeState extends State<Home> with SingleTickerProviderStateMixin {
  int _tabIndex = 0; // Set initial index for "Product"
  late PageController pageController;
  late TabController _tabController;
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    pageController = PageController(initialPage: _tabIndex);
    _tabController = TabController(length: 5, vsync: this, initialIndex: _tabIndex);

    _tabController.addListener(() {
      if (_tabController.index != _tabIndex) {
        setState(() {
          _tabIndex = _tabController.index;
        });
        pageController.jumpToPage(_tabIndex);
      }
    });
  }

  @override
  void dispose() {
    pageController.dispose();
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        await showCupertinoDialog<bool>(
          context: context,
          builder: (context) {
            return Stack(
              children: [
                Positioned.fill(
                  child: BackdropFilter(
                    filter: ImageFilter.blur(sigmaX: 10.0, sigmaY: 10.0),
                    child: Container(
                      color: Colors.black.withValues(alpha: 0),
                    ),
                  ),
                ),
                // CupertinoAlertDialog
                Center(
                  child: CupertinoAlertDialog(
                    title: Text(
                      '${lang.exit}!!',
                      style: theme.textTheme.titleLarge?.copyWith(color: Colors.red),
                    ),
                    content: Text(lang.areYouSureYouWantToCloseTheApp),
                    actions: [
                      CupertinoDialogAction(
                        onPressed: () => Navigator.of(context).pop(false),
                        child: Text(
                          lang.no,
                          style: theme.textTheme.bodySmall?.copyWith(color: Colors.red),
                        ),
                      ),
                      CupertinoDialogAction(
                        onPressed: () {
                          SystemNavigator.pop();
                        },
                        child: Text(
                          lang.yes,
                          style: theme.textTheme.bodySmall?.copyWith(color: kMainColor),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            );
          },
        );
      },
      child: ProviderNetworkObserver(
        child: Scaffold(
          backgroundColor: Colors.white,
          body: PageView(
            physics: NeverScrollableScrollPhysics(),
            controller: pageController,
            onPageChanged: (v) {
              setState(() {
                _tabIndex = v;
                _tabController.index = v;
              });
            },
            children: [
              const HomeScreen(),
              const DashboardScreen(),
              AddProduct(isFromHome: true),
              const Reports(),
              const SettingScreen(),
            ],
          ),
          bottomNavigationBar: CustomPaint(
            size: Size(double.infinity, 80),
            painter: RPSCustomPainter(),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 10),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Expanded(child: _buildNavItem(HugeIcons.strokeRoundedHome01, lang.home, 0, iconWidget: _currentIndex == 0 ? SvgPicture.asset('assets/logo/home.svg') : null)),
                  Expanded(
                      child: _buildNavItem(
                    HugeIcons.strokeRoundedChartHighLow,
                    lang.dashboard,
                    1,
                    iconWidget: SvgPicture.asset(_currentIndex == 1 ? 'assets/logo/dbord.svg' : 'assets/logo/dashboard.svg',
                        colorFilter: ColorFilter.mode(_currentIndex == 1 ? kMainColor : kGreyTextColor, BlendMode.srcIn)),
                  )),
                  Expanded(
                    child: GestureDetector(
                      onTap: () {
                        setState(() {
                          _currentIndex = 2;
                          _tabIndex = 2;
                        });
                        pageController.jumpToPage(_tabIndex);
                      },
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          SvgPicture.asset(
                            'assets/logo/product.svg',
                          ),
                          Text(
                            lang.product,maxLines: 1,overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: _currentIndex == 2 ? kMainColor : kGreyTextColor,
                              fontSize: 12,
                              fontWeight: _currentIndex == 2 ? FontWeight.w600 : FontWeight.normal,
                            ),
                          )
                        ],
                      ),
                    ),
                  ),
                  Expanded(
                    child: _buildNavItem(HugeIcons.strokeRoundedTask02, lang.reports, 3, iconWidget: _currentIndex == 3 ? SvgPicture.asset('assets/logo/reports.svg') : null),
                  ),
                  Expanded(child: _buildNavItem(HugeIcons.strokeRoundedUser, lang.profile, 4, iconWidget: _currentIndex == 4 ? SvgPicture.asset('assets/logo/profile.svg') : null)),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem(IconData icon, String label, int index, {Widget? iconWidget}) {
    final isSelected = _currentIndex == index;
    return GestureDetector(
      onTap: () {
        setState(() {
          _currentIndex = index;
          _tabIndex = index;
        });
        pageController.jumpToPage(_tabIndex);
      },
      child: Padding(
        padding: const EdgeInsets.only(top: 22),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.center,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            iconWidget ??
                Icon(
                  icon,
                  color: isSelected ? kMainColor : kGreyTextColor,
                ),
            SizedBox(height: 5),
            Text(
              label,maxLines: 1,
              style: TextStyle(
                color: isSelected ? kMainColor : kGreyTextColor,
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,overflow: TextOverflow.ellipsis
              ),
            ),
          ],
        ),
      ),
    );
  }
}
