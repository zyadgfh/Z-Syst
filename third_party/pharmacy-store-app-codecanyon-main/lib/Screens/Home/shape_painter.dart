import 'package:flutter/material.dart';

class RPSCustomPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    Path path_0 = Path();
    path_0.moveTo(size.width * 0.5616570, size.height * 0.09958559);
    path_0.cubicTo(size.width * 0.5715628, size.height * 0.1389495, size.width * 0.5843068, size.height * 0.1711712, size.width * 0.5987802, size.height * 0.1711712);
    path_0.lineTo(size.width * 0.9975845, size.height * 0.1711712);
    path_0.cubicTo(size.width * 0.9989179, size.height * 0.1711712, size.width, size.height * 0.1752045, size.width, size.height * 0.1801802);
    path_0.lineTo(size.width, size.height * 0.9819820);
    path_0.cubicTo(size.width, size.height * 0.9919369, size.width * 0.9978382, size.height, size.width * 0.9951691, size.height);
    path_0.lineTo(size.width * 0.007246377, size.height);
    path_0.cubicTo(size.width * 0.003244324, size.height, 0, size.height * 0.9879009, 0, size.height * 0.9729730);
    path_0.lineTo(0, size.height * 0.1801802);
    path_0.cubicTo(0, size.height * 0.1752045, size.width * 0.001081437, size.height * 0.1711712, size.width * 0.002415459, size.height * 0.1711712);
    path_0.lineTo(size.width * 0.4012198, size.height * 0.1711712);
    path_0.cubicTo(size.width * 0.4156932, size.height * 0.1711712, size.width * 0.4284372, size.height * 0.1389495, size.width * 0.4383430, size.height * 0.09958559);
    path_0.cubicTo(size.width * 0.4537657, size.height * 0.03828991, size.width * 0.4756836, 0, size.width * 0.5000000, 0);
    path_0.cubicTo(size.width * 0.5243164, 0, size.width * 0.5462343, size.height * 0.03828991, size.width * 0.5616570, size.height * 0.09958559);
    path_0.close();

    // Shadow effect
    Paint shadowPaint = Paint()
      ..color = Colors.black.withValues(alpha: 0.04) // Shadow color
      ..maskFilter = MaskFilter.blur(BlurStyle.normal, 10); // Apply blur for shadow

    // Draw shadow first (slightly offset to simulate shadow above the path)
    canvas.save();
    canvas.translate(0, -5); // Moving path slightly up for the shadow
    canvas.drawPath(path_0, shadowPaint);
    canvas.restore();

    // Original fill paint
    Paint paint_0_fill = Paint()..style = PaintingStyle.fill;
    paint_0_fill.color = Colors.white;
    canvas.drawPath(path_0, paint_0_fill);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) {
    return true;
  }
}
