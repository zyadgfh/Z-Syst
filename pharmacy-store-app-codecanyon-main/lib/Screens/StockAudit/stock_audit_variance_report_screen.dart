import 'package:flutter/material.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/StockAudit/model/stock_audit_model.dart';
import 'package:mobile_pos/Screens/StockAudit/repo/stock_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';

class StockAuditVarianceScreen extends StatefulWidget {
  final int auditId;

  const StockAuditVarianceScreen({super.key, required this.auditId});

  @override
  State<StockAuditVarianceScreen> createState() =>
      _StockAuditVarianceScreenState();
}

class _StockAuditVarianceScreenState extends State<StockAuditVarianceScreen> {
  final StockAuditRepo _repo = StockAuditRepo();
  StockAuditVarianceData? _report;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() => _isLoading = true);
    final result = await _repo.getVarianceReport(widget.auditId);
    if (mounted && result != null) {
      setState(() {
        _report = result.report;
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  void _reconcileItem(VarianceItem item) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _ReconcileSheet(item: item),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: kWhite),
        title: Text(
          'Variance Report',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: kWhite),
            onPressed: _loadReport,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : _report == null
              ? const Center(child: Text('Failed to load report'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Audit Info
                      if (_report!.audit != null)
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.05),
                                blurRadius: 6,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Row(
                            mainAxisAlignment:
                                MainAxisAlignment.spaceBetween,
                            children: [
                              Column(
                                crossAxisAlignment:
                                    CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Audit #${_report!.audit!['audit_number'] ?? 'N/A'}',
                                    style: theme.textTheme.titleSmall
                                        ?.copyWith(fontWeight: FontWeight.w600),
                                  ),
                                  Text(
                                    '${_report!.audit!['audit_type'] ?? ''}',
                                    style: theme.textTheme.bodySmall
                                        ?.copyWith(color: kNutral600),
                                  ),
                                ],
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: _getStatusColor(
                                          _report!.audit!['status'])
                                      .withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  _report!.audit!['status'] ?? '',
                                  style: TextStyle(
                                    color: _getStatusColor(
                                        _report!.audit!['status']),
                                    fontWeight: FontWeight.w600,
                                    fontSize: 11,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      const SizedBox(height: 16),

                      // Variance Summary
                      if (_report!.variances != null &&
                          _report!.variances!.isNotEmpty)
                        _buildVarianceSummary(theme),
                      const SizedBox(height: 16),

                      // Variance Items List
                      Text(
                        'Items with Variance (${_report!.variances?.length ?? 0})',
                        style: theme.textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w600,
                          color: kNutral800,
                        ),
                      ),
                      const SizedBox(height: 8),
                      if (_report!.variances != null &&
                          _report!.variances!.isNotEmpty)
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _report!.variances!.length,
                          separatorBuilder: (context, index) =>
                              const SizedBox(height: 4),
                          itemBuilder: (context, index) {
                            final item = _report!.variances![index];
                            final isPositive = item.varianceType == 'positive';
                            final isNegative = item.varianceType == 'negative';
                            final varianceColor = isPositive
                                ? const Color(0xFFE53935)
                                : isNegative
                                    ? const Color(0xFF1976D2)
                                    : kMainColor;

                            return Container(
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                    color: varianceColor.withValues(alpha: 0.3),
                                    width: 1.5),
                              ),
                              child: ExpansionTile(
                                leading: CircleAvatar(
                                  radius: 16,
                                  backgroundColor:
                                      varianceColor.withValues(alpha: 0.15),
                                  child: Icon(
                                    isPositive
                                        ? Icons.trending_up_rounded
                                        : Icons.trending_down_rounded,
                                    color: varianceColor,
                                    size: 18,
                                  ),
                                ),
                                title: Text(
                                  item.productName ?? 'Unknown',
                                  style: const TextStyle(fontSize: 13),
                                ),
                                subtitle: Text(
                                  'Batch: ${item.batchNo ?? 'N/A'}',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: kNutral600,
                                  ),
                                ),
                                children: [
                                  Padding(
                                    padding: const EdgeInsets.all(16),
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        _buildVarianceRow('System Qty',
                                            '${item.systemQuantity ?? 0}',
                                            theme),
                                        _buildVarianceRow('Physical Qty',
                                            '${item.physicalQuantity ?? 0}',
                                            theme),
                                        _buildVarianceRow('Variance',
                                            '${item.variance ?? 0}', theme,
                                            color: varianceColor,
                                            isBold: true),
                                        _buildVarianceRow('Variance Value',
                                            '${item.varianceValue ?? 0}',
                                            theme),
                                        const SizedBox(height: 12),
                                        SizedBox(
                                          width: double.infinity,
                                          child: ElevatedButton.icon(
                                            onPressed: () =>
                                                _reconcileItem(item),
                                            icon: const Icon(
                                                Icons.scale_outlined),
                                            label: const Text('Reconcile'),
                                            style: ElevatedButton.styleFrom(
                                              backgroundColor: kMainColor,
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                      vertical: 12),
                                              shape: RoundedRectangleBorder(
                                                borderRadius:
                                                    BorderRadius.circular(8),
                                              ),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        )
                      else
                        Padding(
                          padding: const EdgeInsets.only(top: 40),
                          child: EmptyListWidget(
                            title: 'No variances found in this audit',
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildVarianceSummary(ThemeData theme) {
    final variances = _report!.variances!;
    final totalSystem = variances.fold<int>(
        0, (sum, item) => sum + (item.systemQuantity ?? 0));
    final totalPhysical = variances.fold<int>(
        0, (sum, item) => sum + (item.physicalQuantity ?? 0));
    final positiveCount = variances
        .where((item) => item.varianceType == 'positive')
        .length;
    final negativeCount = variances
        .where((item) => item.varianceType == 'negative')
        .length;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFE3F2FD), Color(0xFFBBDEFB)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Summary',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: kNutral800,
            ),
          ),
          const SizedBox(height: 12),
          _buildSummaryRow('Total System Qty', '$totalSystem', theme),
          _buildSummaryRow('Total Physical Qty', '$totalPhysical', theme),
          _buildSummaryRow('Positive Variances', '$positiveCount', theme,
              color: const Color(0xFFE53935)),
          _buildSummaryRow('Negative Variances', '$negativeCount', theme,
              color: const Color(0xFF1976D2)),
        ],
      ),
    );
  }

  Widget _buildVarianceRow(String label, String value, ThemeData theme,
      {Color? color, bool isBold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral700, fontSize: 12),
          ),
          Text(
            value,
            style: theme.textTheme.bodySmall?.copyWith(
              color: color,
              fontWeight: isBold ? FontWeight.w600 : FontWeight.normal,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value, ThemeData theme,
      {Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral700, fontSize: 12),
          ),
          Text(
            value,
            style: theme.textTheme.bodySmall?.copyWith(
              color: color ?? kNutral800,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }

  Color _getStatusColor(String? status) {
    switch (status) {
      case 'completed':
        return const Color(0xFF43A047);
      case 'in_progress':
        return const Color(0xFFFF6D00);
      case 'cancelled':
        return const Color(0xFFE53935);
      case 'pending':
      default:
        return const Color(0xFF1E88E5);
    }
  }
}

class _ReconcileSheet extends StatelessWidget {
  final VarianceItem item;
  final StockAuditRepo _repo = StockAuditRepo();

  _ReconcileSheet({super.key, required this.item});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom + 16,
        left: 16,
        right: 16,
        top: 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            'Reconcile Variance',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
          ),
          const SizedBox(height: 16),
          Text(
            '${item.productName ?? 'Unknown'}',
            style: Theme.of(context)
                .textTheme
                .titleSmall
                ?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text(
            'Variance: ${item.variance ?? 0} | Value: ${item.varianceValue ?? 0}',
            style: Theme.of(context)
                .textTheme
                .bodySmall
                ?.copyWith(color: kNutral600),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Reconciliation feature coming soon')),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: kMainColor,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: const Text('Create Reconciliation'),
            ),
          ),
        ],
      ),
    );
  }
}
