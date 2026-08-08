import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/StockAudit/model/stock_audit_model.dart';
import 'package:mobile_pos/Screens/StockAudit/repo/stock_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class StockAuditDetailScreen extends StatefulWidget {
  final int auditId;

  const StockAuditDetailScreen({super.key, required this.auditId});

  @override
  State<StockAuditDetailScreen> createState() => _StockAuditDetailScreenState();
}

class _StockAuditDetailScreenState extends State<StockAuditDetailScreen> {
  final StockAuditRepo _repo = StockAuditRepo();
  StockAuditModel? _audit;
  Map<String, dynamic>? _summary;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadAudit();
  }

  Future<void> _loadAudit() async {
    setState(() => _isLoading = true);
    final result = await _repo.getAuditDetail(widget.auditId);
    if (mounted && result != null) {
      setState(() {
        _audit = result.audit;
        _summary = result.summary;
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _performAction(
      Future<StockAuditActionResponse?> Function() action,
      String actionName,
      VoidCallback? onSuccess) async {
    EasyLoading.show(status: '$actionName...');
    final result = await action();
    EasyLoading.dismiss();

    if (result != null && mounted) {
      setState(() {
        _audit = result.audit;
      });
      EasyLoading.showSuccess(result.message ?? '$actionName successful');
      onSuccess?.call();
      _loadAudit();
    } else {
      EasyLoading.showError('Failed to $actionName');
    }
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

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _getStatusColor(_audit?.status);

    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: kWhite),
        title: Text(
          'Audit #${_audit?.auditNumber ?? '...'}',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          if (!_isLoading && _audit?.status == 'pending')
            IconButton(
              icon: const Icon(Icons.play_arrow_rounded, color: kWhite),
              onPressed: () => _performAction(
                () => _repo.startAudit(_audit!.id!),
                'Start Audit',
                () => Navigator.pop(context, true),
              ),
              tooltip: 'Start',
            ),
          if (!_isLoading && _audit?.status == 'in_progress')
            IconButton(
              icon: const Icon(Icons.check_circle_rounded, color: kWhite),
              onPressed: () => _performAction(
                () => _repo.completeAudit(_audit!.id!),
                'Complete Audit',
                () => Navigator.pop(context, true),
              ),
              tooltip: 'Complete',
            ),
          if (!_isLoading && _audit?.status != 'cancelled')
            IconButton(
              icon: const Icon(Icons.refresh_rounded, color: kWhite),
              onPressed: _loadAudit,
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : _audit == null
              ? const Center(child: Text('Audit not found'))
              : RefreshIndicator(
                  onRefresh: _loadAudit,
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Status Badge
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 8),
                          decoration: BoxDecoration(
                            color: statusColor.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(12),
                            border:
                                Border.all(color: statusColor, width: 1),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.circle,
                                color: statusColor,
                                size: 12,
                              ),
                              const SizedBox(width: 8),
                              Text(
                                _audit!.status?.toUpperCase() ?? 'UNKNOWN',
                                style: TextStyle(
                                  color: statusColor,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),

                        // Audit Info Card
                        _buildInfoCard(theme),
                        const SizedBox(height: 16),

                        // Summary Card
                        if (_summary != null) _buildSummaryCard(theme),
                        const SizedBox(height: 16),

                        // Details Section
                        if (_audit!.details != null &&
                            _audit!.details!.isNotEmpty)
                          _buildDetailsSection(theme),
                        const SizedBox(height: 16),

                        // Action Buttons
                        _buildActionButtons(theme, statusColor),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildInfoCard(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.all(16),
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Audit Information',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: kNutral800,
            ),
          ),
          const SizedBox(height: 12),
          _buildInfoRow(Icons.numbers_outlined, 'Audit Type',
              _audit!.auditType ?? 'N/A', theme),
          const SizedBox(height: 8),
          _buildInfoRow(
              Icons.person_outline,
              'Created By',
              _audit!.user?.name ?? 'N/A',
              theme),
          const SizedBox(height: 8),
          _buildInfoRow(
              Icons.calendar_today_outlined,
              'Audit Date',
              _audit!.auditDate != null
                  ? DateFormat.yMMMd().format(DateTime.parse(_audit!.auditDate!))
                  : 'Not started',
              theme),
          const SizedBox(height: 8),
          if (_audit!.completedAt != null)
            _buildInfoRow(
                Icons.check_circle_outline,
                'Completed At',
                DateFormat.yMMMd().add_jm().format(
                    DateTime.parse(_audit!.completedAt!)),
                theme),
          if (_audit!.notes != null && _audit!.notes!.isNotEmpty)
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 8),
                Text(
                  'Notes:',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: kNutral700,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  _audit!.notes!,
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: kNutral600, fontSize: 12),
                ),
              ],
            ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(
      IconData icon, String label, String value, ThemeData theme) {
    return Row(
      children: [
        Icon(icon, size: 16, color: kNutral600),
        const SizedBox(width: 8),
        Text(
          '$label:',
          style: theme.textTheme.bodySmall
              ?.copyWith(color: kNutral600, fontSize: 12),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            value,
            style: theme.textTheme.bodySmall?.copyWith(
              color: kNutral800,
              fontWeight: FontWeight.w500,
              fontSize: 12,
            ),
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.right,
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryCard(ThemeData theme) {
    final data = _summary!;
    return Container(
      padding: const EdgeInsets.all(16),
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
          _buildSummaryRow('Total Items',
              '${data['total_items'] ?? 0}', theme),
          _buildSummaryRow('System Qty',
              '${data['total_system_quantity'] ?? 0}', theme),
          _buildSummaryRow('Physical Qty',
              '${data['total_physical_quantity'] ?? 0}', theme),
          _buildSummaryRow('Variance',
              '${data['total_variance'] ?? 0}', theme,
              isVariance: true),
          const SizedBox(height: 8),
          const Divider(height: 1),
          const SizedBox(height: 8),
          _buildSummaryRow(
              'Positive Variances',
              '${data['positive_variances'] ?? 0}',
              theme),
          _buildSummaryRow(
              'Negative Variances',
              '${data['negative_variances'] ?? 0}',
              theme),
          _buildSummaryRow('No Variances',
              '${data['no_variances'] ?? 0}', theme),
          const SizedBox(height: 8),
          const Divider(height: 1),
          const SizedBox(height: 8),
          _buildSummaryRow(
              'Reconciliations Created',
              '${data['reconciliations_created'] ?? 0}',
              theme),
          _buildSummaryRow(
              'Reconciliations Posted',
              '${data['reconciliations_posted'] ?? 0}',
              theme),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value, ThemeData theme,
      {bool isVariance = false}) {
    final isNegative = isVariance && (int.tryParse(value.replaceAll(',', '')) ?? 0) < 0;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral600, fontSize: 12),
          ),
          Text(
            value,
            style: theme.textTheme.bodySmall?.copyWith(
              color: isNegative ? const Color(0xFFE53935) : kNutral800,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailsSection(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Details (${_audit!.details!.length})',
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: kNutral800,
          ),
        ),
        const SizedBox(height: 8),
        ListView.separated(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: _audit!.details!.length,
          separatorBuilder: (context, index) => const SizedBox(height: 4),
          itemBuilder: (context, index) {
            final detail = _audit!.details![index];
            final hasVariance = (detail.variance ?? 0) != 0;
            return Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: hasVariance
                    ? const Color(0xFFFFEBEE)
                    : Colors.grey.shade50,
                borderRadius: BorderRadius.circular(8),
                border: hasVariance
                    ? const Border(
                        left: BorderSide(
                            color: Color(0xFFE53935), width: 3))
                    : null,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    detail.product?.productName ?? 'Unknown Product',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${detail.product?.productCode ?? ''} • Batch: ${detail.batchNo ?? 'N/A'}',
                    style: theme.textTheme.bodySmall
                        ?.copyWith(color: kNutral600, fontSize: 11),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'System: ${detail.systemQuantity ?? 0}',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: kNutral600,
                          fontSize: 12,
                        ),
                      ),
                      Text(
                        'Physical: ${detail.physicalQuantity ?? 0}',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: kNutral600,
                          fontSize: 12,
                        ),
                      ),
                      if (hasVariance)
                        Text(
                          'Variance: ${detail.variance} (${detail.varianceType})',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: (detail.varianceType == 'positive' ||
                                    detail.varianceType == 'negative')
                                ? const Color(0xFFE53935)
                                : kMainColor,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                        ),
                    ],
                  ),
                ],
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildActionButtons(ThemeData theme, Color statusColor) {
    final status = _audit?.status;

    if (status == 'cancelled') return const SizedBox.shrink();

    if (status == 'pending') {
      return Column(
        children: [
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _performAction(
                () => _repo.startAudit(_audit!.id!),
                'Start',
                () {},
              ),
              icon: const Icon(Icons.play_arrow_rounded),
              label: const Text('Start Audit'),
              style: ElevatedButton.styleFrom(
                backgroundColor: kMainColor,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                _showCancelDialog(context, theme);
              },
              icon: const Icon(Icons.cancel_outlined),
              label: const Text('Cancel Audit'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade50,
                foregroundColor: Colors.red,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
        ],
      );
    }

    if (status == 'in_progress') {
      return Column(
        children: [
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () async {
                await Navigator.pushNamed(
                  context,
                  '/stock-audit-add-detail',
                  arguments: _audit!.id,
                );
                _loadAudit();
              },
              icon: const Icon(Icons.add_rounded),
              label: const Text('Add Items'),
              style: ElevatedButton.styleFrom(
                backgroundColor: kMainColor,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () async {
                await Navigator.pushNamed(
                  context,
                  '/stock-audit-variance',
                  arguments: _audit!.id,
                );
              },
              icon: const Icon(Icons.bar_chart_rounded),
              label: const Text('Variance Report'),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1976D2),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _performAction(
                () => _repo.completeAudit(_audit!.id!),
                'Complete',
                () => Navigator.pop(context, true),
              ),
              icon: const Icon(Icons.check_circle_rounded),
              label: const Text('Complete Audit'),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF43A047),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
        ],
      );
    }

    // Completed
    return Column(
      children: [
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: () async {
              await Navigator.pushNamed(
                context,
                '/stock-audit-variance',
                arguments: _audit!.id,
              );
            },
            icon: const Icon(Icons.bar_chart_rounded),
            label: const Text('View Variance Report'),
            style: ElevatedButton.styleFrom(
              backgroundColor: kMainColor,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      ],
    );
  }

  void _showCancelDialog(BuildContext context, ThemeData theme) {
    final TextEditingController reasonController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
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
              'Cancel Audit',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: reasonController,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: 'Enter cancellation reason (optional)',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('No, Go Back'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () async {
                      final reason = reasonController.text.isNotEmpty
                          ? reasonController.text
                          : null;
                      Navigator.pop(context);
                      await _performAction(
                        () => _repo.cancelAudit(_audit!.id!, reason),
                        'Cancel',
                        () => Navigator.pop(context, true),
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: const Text('Yes, Cancel'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
