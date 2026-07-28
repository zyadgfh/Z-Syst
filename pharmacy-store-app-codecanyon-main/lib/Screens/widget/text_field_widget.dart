import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';

import '../../constant.dart';

abstract class CustomFieldStyles {
  static kSearchDecoration({
    String? hintText,
    IconAlignment iconAlignment = IconAlignment.end,
  }) {
    OutlineInputBorder searchBorder([
      Color? borderColor,
    ]) {
      return OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(
          color: borderColor ?? kBorderColor,
          width: 0.5,
        ),
      );
    }

    const icon = Icon(
      FeatherIcons.search,
      color: kSubTitleColor,
      size: 20,
    );

    return InputDecoration(
      filled: true,
      fillColor: Colors.white,
      hintText: hintText,
      suffixIcon: iconAlignment == IconAlignment.end ? icon : null,
      prefixIcon: iconAlignment == IconAlignment.start ? icon : null,
      enabledBorder: searchBorder(),
      focusedBorder: searchBorder(),
    );
  }

  static kUnderlined(
      BuildContext context, {
        String? hinText,
      }) {
    final theme = Theme.of(context);
    final border = UnderlineInputBorder(
      borderSide: BorderSide(color: theme.colorScheme.primary),
    );

    return InputDecoration(
      hintText: hinText,
      isCollapsed: true,
      contentPadding: const EdgeInsets.all(2),
      border: InputBorder.none,
      focusedBorder: border.copyWith(
        borderSide: BorderSide(color: theme.colorScheme.primary),
      ),
      enabledBorder: border.copyWith(
        borderSide: BorderSide(color: theme.colorScheme.outline),
      ),
      errorBorder: border.copyWith(
        borderSide: BorderSide(color: theme.colorScheme.error),
      ),
      focusedErrorBorder: border.copyWith(
        borderSide: BorderSide(color: theme.colorScheme.error),
      ),
    );
  }
}
