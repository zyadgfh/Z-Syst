import 'package:flutter/material.dart';
import 'model/inventory_turnover_model.dart';
import 'repo/prediction_repo.dart';

class InventoryTurnoverScreen extends StatefulWidget {
  const InventoryTurnoverScreen({super.key});

  @override
  State<InventoryTurnoverScreen> createState() =>
      _InventoryTurnoverScreenState();
}

class _InventoryTurnoverScreenState extends State<InventoryTurnoverScreen>
    with SingleTickerProviderStateMixin {
  final PredictionRepo _repo = PredictionRepo();
  late TabController _tabController;

  InventoryTurnoverSummary? _summary;
  ProductAnalysisResponse? _productAnalysis;
  SlowMovingResult? _slowMoving;
  AbcAnalysisResult? _abcAnalysis;
  Map<String, dynamic>? _trends;

  bool _loadingSummary = true;
  bool _loadingProducts = true;
  bool _loadingSlowMoving = true;
  bool _loadingAbc = true;
  bool _loadingTrends = true;

  String _productMovementFilter = '';
  String _productSortBy = 'turnover_ratio';
  String _productSortDir = 'asc';
  String _reportType = 'monthly';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _loadAllData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadAllData() async {
    await Future.wait([
      _loadSummary(),
      _loadProducts(),
      _loadSlowMoving(),
      _loadAbcAnalysis(),
      _loadTrends(),
    ]);
  }

  Future<void> _loadSummary() async {
    setState(() => _loadingSummary = true);
    final summary = await _repo.getInventoryTurnoverSummary();
    if (mounted) {
      setState(() {
        _summary = summary;
        _loadingSummary = false;
      });
    }
  }

  Future<void> _loadProducts() async {
    setState(() => _loadingProducts = true);
    final products = await _repo.getProductAnalysis(
      movement:
          _productMovementFilter.isNotEmpty ? _productMovementFilter : null,
      sortBy: _productSortBy,
      sortDir: _productSortDir,
      reportType: _reportType,
    );
    if (mounted) {
      setState(() {
        _productAnalysis = products;
        _loadingProducts = false;
      });
    }
  }

  Future<void> _loadSlowMoving() async {
    setState(() => _loadingSlowMoving = true);
    final slow = await _repo.getSlowMovingProducts(category: 'all');
    if (mounted) {
      setState(() {
        _slowMoving = slow;
        _loadingSlowMoving = false;
      });
    }
  }

  Future<void> _loadAbcAnalysis() async {
    setState(() => _loadingAbc = true);
    final abc = await _repo.getAbcAnalysis(period: _reportType);
    if (mounted) {
      setState(() {
        _abcAnalysis = abc;
        _loadingAbc = false;
      });
    }
  }

  Future<void> _loadTrends() async {
    setState(() => _loadingTrends = true);
    final trends = await _repo.getTurnoverTrends(reportType: _reportType);
    if (mounted) {
      setState(() {
        _trends = trends;
        _loadingTrends = false;
      });
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('تحليل دوران المخزون'),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.date_range),
            onSelected: (value) {
              setState(() => _reportType = value);
              _loadAllData();
            },
            itemBuilder: (context) => [
              const PopupMenuItem(
                  value: 'monthly', child: Text('شهري')),
              const PopupMenuItem(
                  value: 'quarterly', child: Text('ربع سنوي')),
              const PopupMenuItem(
                  value: 'yearly', child: Text('سنوي')),
            ],
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabs: const [
            Tab(text: 'الملخص', icon: Icon(Icons.dashboard, size: 18)),
            Tab(text: 'المنتجات', icon: Icon(Icons.inventory_2, size: 18)),
            Tab(
                text: 'البطيء والراكد',
                icon: Icon(Icons.warning_amber, size: 18)),
            Tab(text: 'تحليل ABC', icon: Icon(Icons.pie_chart, size: 18)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildSummaryTab(),
          _buildProductsTab(),
          _buildSlowMovingTab(),
          _buildAbcTab(),
        ],
      ),
    );
  }

  // ========== Summary Tab ==========

  Widget _buildSummaryTab() {
    if (_loadingSummary) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_summary == null) {
      return _buildErrorWidget('لا توجد بيانات متاحة', () => _loadSummary());
    }

    return RefreshIndicator(
      onRefresh: _loadSummary,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Row(
            children: [
              Expanded(
                child: _buildMetricCard(
                  'إجمالي المخزون',
                  '${_summary!.totalInventoryValue?.toStringAsFixed(0) ?? '0'} ج.م',
                  Icons.inventory,
                  Colors.blue,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildMetricCard(
                  'عدد المنتجات',
                  '${_summary!.totalProducts ?? 0}',
                  Icons.production_quantity_limits,
                  Colors.green,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _buildMetricCard(
                  'كمية المخزون',
                  _summary!.totalStockQty?.toStringAsFixed(0) ?? '0',
                  Icons.numbers,
                  Colors.orange,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildMetricCard(
                  'آخر تحديث',
                  _summary!.generatedAt != null
                      ? _formatDate(_summary!.generatedAt!)
                      : '-',
                  Icons.update,
                  Colors.grey,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          if (_summary!.reportsAvailable != null) ...[
            const Text(
              'التقارير المتاحة',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            if (_summary!.reportsAvailable!.monthly != null)
              _buildReportCard(
                  _summary!.reportsAvailable!.monthly!, 'شهري'),
            if (_summary!.reportsAvailable!.quarterly != null)
              _buildReportCard(
                  _summary!.reportsAvailable!.quarterly!, 'ربع سنوي'),
            if (_summary!.reportsAvailable!.yearly != null)
              _buildReportCard(
                  _summary!.reportsAvailable!.yearly!, 'سنوي'),
          ],
          const SizedBox(height: 24),
          if (_summary!.movementDistribution != null) ...[
            const Text(
              'توزيع سرعة الحركة',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            _buildDistributionChart(),
          ],
          const SizedBox(height: 24),
          if (_summary!.abcDistribution != null) ...[
            const Text(
              'توزيع تصنيف ABC',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            _buildAbcDistributionChart(),
          ],
        ],
      ),
    );
  }

  Widget _buildMetricCard(
      String label, String value, IconData icon, Color color) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20, color: color),
                const SizedBox(width: 8),
                Text(label, style: const TextStyle(fontSize: 12)),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReportCard(
      InventoryTurnoverReport report, String periodLabel) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.calendar_month, size: 16, color: Colors.blue),
                const SizedBox(width: 8),
                Text(
                  'تقرير $periodLabel - ${report.periodLabel ?? ''}',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                _buildReportMetric('نسبة الدوران',
                    report.inventoryTurnoverRatio?.toStringAsFixed(2) ?? '0',
                    Colors.teal),
                const SizedBox(width: 8),
                _buildReportMetric(
                    'أيام المخزون',
                    '${report.daysInventoryOutstanding?.toStringAsFixed(0) ?? '0'} يوم',
                    Colors.indigo),
                const SizedBox(width: 8),
                _buildReportMetric(
                    'تكلفة البضاعة',
                    '${(report.totalCogs ?? 0).toStringAsFixed(0)} ج.م',
                    Colors.amber[700]!),
              ],
            ),
            if ((report.slowMovingCount ?? 0) > 0 ||
                (report.deadStockCount ?? 0) > 0) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  if ((report.slowMovingCount ?? 0) > 0)
                    _buildBadge('${report.slowMovingCount} بطيء',
                        Colors.orange),
                  const SizedBox(width: 8),
                  if ((report.deadStockCount ?? 0) > 0)
                    _buildBadge(
                        '${report.deadStockCount} راكد', Colors.red),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildBadge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Text(
        text,
        style: TextStyle(
            color: color, fontSize: 11, fontWeight: FontWeight.bold),
      ),
    );
  }

  Widget _buildReportMetric(
      String label, String value, Color color) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            value,
            style: TextStyle(
                color: color,
                fontWeight: FontWeight.bold,
                fontSize: 14),
          ),
          Text(label,
              style: TextStyle(color: color, fontSize: 10)),
        ],
      ),
    );
  }

  Widget _buildDistributionChart() {
    final dist = _summary!.movementDistribution!;
    final total = (dist.fast ?? 0) +
        (dist.medium ?? 0) +
        (dist.slow ?? 0) +
        (dist.dead ?? 0);

    if (total == 0) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Text('لا توجد بيانات توزيع متاحة'),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _buildDistributionRow(
                'سريع', dist.fast ?? 0, total, Colors.green),
            const SizedBox(height: 8),
            _buildDistributionRow(
                'متوسط', dist.medium ?? 0, total, Colors.blue),
            const SizedBox(height: 8),
            _buildDistributionRow(
                'بطيء', dist.slow ?? 0, total, Colors.orange),
            const SizedBox(height: 8),
            _buildDistributionRow(
                'راكد', dist.dead ?? 0, total, Colors.red),
          ],
        ),
      ),
    );
  }

  Widget _buildDistributionRow(
      String label, int count, int total, Color color) {
    final percent =
        total > 0 ? (count / total * 100).toStringAsFixed(1) : '0';
    return Row(
      children: [
        SizedBox(
          width: 50,
          child: Text(label,
              style: const TextStyle(fontSize: 12)),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: total > 0 ? count / total : 0,
              backgroundColor: color.withValues(alpha: 0.1),
              valueColor: AlwaysStoppedAnimation<Color>(color),
              minHeight: 12,
            ),
          ),
        ),
        const SizedBox(width: 8),
        SizedBox(
          width: 50,
          child: Text(
            '$percent%',
            textAlign: TextAlign.right,
            style: TextStyle(fontSize: 12, color: color),
          ),
        ),
      ],
    );
  }

  Widget _buildAbcDistributionChart() {
    final abc = _summary!.abcDistribution!;
    final total =
        (abc.a ?? 0) + (abc.b ?? 0) + (abc.c ?? 0);

    if (total == 0) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Text('لا توجد بيانات تصنيف ABC متاحة'),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _buildDistributionRow(
                'A - عالي', abc.a ?? 0, total, Colors.red),
            const SizedBox(height: 8),
            _buildDistributionRow(
                'B - متوسط', abc.b ?? 0, total, Colors.amber),
            const SizedBox(height: 8),
            _buildDistributionRow(
                'C - منخفض', abc.c ?? 0, total, Colors.green),
          ],
        ),
      ),
    );
  }

  // ========== Products Tab ==========

  Widget _buildProductsTab() {
    if (_loadingProducts) {
      return const Center(child: CircularProgressIndicator());
    }

    return Column(
      children: [
        // Filter bar
        Container(
          padding: const EdgeInsets.all(8),
          color: Colors.grey[100],
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildFilterChip('الكل', '', Colors.blue),
                const SizedBox(width: 4),
                _buildFilterChip('سريع', 'fast', Colors.green),
                const SizedBox(width: 4),
                _buildFilterChip('متوسط', 'medium', Colors.blue),
                const SizedBox(width: 4),
                _buildFilterChip('بطيء', 'slow', Colors.orange),
                const SizedBox(width: 4),
                _buildFilterChip('راكد', 'dead', Colors.red),
                const SizedBox(width: 12),
                // Sort selector
                DropdownButton<String>(
                  value: _productSortBy,
                  underline: const SizedBox(),
                  items: const [
                    DropdownMenuItem(
                        value: 'turnover_ratio',
                        child: Text('نسبة الدوران',
                            style: TextStyle(fontSize: 12))),
                    DropdownMenuItem(
                        value: 'dio',
                        child: Text('أيام المخزون',
                            style: TextStyle(fontSize: 12))),
                    DropdownMenuItem(
                        value: 'stock_velocity',
                        child: Text('سرعة البيع',
                            style: TextStyle(fontSize: 12))),
                  ],
                  onChanged: (value) {
                    setState(() => _productSortBy = value!);
                    _loadProducts();
                  },
                ),
                IconButton(
                  icon: Icon(
                    _productSortDir == 'asc'
                        ? Icons.arrow_upward
                        : Icons.arrow_downward,
                    size: 18,
                  ),
                  onPressed: () {
                    setState(() =>
                        _productSortDir =
                            _productSortDir == 'asc' ? 'desc' : 'asc');
                    _loadProducts();
                  },
                ),
              ],
            ),
          ),
        ),
        // Products list
        Expanded(
          child: (_productAnalysis?.products == null ||
                  _productAnalysis!.products!.isEmpty)
              ? _buildErrorWidget(
                  'لا توجد منتجات محللة', () => _loadProducts())
              : RefreshIndicator(
                  onRefresh: _loadProducts,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(8),
                    itemCount: _productAnalysis!.products!.length,
                    itemBuilder: (context, index) {
                      final product =
                          _productAnalysis!.products![index];
                      return _buildProductCard(product);
                    },
                  ),
                ),
        ),
      ],
    );
  }

  Widget _buildFilterChip(
      String label, String value, Color color) {
    final selected = _productMovementFilter == value;
    return GestureDetector(
      onTap: () {
        setState(() => _productMovementFilter = value);
        _loadProducts();
      },
      child: Container(
        padding:
            const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? color : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.5)),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: selected ? Colors.white : color,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _buildProductCard(ProductAnalysisResult product) {
    Color movementColor;
    switch (product.movementCategory) {
      case 'fast':
        movementColor = Colors.green;
        break;
      case 'medium':
        movementColor = Colors.blue;
        break;
      case 'slow':
        movementColor = Colors.orange;
        break;
      case 'dead':
        movementColor = Colors.red;
        break;
      default:
        movementColor = Colors.grey;
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
                    product.productName ?? 'منتج',
                    style: const TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: movementColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    product.movementStatusLabel,
                    style: TextStyle(
                        color: movementColor,
                        fontSize: 11,
                        fontWeight: FontWeight.bold),
                  ),
                ),
                if (product.abcCategory != null) ...[
                  const SizedBox(width: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.indigo.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
