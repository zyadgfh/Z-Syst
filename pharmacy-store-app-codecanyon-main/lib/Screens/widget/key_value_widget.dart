import 'package:flutter/material.dart';

import '../../constant.dart';

class KeyValueWidget extends StatelessWidget {
  const KeyValueWidget({super.key, required this.keys, required this.value});
  final String keys;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
            child: Text(
          keys,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kNutral700),
        )),
        Expanded(
          flex: 2,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                ':',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kNutral700),
              ),
              const SizedBox(
                width: 16,
              ),
              Flexible(
                child: Text(
                  value,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kNutrals900),
                ),
              )
            ],
          ),
        )
      ],
    );
  }
}

class KeyValueRow extends StatelessWidget {
  const KeyValueRow({
    super.key,
    required this.title,
    this.titleFlex = 1,
    this.titleStyle,
    this.titleMaxLines,
    this.titleOverflow,
    required this.description,
    this.descriptionFlex = 1,
    this.descriptionStyle,
    this.descriptionMaxLines,
    this.descriptionOverflow,
    this.centerSpace = 8,
    this.bottomSpace = 8,
  });

  final String title;
  final int titleFlex;
  final TextStyle? titleStyle;
  final int? titleMaxLines;
  final TextOverflow? titleOverflow;

  final String description;
  final int descriptionFlex;
  final TextStyle? descriptionStyle;
  final int? descriptionMaxLines;
  final TextOverflow? descriptionOverflow;

  final double centerSpace;
  final double bottomSpace;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    final resolvedTitleStyle = titleStyle ??
        theme.textTheme.bodyMedium?.copyWith(
          color: kNutral700,
        );

    final resolvedDescriptionStyle = descriptionStyle ??
        resolvedTitleStyle?.copyWith(color: kNutrals900);

    return Padding(
      padding: EdgeInsets.only(bottom: bottomSpace),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Flexible(
            flex: titleFlex,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Flexible(
                  child: Text(
                    title,
                    maxLines: titleMaxLines,
                    overflow: titleOverflow,
                    style: resolvedTitleStyle,
                  ),
                ),
                Text(':', style: resolvedTitleStyle),
              ],
            ),
          ),
          SizedBox(width: centerSpace),
          Expanded(
            flex: descriptionFlex,
            child: Text(
              description,
              maxLines: descriptionMaxLines,
              overflow: descriptionOverflow,
              style: resolvedDescriptionStyle,
            ),
          )
        ],
      ),
    );
  }
}
