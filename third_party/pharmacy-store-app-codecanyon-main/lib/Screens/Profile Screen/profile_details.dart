import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Profile%20Screen/edit_profile.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../app_config/api_config.dart';
import '../../Provider/profile_provider.dart';
import '../../constant.dart';

class ProfileDetails extends StatefulWidget {
  const ProfileDetails({super.key});

  @override
  _ProfileDetailsState createState() => _ProfileDetailsState();
}

class _ProfileDetailsState extends State<ProfileDetails> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(builder: (context, ref, __) {
      final businessInfo = ref.watch(businessInfoProvider);
      return businessInfo.when(data: (details) {
        return AcnooScafoldWidget(
          appBar: AppBar(
            title: Text(
              lang.myProfile,
              style: theme.textTheme.titleLarge?.copyWith(color: Colors.white),
            ),
            iconTheme: const IconThemeData(color: Colors.white),
            centerTitle: true,
            backgroundColor: Colors.transparent,
            elevation: 0.0,
          ),
          body: Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: 24,
              vertical: 30,
            ),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Profile Picture
                  Center(
                    child: Column(
                      children: [
                        Container(
                          height: 100.0,
                          width: 100.0,
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
                                    image: NetworkImage(APIConfig.domain + details.pictureUrl.toString()),
                                    fit: BoxFit.cover,
                                  ),
                                  borderRadius: BorderRadius.circular(50),
                                ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          details.user?.name ?? 'n/a',
                          style: theme.textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24.0),
                  // Business name
                  _buildTitleAndDescription(
                    title: lang.companyAndBusinessName,
                    description: details.user?.name ?? 'n/a',
                  ),
                  SizedBox(height: 17),
                  // Business Category
                  _buildTitleAndDescription(
                    title: lang.businessCategory,
                    description: details.category?.name ?? 'n/a',
                  ),
                  SizedBox(height: 17),
                  // email
                  _buildTitleAndDescription(
                    title: lang.email,
                    description: details.user?.email ?? 'n/a',
                  ),
                  SizedBox(height: 17),

                  // Phone Number
                  _buildTitleAndDescription(
                    title: lang.phoneNumber,
                    description: details.phoneNumber ?? 'n/a',
                  ),

                  SizedBox(height: 17),

                  // Opening Balance
                  _buildTitleAndDescription(
                    title: lang.shopOpeningBalance,
                    description: details.shopOpeningBalance.toString() ?? 'n/a',
                  ),
                  SizedBox(height: 17),
                  // Opening Balance
                  _buildTitleAndDescription(
                    title: lang.shopRemainingBalance,
                    description: details.remainingShopBalance.toString() ?? 'n/a',
                  ),
                ],
              ),
            ),
          ),
          bottomNavigationBar: Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: 24.0,
              vertical: 16,
            ),
            child: NewPrimaryButton(
              buttonText: lang.editProfile,
              onPressed: () async {
                await Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => EditProfile(
                        profile: details,
                        ref: ref,
                      ),
                    ));
                setState(() {});
              },
            ),
          ),
        );
      }, error: (e, stack) {
        return Text(e.toString());
      }, loading: () {
        return const CircularProgressIndicator();
      });
    });
  }

  Widget _buildTitleAndDescription({
    required String title,
    required String description,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w400,
                color: kTitle3,
              ),
        ),
        SizedBox(height: 3),
        Text(
          description,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w500,
              ),
        ),
      ],
    );
  }
}
