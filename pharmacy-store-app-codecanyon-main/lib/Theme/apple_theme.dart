import 'package:flutter/material.dart';

/// Apple design system for Z-Syst Flutter — ported 1:1 from
/// `public/assets/css/apple.css` + `design-system/MASTER.md`:
/// materials, hairlines, pill controls, tight tracking, spring motion,
/// and the iOS dark palette.
abstract class AppleDesign {
  // ── Palette ─────────────────────────────────────────────────────────
  static const bg = Color(0xFFF5F5F7);
  static const surface = Color(0xFFFFFFFF);
  static const text = Color(0xFF1D1D1F);
  static const text2 = Color(0xFF6E6E73);
  static const text3 = Color(0xFF86868B);
  static const hairline = Color(0x14000000); // rgba(0,0,0,.08)
  static const accent = Color(0xFF0071E3);
  static const accentPress = Color(0xFF0060C2);
  static const green = Color(0xFF34C759);
  static const red = Color(0xFFFF3B30);
  static const amber = Color(0xFFF59E0B);

  // Dark (iOS palette — surfaces rise, accent lifts)
  static const darkBg = Color(0xFF1C1C1E);
  static const darkSurface = Color(0xFF2C2C2E);
  static const darkSurface2 = Color(0xFF3A3A3C);
  static const darkText = Color(0xFFF5F5F7);
  static const darkAccent = Color(0xFF0A84FF);

  // ── Shape & motion ──────────────────────────────────────────────────
  static const radiusLg = 20.0;
  static const radiusMd = 14.0;
  static const radiusSm = 10.0;
  static const pill = 980.0;

  /// critically-damped spring ≈ response .35s, damping 1
  static const spring = Cubic(0.32, 0.72, 0.0, 1.0);

  static const displayTrack = -0.022; // em
  static const titleTrack = -0.014;

  static const fontStack = 'SFProText-Poppins';

  static ThemeData light() => _theme(Brightness.light);
  static ThemeData dark() => _theme(Brightness.dark);

  static ThemeData _theme(Brightness brightness) {
    final isDark = brightness == Brightness.dark;
    final scheme = ColorScheme.fromSeed(
      seedColor: isDark ? darkAccent : accent,
      brightness: brightness,
    ).copyWith(
      primary: isDark ? darkAccent : accent,
      secondary: isDark ? darkAccent : accent,
      surface: isDark ? darkSurface : surface,
      error: isDark ? red : red,
    );

    final base = isDark ? ThemeData.dark(useMaterial3: true) : ThemeData.light(useMaterial3: true);

    return base.copyWith(
      colorScheme: scheme,
      scaffoldBackgroundColor: isDark ? darkBg : bg,
      fontFamily: fontStack,
      pageTransitionsTheme: const PageTransitionsTheme(builders: {
        TargetPlatform.android: CupertinoPageTransitionsBuilder(),
        TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
      }),
      appBarTheme: AppBarTheme(
        backgroundColor: isDark ? darkBg : bg,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontFamily: fontStack,
          fontSize: 17,
          fontWeight: FontWeight.w600,
          letterSpacing: titleTrack,
          color: isDark ? darkText : text,
        ),
        iconTheme: IconThemeData(color: isDark ? darkText : text),
      ),
      cardTheme: CardThemeData(
        color: isDark ? darkSurface : surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          side: BorderSide(color: isDark ? Colors.white24 : hairline),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: isDark ? darkSurface : surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        hintStyle: TextStyle(color: text3),
        labelStyle: TextStyle(color: text2),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusSm),
          borderSide: BorderSide(color: isDark ? Colors.white24 : hairline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusSm),
          borderSide: BorderSide(color: isDark ? Colors.white24 : hairline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusSm),
          borderSide: BorderSide(color: isDark ? darkAccent : accent, width: 1.4),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: isDark ? darkAccent : accent,
          foregroundColor: Colors.white,
          shape: const StadiumBorder(),
          padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 14),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, letterSpacing: 0),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: isDark ? darkText : text,
          shape: const StadiumBorder(),
          side: BorderSide(color: isDark ? Colors.white24 : hairline),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: isDark ? darkAccent : accent,
          foregroundColor: Colors.white,
          shape: const StadiumBorder(),
          padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 14),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, letterSpacing: 0),
        ),
      ),
      dividerTheme: DividerThemeData(color: isDark ? Colors.white24 : hairline, thickness: 1, space: 1),
      listTileTheme: ListTileThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusSm)),
        iconColor: text2,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusMd)),
        backgroundColor: isDark ? darkSurface2 : text,
        contentTextStyle: TextStyle(color: isDark ? darkText : Colors.white),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: isDark ? darkSurface : surface,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radiusLg)),
        ),
        showDragHandle: true,
      ),
    );
  }
}
