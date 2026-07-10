import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';

import '../../constant.dart';

abstract class CustomFieldStyles {
  static kSearchDecoration({
    String? hintText,
    IconAlignment iconAlignment = IconAlignment.end,
  }) {
    OutlineInputBorder _searchBorder([
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

    const _icon = Icon(
      FeatherIcons.search,
      color: kSubTitleColor,
      size: 20,
    );

    return InputDecoration(
      filled: true,
      fillColor: Colors.white,
      hintText: hintText,
      suffixIcon: iconAlignment == IconAlignment.end ? _icon : null,
      prefixIcon: iconAlignment == IconAlignment.start ? _icon : null,
      enabledBorder: _searchBorder(),
      focusedBorder: _searchBorder(),
    );
  }

  static kUnderlined(
      BuildContext context, {
        String? hinText,
      }) {
    final _theme = Theme.of(context);
    final _border = UnderlineInputBorder(
      borderSide: BorderSide(color: _theme.colorScheme.primary),
    );

    return InputDecoration(
      hintText: hinText,
      isCollapsed: true,
      contentPadding: const EdgeInsets.all(2),
      border: InputBorder.none,
      focusedBorder: _border.copyWith(
        borderSide: BorderSide(color: _theme.colorScheme.primary),
      ),
      enabledBorder: _border.copyWith(
        borderSide: BorderSide(color: _theme.colorScheme.outline),
      ),
      errorBorder: _border.copyWith(
        borderSide: BorderSide(color: _theme.colorScheme.error),
      ),
      focusedErrorBorder: _border.copyWith(
        borderSide: BorderSide(color: _theme.colorScheme.error),
      ),
    );
  }
}