import 'package:flutter/material.dart';
import 'package:mobile_pos/constant.dart';

abstract class AcnooTheme {
  static const _fontFamily = 'Poppins';
  static ThemeData kLightTheme(BuildContext context) {
    final mainTheme = ThemeData.light();
    final textTheme = _getTextTheme(mainTheme.textTheme);
    return mainTheme.copyWith(
      textTheme: textTheme,
      scaffoldBackgroundColor: Colors.transparent,
      snackBarTheme: _getSnackBarTheme(),
      dropdownMenuTheme: DropdownMenuThemeData(
        textStyle: TextStyle(color: kTitleColor),
        inputDecorationTheme: InputDecorationTheme(
          iconColor: kGreyTextColor,
          contentPadding: const EdgeInsets.only(left: 10.0, right: 7.0),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        suffixIconColor: kGreyTextColor,
        iconColor: kGreyTextColor,
        floatingLabelBehavior: FloatingLabelBehavior.always,
        focusColor: kMainColor,
        outlineBorder: const BorderSide(color: Color(0xFFD7D9DE), width: 1.0),
        hintStyle: const TextStyle(color: kNutral700, fontSize: 14.0, fontWeight: FontWeight.normal),
        labelStyle: const TextStyle(color: kNutrals900, fontSize: 16.0, fontWeight: FontWeight.normal),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6.0),
          borderSide: const BorderSide(color: kMainColor, width: 1.0),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6.0),
          borderSide: const BorderSide(color: Color(0xffD7D9DE), width: 1.0),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6.0),
          borderSide: const BorderSide(color: Color(0xFFb00020), width: 1.0),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6.0),
          borderSide: const BorderSide(color: Color(0xFFb00020), width: 1.0),
        ),
        contentPadding: const EdgeInsets.only(left: 10.0, right: 7.0),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6.0),
          borderSide: const BorderSide(color: Color(0xffD7D9DE), width: 1.0),
        ),
        filled: false,
        fillColor: Colors.white,
      ),
      colorScheme: const ColorScheme.light(surface: kWhite, primary: kMainColor, primaryContainer: kWhite, outline: kBorderColor),
      elevatedButtonTheme: _getElevatedButtonTheme(textTheme),
      outlinedButtonTheme: _getOutlineButtonTheme,
      pageTransitionsTheme: PageTransitionsTheme(
        builders: {
          TargetPlatform.android: CustomPageTransitionBuilder(),
          TargetPlatform.iOS: CustomPageTransitionBuilder(),
          // You can define transitions for other platforms if needed
        },
      ), dialogTheme: DialogThemeData(backgroundColor: Colors.white),
    );
  }

  //------------------Elevated Button Theme------------------//
  static const _buttonPadding = EdgeInsets.symmetric(
    horizontal: 24,
    vertical: 12,
  );
  //------------------snackbar theme------------------------
  static SnackBarThemeData _getSnackBarTheme() {
    return const SnackBarThemeData(
      backgroundColor: Color(0xff333333), // Change this to your desired color
      actionTextColor: Colors.white, // Change action button text color if needed
      contentTextStyle: TextStyle(color: Colors.white), // Change the toast message color
    );
  }

  static const _buttonDensity = VisualDensity.standard;
  static _getElevatedButtonTheme(TextTheme baseTextTheme) {
    return ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        padding: _buttonPadding,
        visualDensity: _buttonDensity,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
        backgroundColor: Colors.transparent,
        foregroundColor: kWhite,
        textStyle: baseTextTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w500, fontSize: 18, color: Colors.red),
      ),
    );
  }

  static final _getOutlineButtonTheme = OutlinedButtonThemeData(
    style: OutlinedButton.styleFrom(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(30),
      ),
      visualDensity: _buttonDensity,
      padding: _buttonPadding,
      side: const BorderSide(color: kMainColor),
      foregroundColor: kMainColor,
    ),
  );

  static TextTheme _getTextTheme(TextTheme baseTextTheme) {
    return baseTextTheme.copyWith(
      displayLarge: baseTextTheme.displayLarge?.copyWith(
        fontFamily: _fontFamily,
      ),
      displayMedium: baseTextTheme.displayMedium?.copyWith(
        fontFamily: _fontFamily,
      ),
      displaySmall: baseTextTheme.displaySmall?.copyWith(
        fontFamily: _fontFamily,
      ),
      headlineLarge: baseTextTheme.headlineLarge?.copyWith(
        fontFamily: _fontFamily,
      ),
      headlineMedium: baseTextTheme.headlineMedium?.copyWith(
        fontFamily: _fontFamily,
      ),
      headlineSmall: baseTextTheme.headlineSmall?.copyWith(
        fontFamily: _fontFamily,
      ),
      titleLarge: baseTextTheme.titleLarge?.copyWith(
        fontFamily: _fontFamily,
      ),
      titleMedium: baseTextTheme.titleMedium?.copyWith(
        fontFamily: _fontFamily,
      ),
      titleSmall: baseTextTheme.titleSmall?.copyWith(
        fontFamily: _fontFamily,
      ),
      bodyLarge: baseTextTheme.bodyLarge?.copyWith(
        fontFamily: _fontFamily,
      ),
      bodyMedium: baseTextTheme.bodyMedium?.copyWith(
        fontFamily: _fontFamily,
      ),
      bodySmall: baseTextTheme.bodySmall?.copyWith(fontFamily: _fontFamily, color: kNutral600),
      labelLarge: baseTextTheme.labelLarge?.copyWith(
        fontFamily: _fontFamily,
      ),
      labelMedium: baseTextTheme.labelMedium?.copyWith(
        fontFamily: _fontFamily,
      ),
      labelSmall: baseTextTheme.labelSmall?.copyWith(
        fontFamily: _fontFamily,
      ),
    );
  }
}

class CustomPageTransitionBuilder extends PageTransitionsBuilder {
  @override
  Widget buildTransitions<T>(
    PageRoute<T> route,
    BuildContext context,
    Animation<double> animation,
    Animation<double> secondaryAnimation,
    Widget child,
  ) {
    return FadeTransition(
      opacity: Tween<double>(begin: 0.0, end: 1.0).animate(CurvedAnimation(parent: animation, curve: Curves.easeInOut)),
      child: child,
    );
  }
}
