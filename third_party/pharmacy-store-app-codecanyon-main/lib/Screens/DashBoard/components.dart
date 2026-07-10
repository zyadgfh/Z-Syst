import 'package:flutter/material.dart';
import '../../constant.dart';

// Chart Data
class ChartData {
  ChartData(this.x, this.y, this.y1);

  final String x;
  final double y;
  final double y1;
}

// Dashboard Container
class DashBordContainerWidget extends StatelessWidget {
  final String title;
  final String subtitle;
  final Color? color;
  final LinearGradient? gradient;
  final TextStyle? subTitleStyle;
  const DashBordContainerWidget({super.key, required this.title, required this.subtitle, this.color, this.gradient, this.subTitleStyle});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 80,
      width: double.infinity,
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(8), color: color, gradient: gradient),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 18),
          ),
          Text(
            subtitle,maxLines: 1,overflow: TextOverflow.ellipsis,
            style: subTitleStyle ?? Theme.of(context).textTheme.bodyMedium?.copyWith(color: kNutral700),
          )
        ],
      ),
    );
  }
}
