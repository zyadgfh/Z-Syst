import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class InsuranceClaimListScreen extends StatefulWidget {
  const InsuranceClaimListScreen({super.key});

  @override
  State<InsuranceClaimListScreen> createState() =>
      _InsuranceClaimListScreenState();
}

class _InsuranceClaimListScreenState extends State<InsuranceClaimListScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  List<InsuranceClaimModel> _claims = [];
  bool _isLoading = true;
  String _search = '';
  String? _selectedStatus;

  final List<String> _statuses = [
    'pending',
    'submitted',
    'approved',
    'rejected',
    'paid'
  ];

  @override
  void initState() {
    super.initState();
    _loadClaims();
  }

  Future<void> _loadClaims() async {
    setState(() => _isLoading = true);
    final result = await _repo.getClaims(status: _selectedStatus);
    if (mounted) {
      setState(() {
        _claims = result?.claims ?? [];
        _isLoading = false;
      });
    }
  }

  List<InsuranceClaimModel> get _filteredClaims {
    if (_search.isEmpty && _selectedStatus == null) return _claims;
    return _claims.where((c) {
      final matchesSearch = _search.isEmpty ||
          (c.claimNumber ?? '')
              .toLowerCase()
              .contains(_search.toLowerCase()) ||
          (c.policy?.policyNumber ?? '')
              .toLowerCase()
              .contains(_search.toLowerCase());
      return matchesSearch;
    }).toList();
  }

  String _getStatusText(String? status) {
    switch (status?.toLowerCase()) {
      case 'pending':
        return 'Pending';
      case 'submitted':
        return 'Submitted';
      case 'approved':
        return 'Approved';
      case 'rejected':
        return 'Rejected';
      case 'paid':
        return 'Paid';
      default:
        return status ?? 'Unknown';
    }
  }

  Color _getStatusColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'pending':
        return const Color(0xFFFF9800);
      case 'submitted':
        return const Color(0xFF2196F3);
      case 'approved':
        return const Color(0xFF43A047);
      case 'rejected':
        return const Color(0xFFE53935);
      case 'paid':
        return const Color(0xFF00987F);
      default:
        return kNutral600;
    }
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
          'Insurance Claims',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: kWhite),
            onPressed: _loadClaims,
          ),
        ],
      ),
      body: Column(
        children: [
          // Search + Filter
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                TextField(
                  onChanged: (v) => setState(() => _search = v),
                  decoration: InputDecoration(
                    hintText: 'Search claims...',
                    hintStyle: const TextStyle(color: kNutral600),
                    fillColor: Colors.white,
                    filled: true,
                    prefixIcon:
                        const Icon(Icons.search, color: kNutral600),
                    enabledBorder: OutlineInputBorder(
                      borderSide:
                          const BorderSide(color: kOutlineColor),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderSide:
                          const BorderSide(color: kMainColor),
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: _statuses.map((s) {
                      final isSelected = _selectedStatus == s;
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: FilterChip(
                          selected: isSelected,
                          label: Text(
                            _getStatusText(s),
                            style: TextStyle(
                              color: isSelected
                                  ? Colors.white
                                  : kNutral700,
                              fontSize: 12,
                            ),
                          ),
                          onSelected: (v) {
                            setState(() {
                              _selectedStatus = v ? s : null;
                            });
                            _loadClaims();
                          },
                          selectedColor: _getStatusColor(s),
                          backgroundColor: Colors.white,
                          side: BorderSide(
                            color: isSelected
                                ? _getStatusColor(s)
                                : kOutlineColor,
                          ),
                          materialTapTargetSize:
                              MaterialTapTargetSize.shrinkWrap,
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: kMainColor))
                : _filteredClaims.isEmpty
                    ? const Center(child: Text('No claims found'))
                    : ListView.separated(
                        padding:
                            const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _filteredClaims.length,
                        separatorBuilder: (context, index) =>
                            const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final claim = _filteredClaims[index];
                          return _buildClaimCard(theme, claim);
                        },
                      ),
          ),
        ],
      ),
      flotingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await Navigator.pushNamed(context, '/add-edit-insurance-claim');
          _loadClaims();
        },
        backgroundColor: kMainColor,
        icon: const Icon(Icons.add),
        label: const Text('Add Claim'),
      ),
    );
  }

  Widget _buildClaimCard(
      ThemeData theme, InsuranceClaimModel claim) {
    final statusColor = _getStatusColor(claim.status);
    final dateFormat = DateFormat('MMM dd, yyyy');

    return Card(
      elevation: 1,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: kOutlineColor, width: 1),
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.all(12),
        leading: CircleAvatar(
          radius: 20,
          backgroundColor: statusColor.withValues(alpha: 0.1),
          child: Icon(Icons.receipt_long_outlined,
              color: statusColor, size: 20),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              claim.claimNumber ?? 'Unknown',
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              claim.policy?.policyNumber ?? 'No policy',
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: kNutral600, fontSize: 11),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text(
              '${dateFormat.format(DateTime.parse(claim.filingDate ?? DateTime.now().toString()))} • ${_formatCurrency(claim.claimedAmount)}',
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: kNutral700, fontSize: 12),
            ),
          ],
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: statusColor.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            _getStatusText(claim.status),
            style: TextStyle(
              color: statusColor,
              fontSize: 10,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, bottom: 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildDetailRow(theme, 'Claim Type', claim.claimType ?? 'N/A'),
                _buildDetailRow(
                    theme,
                    'Filing Date',
                    claim.filingDate != null
                        ? dateFormat
                            .format(DateTime.parse(claim.filingDate!))
                        : 'N/A'),
                _buildDetailRow(theme, 'Claimed Amount',
                    _formatCurrency(claim.claimedAmount)),
                _buildDetailRow(theme, 'Approved Amount',
                    _formatCurrency(claim.approvedAmount)),
                if (claim.resolutionDate != null)
                  _buildDetailRow(
                    theme,
                    'Resolution Date',
                    dateFormat.format(DateTime.parse(claim.resolutionDate!)),
                  ),
                if (claim.notes != null && claim.notes!.isNotEmpty)
                  _buildDetailRow(theme, 'Notes', claim.notes!),
                const SizedBox(height: 12),
                _buildClaimActions(theme, claim),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(ThemeData theme, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral600, fontSize: 11),
          ),
          Text(
            value,
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral800, fontSize: 11),
          ),
        ],
      ),
    );
  }

  Widget _buildClaimActions(ThemeData theme, InsuranceClaimModel claim) {
    final status = claim.status?.toLowerCase() ?? '';
    final repo = InsuranceRepo();

    return Wrap(
      spacing: 8,
      children: [
        if (status == 'pending')
          _buildActionBtn(theme, 'Submit', kMainColor, () async {
            EasyLoading.show(status: 'Submitting...');
            final result = await repo.submitClaim(claim.id!);
            EasyLoading.dismiss();
            if (result != null && mounted) {
              EasyLoading.showSuccess(result.message ?? 'Submitted');
              _loadClaims();
            } else {
              EasyLoading.showError('Failed to submit');
            }
          }),
        if (status == 'submitted') ...[
          _buildActionBtn(theme, 'Approve',
              const Color(0xFF43A047), () async {
            EasyLoading.show(status: 'Approving...');
            final result = await repo.approveClaim(claim.id!);
            EasyLoading.dismiss();
            if (result != null && mounted) {
              EasyLoading.showSuccess(result.message ?? 'Approved');
              _loadClaims();
            } else {
              EasyLoading.showError('Failed to approve');
            }
          }),
          _buildActionBtn(theme, 'Reject',
              const Color(0xFFE53935), () async {
            EasyLoading.show(status: 'Rejecting...');
            final result = await repo.rejectClaim(claim.id!);
            EasyLoading.dismiss();
            if (result != null && mounted) {
              EasyLoading.showSuccess(result.message ?? 'Rejected');
              _loadClaims();
            } else {
              EasyLoading.showError('Failed to reject');
            }
          }),
        ],
        if (status == 'approved')
          _buildActionBtn(theme, 'Pay',
              const Color(0xFF00987F), () async {
            EasyLoading.show(status: 'Paying...');
            final result = await repo.payClaim(claim.id!);
            EasyLoading.dismiss();
            if (result != null && mounted) {
              EasyLoading.showSuccess(result.message ?? 'Paid');
              _loadClaims();
            } else {
              EasyLoading.showError('Failed to pay');
            }
          }),
      ],
    );
  }

  Widget _buildActionBtn(
      ThemeData theme, String label, Color color, VoidCallback onTap) {
    return SizedBox(
      height: 32,
      child: TextButton.icon(
        onPressed: onTap,
        icon: const SizedBox.shrink(),
        label: Text(
          label,
          style: TextStyle(
            color: color,
            fontSize: 12,
            fontWeight: FontWeight.w600,
          ),
        ),
        style: TextButton.styleFrom(
          foregroundColor: color,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          minimumSize: Size.zero,
          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        ),
      ),
    );
  }

  String _formatCurrency(dynamic value) {
    if (value == null) return '0.00';
    final numValue = double.tryParse(value.toString()) ?? 0;
    return '\$${numValue.toStringAsFixed(2)}';
  }
}
