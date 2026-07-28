import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class EmptyListWidget extends StatelessWidget {
  const EmptyListWidget({
    super.key,
    required this.title,
  });
  final String title;

  @override
  Widget build(BuildContext context) {
    TextTheme textTheme = Theme.of(context).textTheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Padding(
          padding: const EdgeInsets.only(right: 40.0,top: 20),
          child: SvgPicture.asset(
            'assets/empty.svg',
            width: 269,
            height: 213,
            fit: BoxFit.contain,
            alignment: Alignment.center,
          ),
        ),
        const SizedBox(height: 8.0),
        Text(
          title,
          style: textTheme.bodySmall?.copyWith(fontSize: 20),
        )
      ],
    );
  }
}
