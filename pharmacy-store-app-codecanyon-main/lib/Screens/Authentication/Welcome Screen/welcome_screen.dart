import 'package:flutter/material.dart';
import 'package:mobile_pos/GlobalComponents/button_global.dart';
import 'package:mobile_pos/Screens/Authentication/Sign%20In/sign_in_screen.dart';
import 'package:mobile_pos/Screens/Authentication/Sign%20Up/sign_up_screen.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:mobile_pos/constant.dart';

import '../../widget/acnoo_scafold.dart';

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return AcnooScafoldWidget(
        body: Padding(
      padding: const EdgeInsets.symmetric(vertical: 40, horizontal: 16),
      child: Column(
        children: [
          const NameWithLogo(),
          const SizedBox(
            height: 40,
          ),
          Text(
            lang.createAFreeAccount,
            style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
          ),
          const SizedBox(
            height: 16,
          ),
          Text(
            lang.welcomeToOurPlatform,
            style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
            textAlign: TextAlign.center,
          ),
          const SizedBox(
            height: 32,
          ),
          NewPrimaryButton(
            buttonText: lang.logIn,
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (context) => const SignIn()));
            },
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (context) => const SignUpScreen()));
                },
                child: Text(
                  lang.register,
                  style: theme.textTheme.bodyLarge?.copyWith(
                      // fontSize: 18,
                      fontWeight: FontWeight.w500,
                      color: kMainColor),
                )),
          ),
        ],
      ),
    ));
  }
}
