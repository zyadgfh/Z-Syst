import 'package:flutter/material.dart';
import 'package:mobile_pos/Screens/Fefo/repo/fefo_repo.dart';
import 'package:mobile_pos/Screens/Fefo/model/fefo_model.dart';
import 'package:mobile_pos/Provider/language_provider.dart';
import 'package:provider/provider.dart';

class FefoReportScreen extends StatefulWidget {
  const FefoReportScreen({super.key});

  @override
  State<FefoReportScreen> createState() => _FefoReportScreenState();
}

class _FefoReportScreenState extends State<FefoReportScreen> {
  final FefoRepo _repo = FefoRepo();
  bool _loading = true;
  FefoReport? _report;

  @override
  void initState() {
    super.initState();
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() => _loading = true);
    final report = await _repo.getReport();
    setState(() {
      _report = report;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final lang = Provider.of<LanguageProvider>(context).currentLang;
    return Scaffold(
      appBar: AppBar(
        title: Text(lang == 'ar' ? 'تقرير FEFO' : 'FEFO Report'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadReport,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadReport,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Summary Cards
                    _buildSummaryRow(lang),
                    const SizedBox(height: 16),

                    // Expiry Breakdown
                    _buildExpiryBreakdown(lang),
                    const SizedBox(height: 16),

                    // Priority Products
                    if (_report?.priorityProducts != null && _report!.priorityProducts!.isNotEmpty)
                      _buildPriorityProducts(lang),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSummaryRow(String lang) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.6,
      children: [
        _buildStatCard(
          title: lang == 'ar' ? 'إجمالي الدفعات' : 'Total Batches',
          value: '${_report?.totalBatches ?? 0}',
          color: Colors.blue,
          icon: Icons.inventory_2,
        ),
        _buildStatCard(
          title: lang == 'ar' ? 'إجمالي الكمية' : 'Total Qty',
          value: '${_report?.totalStockQty ?? 0}',
          color: Colors.teal,
          icon: Icons.inventory,
        ),
        _buildStatCard(
          title: lang == 'ar' ? 'منتهي الصلاحية' : 'Expired',
          value: '${_report?.expiredBatches ?? 0}',
          color: Colors.red,
          icon: Icons.warning_amber,
          subtitle: '${_report?.expiredQty ?? 0} ${lang == 'ar' ? 'وحدة' : 'units'}',
        ),
        _buildStatCard(
          title: lang == 'ar' ? 'خلال 30 يوماً' : 'Within 30 Days',
          value: '${_report?.expiringWithin30Days ?? 0}',
          color: Colors.orange,
          icon: Icons.timer,
          subtitle: '${_report?.expiring30Qty ?? 0} ${lang == 'ar' ? 'وحدة' : 'units'}',
        ),
      ],
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required Color color,
    required IconData icon,
    String? subtitle,
  }) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            if (subtitle != null)
              Text(
                subtitle,
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.grey[500],
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildExpiryBreakdown(String lang) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              lang == 'ar' ? 'تفصيل حالات انتهاء الصلاحية' : 'Expiry Breakdown',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            _buildProgressItem(
              label: lang == 'ar' ? 'منتهي (Expired)' : 'Expired',
              value: _report?.expiredBatches ?? 0,
              total: _report?.totalBatches ?? 1,
              color: Colors.red,
            ),
            _buildProgressItem(
              label: lang == 'ar' ? 'خلال 7 أيام' : 'Within 7 Days',
              value: _report?.expiringWithin7Days ?? 0,
              total: _report?.totalBatches ?? 1,
              color: Colors.deepOrange,
            ),
            _buildProgressItem(
              label: lang == 'ar' ? 'خلال 30 يوماً' : 'Within 30 Days',
              value: _report?.expiringWithin30Days ?? 0,
              total: _report?.totalBatches ?? 1,
              color: Colors.orange,
            ),
            _buildProgressItem(
              label: lang == 'ar' ? 'بدون تاريخ صلاحية' : 'No Expiry Date',
              value: _report?.noExpiryBatches ?? 0,
              total: _report?.totalBatches ?? 1,
              color: Colors.grey,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProgressItem({
    required String label,
    required int value,
    required int total,
    required Color color,
  }) {
    final percentage = total > 0 ? (value / total) : 0.0;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: const TextStyle(fontSize: 14)),
              Text('$value', style: TextStyle(fontWeight: FontWeight.bold, color: color)),
            ],
          ),
          const SizedBox(height: 4),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: percentage.clamp(0.0, 1.0),
              backgroundColor: Colors.grey[200],
              color: color,
              minHeight: 8,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPriorityProducts(String lang) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.priority_high, color: Colors.orange),
                const SizedBox(width: 8),
                Text(
                  lang == 'ar' ? 'المنتجات ذات الأولوية (FEFO)' : 'Priority Products (FEFO)',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              lang == 'ar' ? 'منتجات تحتاج للبيع أولاً بسبب قرب انتهاء صلاحيتها'
                  : 'Products that should be sold first due to near expiry',
              style: TextStyle(color: Colors.grey[600], fontSize: 12),
            ),
            const SizedBox(height: 12),
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _report?.priorityProducts?.length ?? 0,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final item = _report!.priorityProducts![index];
                final urgent = (item.nearExpiryQty ?? 0) > 0;
                return ListTile(
                  dense: true,
                  leading: CircleAvatar(
                    backgroundColor: urgent ? Colors.orange[100] : Colors.grey[100],
                    child: Text(
                      '${index + 1}',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: urgent ? Colors.orange : Colors.grey,
                      ),
                    ),
                  ),
                  title: Text(item.productName ?? 'N/A'),
                  subtitle: Text(
                    lang == 'ar'
                        ? 'المخزون: ${item.totalStock ?? 0} | قريب الانتهاء: ${item.nearExpiryQty ?? 0}'
                        : 'Stock: ${item.totalStock ?? 0} | Near Expiry: ${item.nearExpiryQty ?? 0}',
                  ),
                  trailing: urgent
                      ? Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.red[50],
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.red[200]!),
                          ),
                          child: Text(
                            lang == 'ar' ? 'عاجل' : 'Urgent',
                            style: TextStyle(
                              color: Colors.red[700],
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        )
                      : null,
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

