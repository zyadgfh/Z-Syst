import 'package:flutter/material.dart';
import 'model/prediction_model.dart';
import 'repo/prediction_repo.dart';

class PredictionReportScreen extends StatefulWidget {
  const PredictionReportScreen({super.key});

  @override
  State<PredictionReportScreen> createState() => _PredictionReportScreenState();
}

class _PredictionReportScreenState extends State<PredictionReportScreen> {
  final PredictionRepo _repo = PredictionRepo();
  DemandReport? _report;
  bool _loading = true;
  String _period = 'daily';
  bool _forecasting = false;

  @override
  void initState() {
    super.initState();
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() => _loading = true);
    final report = await _repo.getDemandReport(period: _period);
    if (mounted) {
      setState(() {
        _report = report;
        _loading = false;
      });
    }
  }

  Future<void> _forecastAll() async {
    setState(() => _forecasting = true);
    await _repo.forecastAll();
    if (mounted) {
      setState(() => _forecasting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('تم تحديث جميع التنبؤات'),
          backgroundColor: Colors.green,
        ),
      );
      _loadReport();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('تقرير الطلب المتوقع'),
        actions: [
          if (_forecasting)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(16.0),
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                ),
              ),
            )
          else
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: _forecastAll,
              tooltip: 'تحديث التنبؤات',
            ),
        ],
      ),
      body: Column(
        children: [
          // Period selector
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: Colors.grey[100],
            child: Row(
              children: [
                const Text('الفترة: ', style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(width: 8),
                _buildPeriodChip('daily', 'يومي'),
                const SizedBox(width: 4),
                _buildPeriodChip('weekly', 'أسبوعي'),
                const SizedBox(width: 4),
                _buildPeriodChip('monthly', 'شهري'),
                const Spacer(),
                if (_report != null)
                  Text(
                    '${_report!.productsCount ?? 0} منتج',
                    style: const TextStyle(color: Colors.grey, fontSize: 12),
                  ),
              ],
            ),
          ),
          // Content
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _report == null || (_report!.products?.isEmpty ?? true)
                    ? const Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.bar_chart, size: 64, color: Colors.grey),
                            SizedBox(height: 16),
                            Text(
                              'لا توجد بيانات تنبؤ متاحة',
                              style: TextStyle(color: Colors.grey, fontSize: 16),
                            ),
                            SizedBox(height: 8),
                            Text(
                              'اضغط على زر التحديث لإنشاء التنبؤات',
                              style: TextStyle(color: Colors.grey, fontSize: 12),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadReport,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(8),
                          itemCount: _report!.products!.length,
                          itemBuilder: (context, index) {
                            final item = _report!.products![index];
                            return _buildProductCard(item);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildPeriodChip(String value, String label) {
    final selected = _period == value;
    return GestureDetector(
      onTap: () {
        setState(() => _period = value);
        _loadReport();
      },
      child: Chip(
        label: Text(label, style: const TextStyle(fontSize: 12)),
        backgroundColor: selected ? const Color(0xFF1A237E) : Colors.white,
        labelStyle: TextStyle(color: selected ? Colors.white : Colors.black),
        side: BorderSide(
          color: selected ? const Color(0xFF1A237E) : Colors.grey[300]!,
        ),
      ),
    );
  }

  Widget _buildProductCard(DemandReportItem item) {
    final needsReorder = item.needsReorder ?? false;
    final stockCoverage = item.stockCoverageDays ?? 999;

    Color coverageColor;
    if (stockCoverage <= 7) {
      coverageColor = Colors.red;
    } else if (stockCoverage <= 30) {
      coverageColor = Colors.orange;
    } else if (stockCoverage <= 90) {
      coverageColor = Colors.amber;
    } else {
      coverageColor = Colors.green;
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    item.productName ?? 'منتج',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                ),
                if (needsReorder)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.red[50],
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.red[200]!),
                    ),
                    child: const Text(
                      'يحتاج طلب',
                      style: TextStyle(color: Colors.red, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                _buildInfoBox('المخزون الحالي', item.currentStock?.toStringAsFixed(0) ?? '0', Colors.blue),
                const SizedBox(width: 8),
                _buildInfoBox('الطلب المتوقع', item.totalPredictedDemand?.toStringAsFixed(0) ?? '0', Colors.purple),
                const SizedBox(width: 8),
                _buildInfoBox('الثقة', '${item.averageConfidence?.toStringAsFixed(0) ?? '?'}%', Colors.teal),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.hourglass_bottom, size: 16, color: coverageColor),
                const SizedBox(width: 4),
                Text(
                  'تغطية المخزون: ${stockCoverage == 999 ? "∞" : stockCoverage.toStringAsFixed(0)} يوم',
                  style: TextStyle(color: coverageColor, fontSize: 12, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoBox(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          children: [
            Text(
              value,
              style: TextStyle(
                color: color,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(color: color, fontSize: 10),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

