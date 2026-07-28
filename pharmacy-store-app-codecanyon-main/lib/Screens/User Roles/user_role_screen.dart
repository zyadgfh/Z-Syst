import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/Screens/User%20Roles/user_role_details.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';

import '../../constant.dart';
import 'Provider/user_role_provider.dart';
import 'add_user_role_screen.dart';

class UserRoleScreen extends StatefulWidget {
  const UserRoleScreen({super.key});

  @override
  State<UserRoleScreen> createState() => _UserRoleScreenState();
}

class _UserRoleScreenState extends State<UserRoleScreen> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Consumer(builder: (context, ref, __) {
      final userRoleData = ref.watch(userRoleProvider);
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: Text(
            lang.S.of(context).userRole,
            style: theme.textTheme.titleMedium?.copyWith(color: Colors.white),
          ),
          centerTitle: true,
          iconTheme: const IconThemeData(color: Colors.white),
          elevation: 0.0,
        ),
        body: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
            child: userRoleData.when(data: (users) {
              if (users.isNotEmpty) {
                return ListView.builder(
                  padding: EdgeInsets.zero,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: users.length,
                  shrinkWrap: true,
                  itemBuilder: (BuildContext context, int index) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 15),
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
                          visualDensity: const VisualDensity(vertical: -4, horizontal: -4),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 10),
                          onTap: () {
                            UserRoleDetails(
                              userRoleModel: users[index],
                            ).launch(context);
                          },
                          title: Text(
                            users[index].email ?? '',
                            style: theme.textTheme.bodyLarge,
                          ),
                          subtitle: Text(users[index].name ?? '', style: theme.textTheme.bodyMedium),
                          trailing: const Icon(
                            Icons.arrow_forward_ios,
                            color: kGreyTextColor,
                            size: 20,
                          ),
                        ),
                      ),
                    );
                  },
                );
              } else {
                return Center(child: Text(lang.S.of(context).noRoleFound));
              }
            }, error: (e, stack) {
              return Text(e.toString());
            }, loading: () {
              return const Center(child: CircularProgressIndicator());
            }),
          ),
        ),
        bottomNavigationBar: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24,vertical: 24),
          child: NewPrimaryButton(
            buttonText: lang.S.of(context).addUserRole,
            onPressed: () => const AddUserRole().launch(context),
          ),
        ),
      );
    });
  }
}
