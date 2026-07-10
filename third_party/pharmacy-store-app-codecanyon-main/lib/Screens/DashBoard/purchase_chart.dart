import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'components.dart';
import 'model/dashboard_overview_model.dart';

class PurchaseSaleChart extends StatefulWidget {
  const PurchaseSaleChart({super.key, required this.model});

  final DashboardOverviewModel model;

  @override
  State<PurchaseSaleChart> createState() => _PurchaseSaleChartState();
}

class _PurchaseSaleChartState extends State<PurchaseSaleChart> {
  List<ChartData> chartData = [];

  @override
  void initState() {
    super.initState();
    getData(widget.model);
  }

  void getData(DashboardOverviewModel model) {
    chartData = [];
    for (int i = 0; i < model.data!.sales!.length; i++) {
      chartData.add(ChartData(
        model.data!.sales![i].date!,
        model.data!.sales![i].amount!.toDouble(),
        model.data!.purchases![i].amount!.toDouble(),
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final _theme = Theme.of(context);
    return Scaffold(
      body: Center(
        child: Container(
          color: Colors.white,
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: SizedBox(
              width: chartData.length * 50.0,
              child: Stack(
                alignment: Alignment.topRight,
                children: [
                  LineChart(
                    LineChartData(
                      maxY: _getMaxY(),
                      lineBarsData: _buildLineBars(),
                      lineTouchData: LineTouchData(
                        enabled: true,
                        touchTooltipData: LineTouchTooltipData(
                          // maxContentWidth: 240,
                          getTooltipItems: (touchedSpots) {
                            return touchedSpots.map((item) {
                              final _value = NumberFormat.compactCurrency(
                                decimalDigits: 4,
                                symbol: '',
                                locale: 'en',
                              ).format(item.bar.spots[item.spotIndex].y);

                              return LineTooltipItem(
                                "",
                                _theme.textTheme.bodySmall!,
                                textAlign: TextAlign.start,
                                children: [
                                  TextSpan(
                                    text: "●",
                                    style: TextStyle(color: item.barIndex == 0 ? Color(0xffFE8C34) : Color(0xff00987F)),
                                  ),
                                  TextSpan(
                                    text: "${item.barIndex == 0 ? 'Purchase' : 'Sale'}:",
                                    style: TextStyle(
                                      color: const Color(0xff667085),
                                    ),
                                  ),
                                  TextSpan(
                                    text: " $_value",
                                    style: TextStyle(
                                      color: const Color(0xff344054),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              );
                            }).toList();
                          },
                          tooltipRoundedRadius: 4,
                          getTooltipColor: (touchedSpot) {
                            return Colors.white;
                          },
                        ),
                      ),
                      titlesData: FlTitlesData(
                        show: true,
                        bottomTitles: AxisTitles(
                          sideTitles: SideTitles(
                            showTitles: true,
                            getTitlesWidget: (value, meta) {
                              return SingleChildScrollView(
                                scrollDirection: Axis.horizontal,
                                child: _getBottomTitles(value, meta),
                              );
                            },
                            reservedSize: 25,
                          ),
                        ),
                        rightTitles: AxisTitles(
                          sideTitles: SideTitles(
                            showTitles: false,
                            // getTitlesWidget: _getLeftTitles,
                            // reservedSize: _getLeftTitleReservedSize(),
                          ),
                        ),
                        topTitles: const AxisTitles(
                          sideTitles: SideTitles(showTitles: false),
                        ),
                        leftTitles: AxisTitles(
                          sideTitles: SideTitles(
                            showTitles: true,
                            getTitlesWidget: _getLeftTitles,
                            reservedSize: _getLeftTitleReservedSize(),
                          ),
                        ),
                      ),
                      borderData: FlBorderData(show: false),
                      gridData: FlGridData(
                        show: true,
                        drawVerticalLine: false,
                        drawHorizontalLine: true,
                        getDrawingHorizontalLine: (value) {
                          return const FlLine(
                            color: Color(0xffD1D5DB),
                            dashArray: [4, 4],
                            strokeWidth: 1,
                          );
                        },
                      ),
                    ),
                  ),
                  // Column(
                  //   children: [
                  //     SizedBox(),
                  //     const Spacer(),
                  //     Padding(
                  //       padding: const EdgeInsets.only(bottom: 42, right: 42, left: 35),
                  //       child: CustomPaint(
                  //         size: Size(chartData.length * 50.0 - _getLeftTitleReservedSize(), 0.1),
                  //         painter: DashedBarPainter(
                  //           barHeight: 1,
                  //           barColor: const Color(0xffD1D5DB),
                  //           dashWidth: 4,
                  //           dashSpace: 4,
                  //         ),
                  //       ),
                  //     ),
                  //   ],
                  // ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  double _getMaxY() {
    double maxY = 0;
    for (var data in chartData) {
      maxY = maxY > data.y ? maxY : data.y;
      maxY = maxY > data.y1 ? maxY : data.y1;
    }
    return maxY + 100;
  }

  double _getLeftTitleReservedSize() {
    double maxY = _getMaxY();
    if (maxY < 999) {
      return 32;
    } else if (maxY < 1000) {
      return 35;
    } else if (maxY < 10000) {
      return 42;
    } else {
      return 50;
    }
  }

  List<LineChartBarData> _buildLineBars() {
    return [
      // Purchase Line
      LineChartBarData(
        spots: chartData.asMap().entries.map((entry) {
          int index = entry.key;
          ChartData data = entry.value;
          return FlSpot(index.toDouble(), data.y);
        }).toList(),
        isCurved: true,
        color: Color(0xffFF7E5C),
        barWidth: 2,
        dotData: FlDotData(show: false),
        belowBarData: BarAreaData(
          show: true,
          applyCutOffY: true,
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            tileMode: TileMode.decal,
            colors: [
              Color(0xFF7E5C33).withValues(alpha: 0.02),
              Colors.white,
            ],
          ),
        ), // No area below the line
      ),

      // Sales Line
      LineChartBarData(
        spots: chartData.asMap().entries.map((entry) {
          int index = entry.key;
          ChartData data = entry.value;
          return FlSpot(index.toDouble(), data.y1);
        }).toList(),
        isCurved: true,
        barWidth: 2,
        color: kMainColor,
        belowBarData: BarAreaData(
          show: true,
          applyCutOffY: true,
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            // stops: const [100, 80],
            tileMode: TileMode.decal,
            colors: [
              Color(0xff00987F).withValues(alpha: 0.04),
              Colors.white,
            ],
          ),
        ),
        dotData: FlDotData(show: false),
      ),
    ];
  }

  Widget _getBottomTitles(double value, TitleMeta meta) {
    const style = TextStyle(
      color: Color(0xff4D4D4D),
      fontSize: 12,
    );

    String text = chartData[value.toInt()].x;

    return SideTitleWidget(
      meta: TitleMeta(
        min: meta.min,
        max: meta.max,
        parentAxisSize: meta.parentAxisSize,
        axisPosition: meta.axisPosition,
        appliedInterval: meta.appliedInterval,
        sideTitles: meta.sideTitles,
        formattedValue: meta.formattedValue,
        axisSide: meta.axisSide,
        rotationQuarterTurns: meta.rotationQuarterTurns,
      ),
      space: 8,
      child: Text(text, style: style),
    );
  }

  Widget _getLeftTitles(double value, TitleMeta meta) {
    double maxY = _getMaxY();
    if (value == maxY) {
      return const SizedBox.shrink();
    }

    return SideTitleWidget(
      meta: TitleMeta(
        min: meta.min,
        max: meta.max,
        parentAxisSize: meta.parentAxisSize,
        axisPosition: meta.axisPosition,
        appliedInterval: meta.appliedInterval,
        sideTitles: meta.sideTitles,
        formattedValue: meta.formattedValue,
        axisSide: meta.axisSide,
        rotationQuarterTurns: meta.rotationQuarterTurns,
      ),
      child: Text(
        value.toInt().toString(),
        style: const TextStyle(
          color: Colors.black,
          fontSize: 12,
        ),
      ),
    );
  }
}

///---------------------------------dash line-------------------------------

class DashedBarPainter extends CustomPainter {
  final double barHeight;
  final Color barColor;
  final double dashWidth;
  final double dashSpace;

  DashedBarPainter({
    required this.barHeight,
    required this.barColor,
    this.dashWidth = 4.0,
    this.dashSpace = 2.0,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = barColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = barHeight;

    final dashPath = Path();
    for (double i = 0; i < size.width; i += dashWidth + dashSpace) {
      dashPath.addRect(Rect.fromLTWH(i, 0, dashWidth, size.height));
    }
    canvas.drawPath(dashPath, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
