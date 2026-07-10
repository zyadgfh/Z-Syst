import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/constant.dart';

import '../app_config/app_config.dart';

// ignore: must_be_immutable
class ButtonGlobal extends StatelessWidget {
  // ignore: prefer_typing_uninitialized_variables
  var iconWidget;
  final String buttontext;
  final Color iconColor;
  final Decoration? buttonDecoration;
  // ignore: prefer_typing_uninitialized_variables
  var onPressed;

  // ignore: use_key_in_widget_constructors
  ButtonGlobal({required this.iconWidget, required this.buttontext, required this.iconColor, this.buttonDecoration, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    return TextButton(
      onPressed: onPressed,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.only(top: 10.0, bottom: 10.0),
        decoration: buttonDecoration ?? BoxDecoration(borderRadius: BorderRadius.circular(8), color: kMainColor),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              buttontext,
              style: GoogleFonts.jost(fontSize: 20.0, color: Colors.white),
            ),
            const SizedBox(
              width: 2,
            ),
            Icon(
              iconWidget,
              color: iconColor,
            ),
          ],
        ),
      ),
    );
  }
}

// ignore: must_be_immutable
class ButtonGlobalWithoutIcon extends StatelessWidget {
  final String buttontext;
  final Decoration buttonDecoration;
  // ignore: prefer_typing_uninitialized_variables
  var onPressed;
  final Color buttonTextColor;

  // ignore: use_key_in_widget_constructors
  ButtonGlobalWithoutIcon({required this.buttontext, required this.buttonDecoration, required this.onPressed, required this.buttonTextColor});

  @override
  Widget build(BuildContext context) {
    return TextButton(
      onPressed: onPressed,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.only(top: 10.0, bottom: 10.0),
        decoration: buttonDecoration,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              buttontext,
              style: GoogleFonts.jost(fontSize: 20.0, color: buttonTextColor),
            ),
          ],
        ),
      ),
    );
  }
}

///-----------------------name with logo------------------
class NameWithLogo extends StatelessWidget {
  const NameWithLogo({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      children: [
        Container(
          height: 99.65,
          width: 83.33,
          decoration: BoxDecoration(
            image: DecorationImage(
              image: AssetImage(AppConfig.mainLogo),
              fit: BoxFit.cover,
            ),
          ),
        ),
        Text(
          AppConfig.appName,
          style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
        )
      ],
    );
  }
}

///-------------------update button--------------------------------

class UpdateButton extends StatelessWidget {
  const UpdateButton({Key? key, required this.text, required this.onpressed}) : super(key: key);
  final String text;
  final VoidCallback onpressed;
  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onpressed,
      child: Container(
        alignment: Alignment.center,
        width: double.infinity,
        height: 48,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          color: kMainColor,
        ),
        child: Text(
          text,
          style: gTextStyle.copyWith(color: kWhite, fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ),
    );
  }
}
