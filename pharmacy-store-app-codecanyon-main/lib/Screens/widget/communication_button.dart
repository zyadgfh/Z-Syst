import 'package:flutter/material.dart';

import '../../constant.dart';

class CommunicationButton extends StatelessWidget {
  const CommunicationButton({super.key, required this.onTap, required this.icon, required this.title});
  final VoidCallback onTap;
  final Widget icon;
  final String title;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 78,
        decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(5),
            color: kMainColorBg
        ),
        child:  Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            icon,
            const SizedBox(height: 6,),
            Text(title)
          ],
        ),
      ),
    );
  }
}
