import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/DashBoard/dashboard.dart';
import 'package:mobile_pos/Screens/Profile%20Screen/profile_details.dart';
import 'package:mobile_pos/Screens/User%20Roles/user_role_screen.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:nb_utils/nb_utils.dart';
import '../../Provider/profile_provider.dart';
import '../../app_config/app_config.dart';
import '../../currency.dart';
import '../../model/business_info_model.dart';
import '../Authentication/Repo/logout_repo.dart';
import '../Currency/currency_screen.dart';
import '../Shimmers/home_screen_appbar_shimmer.dart';
import '../barcode/gererate_barcode.dart';
import '../language/language.dart';
import '../subscription/package_screen.dart';

class SettingScreen extends StatefulWidget {
  const SettingScreen({super.key});

  @override
  _SettingScreenState createState() => _SettingScreenState();
}

class _SettingScreenState extends State<SettingScreen> {
  String? dropdownValue = '\$ (US Dollar)';
  bool isExpanded = false;
  bool isHelpExpanded = false;
  bool isAboutExpanded = false;

  @override
  void initState() {
    super.initState();
    _initializeSettings();
  }

  Future<void> _initializeSettings() async {
    await _loadCurrency();
  }

  Future<void> _loadCurrency() async {
    final prefs = await SharedPreferences.getInstance();
    String? savedCurrency = prefs.getString('currency');

    if (savedCurrency != null && savedCurrency.isNotEmpty) {
      for (var element in items) {
        if (element.startsWith(savedCurrency)) {
          setState(() {
            dropdownValue = element;
          });
          break;
        }
      }
    } else {
      setState(() {
        dropdownValue = items[0];
      });
    }
  }

  Widget _buildListTile({
    required String title,
    required String svgAsset,
    required VoidCallback onTap,
    Widget? trailing,
    bool visible = true,
  }) {
    return visible
        ? ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(
              title,
              style: GoogleFonts.poppins(color: Color(0xff22215B), fontSize: 16.0),
            ),
            onTap: onTap,
            leading: SvgPicture.asset(
              svgAsset,
              height: 36,
              width: 36,
            ),
            trailing: trailing,
          )
        : SizedBox.shrink();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Consumer(builder: (context, ref, _) {
      AsyncValue<BusinessInformation> businessInfo = ref.watch(businessInfoProvider);
      return AcnooScafoldWidget(
        appBar: businessInfo.when(
          data: (details) {
            return AppBar(
              iconTheme: IconThemeData(color: Colors.white),
              backgroundColor: Colors.transparent,
              toolbarHeight: 80,
              titleSpacing: 0,
              title: ListTile(
                // contentPadding: EdgeInsets.symmetric(vertical: 0),
                leading: _buildAvatar(details),
                title: Text(
                  details.user?.role == 'staff' ? '${details.companyName ?? ''} [${details.user?.name ?? ''}]' : details.companyName ?? '',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                subtitle: Text(
                  '${details.enrolledPlan?.plan?.subscriptionName ?? ''} Plan',
                  style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white),
                ),
              ),
            );
          },
          error: (e, stack) => Text(e.toString()),
          loading: () => const HomeScreenAppBarShimmer(),
        ),
        body: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Profile
                _buildListTile(
                  title: l.S.of(context).profile,
                  svgAsset: 'assets/profile.svg',
                  onTap: () => const ProfileDetails().launch(context),
                  trailing: const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                ),

                // Your Package
                _buildListTile(
                  title: l.S.of(context).yourPack,
                  svgAsset: 'assets/subscription.svg',
                  onTap: () => const PackageScreen().launch(context),
                  trailing: const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                ),
                _buildListTile(
                  title: l.S.of(context).dashboard,
                  svgAsset: 'assets/dashboard.svg',
                  onTap: () => const DashboardScreen().launch(context),
                  trailing: const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                ),
                _buildListTile(
                  title: l.S.of(context).userRole,
                  svgAsset: 'assets/userRole.svg',
                  onTap: () => const UserRoleScreen().launch(context),
                  trailing: const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                  visible: businessInfo.value?.user?.role != 'staff',
                ),
                _buildListTile(
                  title: l.S.of(context).currency,
                  svgAsset: 'assets/currency.svg',
                  onTap: () async {
                    await const CurrencyScreen().launch(context);
                    setState(() {});
                  },
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        '($currency)',
                        style: GoogleFonts.poppins(color: Colors.black, fontSize: 14.0),
                      ),
                      const SizedBox(width: 4.0),
                      const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                    ],
                  ),
                ),
                _buildListTile(
                  title: l.S.of(context).barcodeGenerator,
                  svgAsset: 'assets/print.svg',
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const BarcodeGeneratorScreen()),
                  ),
                  trailing: const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                ),
                _buildListTile(
                  title: l.S.of(context).selectLang,
                  svgAsset: 'assets/language.svg',
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const SelectLanguage()),
                  ),
                  trailing: const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                ),
                _buildListTile(
                  title: l.S.of(context).logOut,
                  svgAsset: 'assets/logout.svg',
                  onTap: () async {
                    ref.invalidate(businessInfoProvider);
                    EasyLoading.show(status: l.S.of(context).logOut);
                    LogOutRepo repo = LogOutRepo();
                    await repo.signOutApi(context: context, ref: ref);
                  },
                  trailing: const Icon(Icons.arrow_forward_ios, color: Color(0xff22215B), size: 16),
                ),
                SizedBox(height: 16),
                Text(
                  '${AppConfig.appName} V-${AppConfig.appVersion}',
                  style: theme.textTheme.bodyMedium?.copyWith(color: Color(0xff22215B)),
                ),
              ],
            ),
          ),
        ),
      );
    });
  }

  Widget _buildAvatar(BusinessInformation details) {
    return Container(
      height: 42,
      width: 42,
      decoration: BoxDecoration(
        image: details.pictureUrl == null
            ? const DecorationImage(image: AssetImage('images/nAvatar.png'), fit: BoxFit.cover)
            : DecorationImage(image: NetworkImage(APIConfig.domain + details.pictureUrl.toString()), fit: BoxFit.cover),
        borderRadius: BorderRadius.circular(50),
      ),
    );
  }
}
