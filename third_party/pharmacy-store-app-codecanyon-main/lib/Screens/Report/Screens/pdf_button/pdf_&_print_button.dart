import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class PdfButton extends StatelessWidget {
  final VoidCallback onPressed;
  const PdfButton({super.key, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    return IconButton(
        padding: EdgeInsets.zero,
        visualDensity: VisualDensity(horizontal: -4, vertical: -4),
        onPressed: onPressed,
        icon: Image.asset(
          'assets/logo/pdf.png',
          height: 25,
          width: 25,
        ));
  }
}

///---------------------pdf button--------------------------

class PrintButton extends StatelessWidget {
  const PrintButton({super.key, required this.onPressed});
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 10, left: 5),
      child: IconButton(
          padding: EdgeInsets.zero,
          visualDensity: VisualDensity(horizontal: -4, vertical: -4),
          onPressed: onPressed,
          icon: SvgPicture.asset(
            'assets/logo/print.svg',
            height: 25,
            width: 25,
          )),
    );
  }
}
