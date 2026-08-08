import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class InsurancePolicyDetailScreen extends StatefulWidget {
  final int policyId;

  const InsurancePolicyDetailScreen({super.key, required this.policyId});

  @override
  State<InsurancePolicyDetailScreen> createState() =>
      _InsurancePolicyDetailScreenState();
}

class _InsurancePolicyDetailScreenState
    extends State<InsurancePolicyDetailScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  InsurancePolicyModel? _policy;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadPolicy();
  }

  Future<void> _loadPolicy() async {
    setState(() => _isLoading = true);
    final result = await _repo.getPolicies();
    if (mounted) {
      final policies = result?.policies ?? [];
      setState(() {
        _policy = policies.firstWhere(
          (p) => p.id == widget.policyId,
          orElse: () => policies.isNotEmpty ? policies.first : InsurancePolicyModel(),
        );
        _isLoading = false;
      });
    }
  }

  Future<void> _deletePolicy() async {
    EasyLoading.show(status: 'Deleting...');
    final result = await _repo.deletePolicy(widget.policyId);
    EasyLoading.dismiss();

    if (result != null && mounted) {
      EasyLoading.showSuccess(result.message ?? 'Deleted');
      Navigator.pop(context, true);
    } else {
      EasyLoading.showError('Failed to delete');
    }
  }

  void _showDeleteConfirmation() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Policy'),
        content: Text(
          'Are you sure you want to delete this policy? This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _deletePolicy();
            },
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  void _showCopyMenu() {
    showMenu(
      context: context,
      position: const RelativeRect.fromLTRB(100, 100, 0, 0),
      items: [
        PopupMenuItem(
          child: const Text('Copy Policy Number'),
          onTap: () {
            if (_policy?.policyNumber != null) {
              Clipboard.setData(
                ClipboardData(text: _policy!.policyNumber!),
              );
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Copied to clipboard')),
              );
            }
          },
        ),
      ],
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
          'Policy Details',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          if (_policy != null)
            IconButton(
              icon: const Icon(Icons.copy_rounded, color: kWhite),
              onPressed: _showCopyMenu,
            ),
          IconButton(
            icon: const Icon(Icons.refresh, color: kWhite),
            onPressed: _loadPolicy,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : _policy == null || _policy!.id == null
              ? const Center(child: Text('Policy not found'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildHeaderCard(theme),
                      const SizedBox(height: 16),
                      _buildDetailsCard(theme),
                      const SizedBox(height: 16),
                      _buildCoveragesCard(theme),
                      const SizedBox(height: 16),
                      _buildClaimsCard(theme),
                      const SizedBox(height: 24),
                      _buildActionButtons(theme),
                    ],
                  ),
                ),
    );
  }

  Widget _buildHeaderCard(ThemeData theme) {
    final p = _policy!;
    final statusColor = _getStatusColor(p.status);
    final isExpired = p.endDate != null
        ? DateTime.tryParse(p.endDate!)?.isBefore(DateTime.now()) ?? false
        : false;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            statusColor.withValues(alpha: 0.8),
            statusColor.withValues(alpha: 0.6),
          ],
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                p.policyNumber ?? 'Unknown',
                style: theme.textTheme.titleLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
              if (isExpired)
                const Icon(Icons.warning_rounded, color: Colors.white70),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            p.company?.name ?? 'Unknown Company',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: Colors.white70,
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Text(
              isExpired ? 'EXPIRED' : _getStatusText(p.status),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailsCard(ThemeData theme) {
    final p = _policy!;
    final dateFormat = DateFormat('MMM dd, yyyy');

    return Card(
      elevation: 1,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: kOutlineColor, width: 1),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Policy Details',
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w600,
                color: kNutral800,
              ),
            ),
            const SizedBox(height: 12),
            _buildDetailRow(
                theme, 'Policy Type', p.policyType ?? 'N/A'),
            _buildDetailRow(
                theme, 'Coverage Type', p.coverageType ?? 'N/A'),
            _buildDetailRow(
                theme,
                'Coverage Amount',
                _formatCurrency(p.coverageAmount)),
            _buildDetailRow(
                theme,
                'Premium Amount',
                _formatCurrency(p.premiumAmount)),
            _buildDetailRow(
                theme,
                'Start Date',
                p.startDate != null
                    ? dateFormat.format(DateTime.parse(p.startDate!))
                    : 'N/A'),
            _buildDetailRow(
                theme,
                'End Date',
                p.endDate != null
                    ? dateFormat.format(DateTime.parse(p.endDate!))
                    : 'N/A'),
          ],
        ),
      ),
    );
  }

  Widget _buildCoveragesCard(ThemeData theme) {
    return Card(
      elevation: 1,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: kOutlineColor, width: 1),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Coverage Rules',
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w600,
                color: kNutral800,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              'No coverage rules defined for this policy.',
              style: theme.textTheme.bodySmall?.copyWith(
                color: kNutral600,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildClaimsCard(ThemeData theme) {
    return Card(
      elevation: 1,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: kOutlineColor, width: 1),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Claims',
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w600,
                color: kNutral800,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              'No claims filed for this policy.',
              style: theme.textTheme.bodySmall?.copyWith(
                color: kNutral600,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionButtons(ThemeData theme) {
    return Row(
      children: [
        Expanded(
          child: OutlinedButton.icon(
            onPressed: () async {
              await Navigator.pushNamed(
                context,
                '/add-edit-insurance-policy',
                arguments: _policy,
              );
              _loadPolicy();
            },
            icon: const Icon(Icons.edit, color: kMainColor),
            label: const Text('Edit',
                style: TextStyle(color: kMainColor)),
            style: OutlinedButton.styleFrom(
              side: const BorderSide(color: kMainColor),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: ElevatedButton.icon(
            onPressed: _showDeleteConfirmation,
            icon: const Icon(Icons.delete, color: Colors.white),
            label: const Text('Delete',
                style: TextStyle(color: Colors.white)),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFE53935),
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

  Widget _buildDetailRow(ThemeData theme, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
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
              color: kNutral800,
              fontWeight: FontWeight.w500,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }

  String _getStatusText(String? status) {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'Active';
      case 'expired':
        return 'Expired';
      case 'pending':
        return 'Pending';
      default:
        return status ?? 'Unknown';
    }
  }

  Color _getStatusColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'active':
        return const Color(0xFF43A047);
      case 'expired':
        return const Color(0xFFE53935);
      case 'pending':
        return const Color(0xFFFF9800);
      default:
        return kNutral600;
    }
  }

  String _formatCurrency(dynamic value) {
    if (value == null) return '0.00';
    final numValue = double.tryParse(value.toString()) ?? 0;
    return '\$${numValue.toStringAsFixed(2)}';
  }
}
