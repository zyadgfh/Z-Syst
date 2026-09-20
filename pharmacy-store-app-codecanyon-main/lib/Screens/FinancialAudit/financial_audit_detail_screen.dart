import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/FinancialAudit/model/financial_audit_model.dart';
import 'package:mobile_pos/Screens/FinancialAudit/repo/financial_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';

class FinancialAuditDetailScreen extends StatefulWidget {
  final int auditId;

  const FinancialAuditDetailScreen({super.key, required this.auditId});

  @override
  State<FinancialAuditDetailScreen> createState() =>
      _FinancialAuditDetailScreenState();
}

class _FinancialAuditDetailScreenState
    extends State<FinancialAuditDetailScreen> {
  final FinancialAuditRepo _repo = FinancialAuditRepo();
  FinancialAuditModel? _audit;
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
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _performAction(
      Future<FinancialAuditActionResponse?> Function() action,
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
                () {},
              ),
              tooltip: 'Start',
            ),
          if (!_isLoading)
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
                            border: Border.all(color: statusColor, width: 1),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.circle, color: statusColor, size: 12),
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

                        // Balances Card (for in_progress)
                        if (_audit?.status == 'in_progress' ||
                            _audit?.status == 'completed')
                          _buildBalancesCard(theme),
                        const SizedBox(height: 16),

                        // Transactions Section
                        if (_audit?.status == 'completed' ||
                            _audit?.status == 'in_progress')
                          _buildTransactionsSection(theme),
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
          _buildInfoRow(Icons.category_outlined, 'Audit Type',
              _audit!.auditType ?? 'N/A', theme),
          const SizedBox(height: 8),
          _buildInfoRow(Icons.calendar_today_outlined, 'Start Date',
              _audit!.startDate ?? 'N/A', theme),
          const SizedBox(height: 8),
          _buildInfoRow(Icons.calendar_today_outlined, 'End Date',
              _audit!.endDate ?? 'N/A', theme),
          const SizedBox(height: 8),
          _buildInfoRow(Icons.person_outline, 'Created By',
              _audit!.user?.name ?? 'N/A', theme),
          if (_audit!.completedAt != null)
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 8),
                _buildInfoRow(Icons.check_circle_outline, 'Completed At',
                    DateFormat.yMMMd().add_jm().format(
                        DateTime.parse(_audit!.completedAt!)),
                    theme),
              ],
            ),
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

  Widget _buildBalancesCard(ThemeData theme) {
    final openingBalance =
        double.tryParse(_audit!.openingBalance?.toString() ?? '0') ?? 0;
    final closingBalance =
        double.tryParse(_audit!.closingBalance?.toString() ?? '0') ?? 0;
    final variance = double.tryParse(_audit!.variance?.toString() ?? '0') ?? 0;
    final varianceColor = variance >= 0
        ? const Color(0xFF43A047)
        : const Color(0xFFE53935);

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
            'Financial Balances',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: kNutral800,
            ),
          ),
          const SizedBox(height: 12),
          _buildBalanceRow('Opening Balance', openingBalance, theme),
          _buildBalanceRow('Closing Balance', closingBalance, theme,
              color: variance >= 0 ? const Color(0xFF43A047) : kMainColor),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF5F5F5),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              children: [
                _buildBalanceRow('Variance', variance, theme,
                    color: varianceColor, isBold: true),
                const SizedBox(height: 4),
                Text(
                  variance.abs() < 0.01 ? 'Balanced' : 'Requires investigation',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: variance.abs() < 0.01
                        ? const Color(0xFF43A047)
                        : const Color(0xFFE53935),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBalanceRow(String label, dynamic value, ThemeData theme,
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
            NumberFormat.currency(locale: 'en_US', symbol: '').format(value),
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

  Widget _buildTransactionsSection(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Transactions',
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: kNutral800,
          ),
        ),
        const SizedBox(height: 8),
        _buildTransactionTile(theme, 'Sales', Icons.point_of_sale_rounded,
            _audit!.totalRevenue ?? 0, kMainColor),
        _buildTransactionTile(
            theme, 'Expenses', Icons.money_off_rounded, _audit!.totalExpenses ?? 0,
            const Color(0xFFE53935)),
        const SizedBox(height: 8),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            onPressed: () async {
              await Navigator.pushNamed(
                context,
                '/financial-audit-transactions',
                arguments: widget.auditId,
              );
            },
            icon: const Icon(Icons.list_rounded),
            label: const Text('View Transaction Details'),
            style: OutlinedButton.styleFrom(
              foregroundColor: kMainColor,
              side: const BorderSide(color: kMainColor),
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTransactionTile(
    ThemeData theme,
    String label,
    IconData icon,
    dynamic amount,
    Color color,
  ) {
    return ListTile(
      leading: CircleAvatar(
        radius: 16,
        backgroundColor: color.withValues(alpha: 0.15),
        child: Icon(icon, color: color, size: 18),
      ),
      title: Text(label, style: const TextStyle(fontSize: 13)),
      trailing: Text(
        NumberFormat.currency(locale: 'en_US', symbol: '').format(amount),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w600,
          fontSize: 13,
        ),
      ),
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
              onPressed: () => _showCancelDialog(context, theme),
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
                  '/financial-audit-execute',
                  arguments: _audit!.id,
                );
                _loadAudit();
              },
              icon: const Icon(Icons.calculate_rounded),
              label: const Text('Execute (Enter Balances)'),
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
                '/financial-audit-report',
                arguments: _audit!.id,
              );
            },
            icon: const Icon(Icons.picture_as_pdf_rounded),
            label: const Text('View Report'),
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
