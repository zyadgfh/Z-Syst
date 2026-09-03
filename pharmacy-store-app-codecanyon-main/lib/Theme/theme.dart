import 'package:flutter/material.dart';
import 'package:mobile_pos/constant.dart';
import 'package:google_fonts/google_fonts.dart';

abstract class AcnooTheme {
  static const _fontFamily = 'Outfit';

  // z-syst-pharmacy-mobile Design System Colors
  static const Color primaryColor = Color(0xFF15803D);
  static const Color onPrimaryColor = Color(0xFFFFFFFF);
  static const Color secondaryColor = Color(0xFF22C55E);
  static const Color accentColor = Color(0xFF0369A1);
  static const Color backgroundColor = Color(0xFFF0FDF4);
  static const Color foregroundColor = Color(0xFF14532D);
  static const Color mutedColor = Color(0xFFE8F0F1);
  static const Color borderColor = Color(0xFFBBF7D0);
  static const Color destructiveColor = Color(0xFFDC2626);
  static const Color ringColor = Color(0xFF15803D);

  // Dark Mode Colors
  static const Color primaryColorDark = Color(0xFF22C55E);
  static const Color onPrimaryColorDark = Color(0xFFFFFFFF);
  static const Color backgroundColorDark = Color(0xFF0F172A);
  static const Color foregroundColorDark = Color(0xFFF0FDF4);
  static const Color mutedColorDark = Color(0xFF1E293B);
  static const Color borderColorDark = Color(0xFF334155);
  static const Color cardColorDark = Color(0xFF1E293B);

  // Spacing - Density 6/10 (Standard)
  static const double spaceXs = 4.0;
  static const double spaceSm = 8.0;
  static const double spaceMd = 16.0;
  static const double spaceLg = 24.0;
  static const double spaceXl = 32.0;
  static const double space2xl = 48.0;
  static const double space3xl = 64.0;

  // Border Radius
  static const double radiusSm = 4.0;
  static const double radiusMd = 8.0;
  static const double radiusLg = 12.0;
  static const double radiusXl = 16.0;

  // Shadows
  static const BoxShadow shadowSm = BoxShadow(
    color: Color(0x0D000000),
    offset: Offset(0, 1),
    blurRadius: 2,
  );
  static const BoxShadow shadowMd = BoxShadow(
    color: Color(0x1A000000),
    offset: Offset(0, 4),
    blurRadius: 6,
  );
  static const BoxShadow shadowLg = BoxShadow(
    color: Color(0x1A000000),
    offset: Offset(0, 10),
    blurRadius: 15,
  );
  static const BoxShadow shadowXl = BoxShadow(
    color: Color(0x26000000),
    offset: Offset(0, 20),
    blurRadius: 25,
  );

  static ThemeData kLightTheme(BuildContext context) {
    final textTheme = _getTextTheme(ThemeData.light().textTheme);
    return ThemeData.light().copyWith(
      textTheme: textTheme,
      scaffoldBackgroundColor: backgroundColor,
      snackBarTheme: _getSnackBarTheme(),
      dropdownMenuTheme: DropdownMenuThemeData(
        textStyle: TextStyle(color: foregroundColor),
        inputDecorationTheme: InputDecorationTheme(
          iconColor: mutedColor,
          contentPadding: const EdgeInsets.symmetric(horizontal: spaceMd, vertical: spaceSm),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        suffixIconColor: mutedColor,
        iconColor: mutedColor,
        floatingLabelBehavior: FloatingLabelBehavior.always,
        focusColor: primaryColor,
        contentPadding: const EdgeInsets.symmetric(horizontal: spaceMd, vertical: spaceSm),
        hintStyle: const TextStyle(color: Color(0xFF404040), fontSize: 14.0, fontWeight: FontWeight.normal),
        labelStyle: const TextStyle(color: Color(0xFF131313), fontSize: 16.0, fontWeight: FontWeight.normal),
        filled: true,
        fillColor: backgroundColor,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: borderColor, width: 1.0),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: borderColor, width: 1.0),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: primaryColor, width: 2.0),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: destructiveColor, width: 1.0),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: destructiveColor, width: 2.0),
        ),
      ),
      colorScheme: const ColorScheme.light(
        primary: primaryColor,
        onPrimary: onPrimaryColor,
        secondary: secondaryColor,
        tertiary: accentColor,
        surface: Color(0xFFF0FDF4),
        background: backgroundColor,
        error: destructiveColor,
        onError: Color(0xFFFFFFFF),
        outline: borderColor,
      ),
      elevatedButtonTheme: _getElevatedButtonTheme(textTheme),
      outlinedButtonTheme: _getOutlinedButtonTheme(textTheme),
      textButtonTheme: _getTextButtonTheme(textTheme),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          side: const BorderSide(color: borderColor),
        ),
        shadowColor: shadowMd.color,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusXl),
        ),
        titleTextStyle: textTheme.titleLarge?.copyWith(color: foregroundColor),
        contentTextStyle: textTheme.bodyMedium?.copyWith(color: foregroundColor),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radiusXl)),
        ),
      ),
      dividerTheme: DividerThemeData(
        color: borderColor,
        thickness: 1,
        space: spaceMd,
      ),
      chipTheme: ChipThemeData(
        backgroundColor: mutedColor,
        selectedColor: primaryColor.withValues(alpha: 0.1),
        labelStyle: textTheme.labelMedium?.copyWith(color: foregroundColor),
        secondaryLabelStyle: textTheme.labelMedium?.copyWith(color: onPrimaryColor),
        padding: const EdgeInsets.symmetric(horizontal: spaceMd, vertical: spaceSm),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          side: const BorderSide(color: borderColor),
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: foregroundColor,
        elevation: 0,
        scrolledUnderElevation: 1,
        shadowColor: shadowSm.color,
        surfaceTintColor: Colors.white,
        titleTextStyle: textTheme.titleLarge?.copyWith(
          color: foregroundColor,
          fontWeight: FontWeight.w700,
        ),
      ),
      tabBarTheme: TabBarThemeData(
        labelColor: primaryColor,
        unselectedLabelColor: mutedColor,
        indicatorColor: primaryColor,
        indicatorSize: TabBarIndicatorSize.label,
        labelStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
        unselectedLabelStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w500),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        indicatorColor: primaryColor.withValues(alpha: 0.1),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return textTheme.labelSmall?.copyWith(
              color: primaryColor,
              fontWeight: FontWeight.w700,
            );
          }
          return textTheme.labelSmall?.copyWith(
            color: mutedColor,
            fontWeight: FontWeight.w500,
          );
        }),
        height: 72,
      ),
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: ZoomPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
        },
      ),
    );
  }

  static ThemeData kDarkTheme(BuildContext context) {
    final textTheme = _getTextTheme(ThemeData.dark().textTheme);
    return ThemeData.dark().copyWith(
      textTheme: textTheme,
      scaffoldBackgroundColor: backgroundColorDark,
      snackBarTheme: _getSnackBarTheme(isDark: true),
      dropdownMenuTheme: DropdownMenuThemeData(
        textStyle: TextStyle(color: foregroundColorDark),
        inputDecorationTheme: InputDecorationTheme(
          iconColor: mutedColorDark,
          contentPadding: const EdgeInsets.symmetric(horizontal: spaceMd, vertical: spaceSm),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        suffixIconColor: mutedColorDark,
        iconColor: mutedColorDark,
        floatingLabelBehavior: FloatingLabelBehavior.always,
        focusColor: primaryColorDark,
        contentPadding: const EdgeInsets.symmetric(horizontal: spaceMd, vertical: spaceSm),
        hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14.0, fontWeight: FontWeight.normal),
        labelStyle: const TextStyle(color: Color(0xFFF0FDF4), fontSize: 16.0, fontWeight: FontWeight.normal),
        filled: true,
        fillColor: mutedColorDark,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: borderColorDark, width: 1.0),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: borderColorDark, width: 1.0),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: primaryColorDark, width: 2.0),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: destructiveColor, width: 1.0),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: destructiveColor, width: 2.0),
        ),
      ),
      colorScheme: const ColorScheme.dark(
        primary: primaryColorDark,
        onPrimary: onPrimaryColorDark,
        secondary: secondaryColor,
        tertiary: accentColor,
        surface: cardColorDark,
        background: backgroundColorDark,
        error: destructiveColor,
        onError: Color(0xFFFFFFFF),
        outline: borderColorDark,
      ),
      elevatedButtonTheme: _getElevatedButtonTheme(textTheme, isDark: true),
      outlinedButtonTheme: _getOutlinedButtonTheme(textTheme, isDark: true),
      textButtonTheme: _getTextButtonTheme(textTheme, isDark: true),
      cardTheme: CardThemeData(
        color: cardColorDark,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          side: const BorderSide(color: borderColorDark),
        ),
        shadowColor: shadowMd.color,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: cardColorDark,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusXl),
        ),
        titleTextStyle: textTheme.titleLarge?.copyWith(color: foregroundColorDark),
        contentTextStyle: textTheme.bodyMedium?.copyWith(color: foregroundColorDark),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: cardColorDark,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radiusXl)),
        ),
      ),
      dividerTheme: DividerThemeData(
        color: borderColorDark,
        thickness: 1,
        space: spaceMd,
      ),
      chipTheme: ChipThemeData(
        backgroundColor: mutedColorDark,
        selectedColor: primaryColorDark.withValues(alpha: 0.2),
        labelStyle: textTheme.labelMedium?.copyWith(color: foregroundColorDark),
        secondaryLabelStyle: textTheme.labelMedium?.copyWith(color: onPrimaryColorDark),
        padding: const EdgeInsets.symmetric(horizontal: spaceMd, vertical: spaceSm),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          side: const BorderSide(color: borderColorDark),
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: cardColorDark,
        foregroundColor: foregroundColorDark,
        elevation: 0,
        scrolledUnderElevation: 1,
        shadowColor: shadowSm.color,
        surfaceTintColor: cardColorDark,
        titleTextStyle: textTheme.titleLarge?.copyWith(
          color: foregroundColorDark,
          fontWeight: FontWeight.w700,
        ),
      ),
      tabBarTheme: TabBarThemeData(
        labelColor: primaryColorDark,
        unselectedLabelColor: mutedColorDark,
        indicatorColor: primaryColorDark,
        indicatorSize: TabBarIndicatorSize.label,
        labelStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
        unselectedLabelStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w500),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: cardColorDark,
        indicatorColor: primaryColorDark.withValues(alpha: 0.2),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return textTheme.labelSmall?.copyWith(
              color: primaryColorDark,
              fontWeight: FontWeight.w700,
            );
          }
          return textTheme.labelSmall?.copyWith(
            color: mutedColorDark,
            fontWeight: FontWeight.w500,
          );
        }),
        height: 72,
      ),
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: ZoomPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
        },
      ),
    );
  }

  static SnackBarThemeData _getSnackBarTheme({bool isDark = false}) {
    final bgColor = isDark ? cardColorDark : const Color(0xFF333333);
    return SnackBarThemeData(
      backgroundColor: bgColor,
      actionTextColor: Colors.white,
      contentTextStyle: TextStyle(color: isDark ? foregroundColorDark : Colors.white),
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(radiusMd),
      ),
      elevation: 4,
    );
  }

  static const _buttonPadding = EdgeInsets.symmetric(
    horizontal: 24,
    vertical: 14,
  );

  static const _buttonDensity = VisualDensity.standard;

  static ElevatedButtonThemeData _getElevatedButtonTheme(TextTheme baseTextTheme, {bool isDark = false}) {
    final primary = isDark ? primaryColorDark : primaryColor;
    final onPrimary = isDark ? onPrimaryColorDark : onPrimaryColor;
    
    return ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        padding: _buttonPadding,
        visualDensity: _buttonDensity,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
        ),
        backgroundColor: primary,
        foregroundColor: onPrimary,
        textStyle: baseTextTheme.labelLarge?.copyWith(
          fontWeight: FontWeight.w600,
          fontSize: 16,
        ),
        elevation: 0,
        shadowColor: Colors.transparent,
        overlayColor: onPrimary.withValues(alpha: 0.1),
      ),
    );
  }

  static OutlinedButtonThemeData _getOutlinedButtonTheme(TextTheme baseTextTheme, {bool isDark = false}) {
    final primary = isDark ? primaryColorDark : primaryColor;
    final onPrimary = isDark ? onPrimaryColorDark : onPrimaryColor;
    final border = isDark ? borderColorDark : borderColor;
    
    return OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        padding: _buttonPadding,
        visualDensity: _buttonDensity,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
        ),
        foregroundColor: primary,
        side: BorderSide(color: primary, width: 2),
        textStyle: baseTextTheme.labelLarge?.copyWith(
          fontWeight: FontWeight.w600,
          fontSize: 16,
        ),
        backgroundColor: Colors.transparent,
        overlayColor: primary.withValues(alpha: 0.1),
      ),
    );
  }

  static TextButtonThemeData _getTextButtonTheme(TextTheme baseTextTheme, {bool isDark = false}) {
    final primary = isDark ? primaryColorDark : primaryColor;
    return TextButtonThemeData(
      style: TextButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: spaceMd, vertical: spaceSm),
        visualDensity: _buttonDensity,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
        ),
        foregroundColor: primary,
        textStyle: baseTextTheme.labelLarge?.copyWith(
          fontWeight: FontWeight.w600,
          fontSize: 16,
        ),
        overlayColor: primary.withValues(alpha: 0.1),
      ),
    );
  }

  static TextTheme _getTextTheme(TextTheme baseTextTheme) {
    return GoogleFonts.outfitTextTheme(baseTextTheme).copyWith(
      displayLarge: GoogleFonts.outfit(
        fontSize: 57,
        fontWeight: FontWeight.w700,
        letterSpacing: -0.25,
        height: 1.12,
      ),
      displayMedium: GoogleFonts.outfit(
        fontSize: 45,
        fontWeight: FontWeight.w700,
        letterSpacing: 0,
        height: 1.16,
      ),
      displaySmall: GoogleFonts.outfit(
        fontSize: 36,
        fontWeight: FontWeight.w700,
        letterSpacing: 0,
        height: 1.22,
      ),
      headlineLarge: GoogleFonts.outfit(
        fontSize: 32,
        fontWeight: FontWeight.w700,
        letterSpacing: 0,
        height: 1.25,
      ),
      headlineMedium: GoogleFonts.outfit(
        fontSize: 28,
        fontWeight: FontWeight.w700,
        letterSpacing: 0,
        height: 1.29,
      ),
      headlineSmall: GoogleFonts.outfit(
        fontSize: 24,
        fontWeight: FontWeight.w700,
        letterSpacing: 0,
        height: 1.33,
      ),
      titleLarge: GoogleFonts.outfit(
        fontSize: 22,
        fontWeight: FontWeight.w700,
        letterSpacing: 0,
        height: 1.27,
      ),
      titleMedium: GoogleFonts.outfit(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.15,
        height: 1.5,
      ),
      titleSmall: GoogleFonts.outfit(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.1,
        height: 1.43,
      ),
      bodyLarge: GoogleFonts.outfit(
        fontSize: 16,
        fontWeight: FontWeight.w400,
        letterSpacing: 0.5,
        height: 1.5,
      ),
      bodyMedium: GoogleFonts.outfit(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        letterSpacing: 0.25,
        height: 1.43,
      ),
      bodySmall: GoogleFonts.outfit(
        fontSize: 12,
        fontWeight: FontWeight.w400,
        letterSpacing: 0.4,
        height: 1.33,
        color: const Color(0xFF404040),
      ),
      labelLarge: GoogleFonts.outfit(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.1,
        height: 1.43,
      ),
      labelMedium: GoogleFonts.outfit(
        fontSize: 12,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.5,
        height: 1.33,
      ),
      labelSmall: GoogleFonts.outfit(
        fontSize: 11,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.5,
        height: 1.45,
      ),
    );
  }
}