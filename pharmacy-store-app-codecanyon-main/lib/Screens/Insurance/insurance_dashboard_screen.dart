import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class InsuranceDashboardScreen extends StatefulWidget {
  const InsuranceDashboardScreen({super.key});

  @override
  State<InsuranceDashboardScreen> createState() =>
      _InsuranceDashboardScreenState();
}

class _InsuranceDashboardScreenState extends State<InsuranceDashboardScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  InsuranceDashboardResponse? _dashboard;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() => _isLoading = true);
    final result = await _repo.getDashboard();
    if (mounted) {
      setState(() {
        _dashboard = result;
        _isLoading = false;
      });
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
          'Insurance Dashboard',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: kWhite),
            onPressed: _loadDashboard,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : _dashboard == null
              ? const Center(child: Text('Failed to load dashboard'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Summary Cards
                      _buildSummaryCards(theme),
                      const SizedBox(height: 24),

                      // Claims by Status
                      _buildClaimsChart(theme),
                      const SizedBox(height: 24),

                      // Expiring Policies
                      _buildExpiringPolicies(theme),
                      const SizedBox(height: 24),

                      // Quick Actions
                      _buildQuickActions(theme),
                    ],
                  ),
                ),
    );
  }

  Widget _buildSummaryCards(ThemeData theme) {
    final d = _dashboard!;

    return GridView.count(
      crossAxisCount: 2,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      childAspectRatio: 1.3,
      children: [
        _buildStatCard(
          theme,
          'Companies',
          d.companiesCount?.toString() ?? '0',
          Icons.business_outlined,
          kMainColor,
        ),
        _buildStatCard(
          theme,
          'Policies',
          d.policiesCount?.toString() ?? '0',
          Icons.document_scanner_outlined,
          const Color(0xFF1565C0),
        ),
        _buildStatCard(
          theme,
          'Active',
          d.activePoliciesCount?.toString() ?? '0',
          Icons.check_circle_outline,
          const Color(0xFF43A047),
        ),
        _buildStatCard(
          theme,
          'Expired',
          d.expiredPoliciesCount?.toString() ?? '0',
          Icons.error_outline,
          const Color(0xFFE53935),
        ),
        _buildStatCard(
          theme,
          'Claims',
          d.claimsCount?.toString() ?? '0',
          Icons.receipt_long_outlined,
          const Color(0xFFFF9800),
        ),
        _buildStatCard(
          theme,
          'Pending Claims',
          d.pendingClaimsCount?.toString() ?? '0',
          Icons.pending_outlined,
          const Color(0xFF9C27B0),
        ),
      ],
    );
  }

  Widget _buildStatCard(
    ThemeData theme,
    String label,
    String value,
    IconData icon,
    Color color,
  ) {
    return Container(
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
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircleAvatar(
            radius: 18,
            backgroundColor: color.withValues(alpha: 0.15),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
              fontSize: 20,
              color: kNutral800,
            ),
          ),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: kNutral600,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildClaimsChart(ThemeData theme) {
    final d = _dashboard!;
    final pending = d.pendingClaimsCount?.toDouble() ?? 0;
    final approved = d.approvedClaimsCount?.toDouble() ?? 0;
    final rejected = d.rejectedClaimsCount?.toDouble() ?? 0;
    final total = pending + approved + rejected;

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
            'Claims by Status',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: kNutral800,
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 140,
            child: PieChart(
              PieChartData(
                sections: [
                  if (pending > 0)
                    PieChartSectionData(
                      color: const Color(0xFFFF9800),
                      value: pending,
                      title: '${total > 0 ? (pending / total * 100).round() : 0}%',
                      titleStyle: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 10,
                      ),
                      radius: 24,
                    ),
                  if (approved > 0)
                    PieChartSectionData(
                      color: const Color(0xFF43A047),
                      value: approved,
                      title:
                          '${total > 0 ? (approved / total * 100).round() : 0}%',
                      titleStyle: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 10,
                      ),
                      radius: 24,
                    ),
                  if (rejected > 0)
                    PieChartSectionData(
                      color: const Color(0xFFE53935),
                      value: rejected,
                      title:
                          '${total > 0 ? (rejected / total * 100).round() : 0}%',
                      titleStyle: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 10,
                      ),
                      radius: 24,
                    ),
                ],
                borderData: FlBorderData(show: false),
                centerText:
                    '${d.claimsCount?.toString() ?? '0'}\nClaims',
                centerTextStyle: const TextStyle(
                  color: kNutral800,
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
                sectionsSpace: 2,
              ),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _buildLegendItem(theme, 'Pending', pending.toInt(),
                  const Color(0xFFFF9800)),
              _buildLegendItem(theme, 'Approved', approved.toInt(),
                  const Color(0xFF43A047)),
              _buildLegendItem(theme, 'Rejected', rejected.toInt(),
                  const Color(0xFFE53935)),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Total Coverage: ${_formatCurrency(d.totalCoverageAmount)} | Total Claims: ${_formatCurrency(d.totalClaimsAmount)}',
            style: theme.textTheme.bodySmall?.copyWith(
              color: kNutral600,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem(
      ThemeData theme, String label, int value, Color color) {
    return Row(
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 4),
        Text(
          '$label: $value',
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontSize: 10,
          ),
        ),
      ],
    );
  }

  Widget _buildExpiringPolicies(ThemeData theme) {
    final policies = _dashboard?.expiringPolicies ?? [];
    if (policies.isEmpty) return const SizedBox.shrink();

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
            'Expiring Policies',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: kNutral800,
            ),
          ),
          const SizedBox(height: 8),
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: policies.length,
            separatorBuilder: (context, index) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final p = policies[index];
              return ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                leading: CircleAvatar(
                  radius: 16,
                  backgroundColor: const Color(0xFFFF9800).withValues(alpha: 0.15),
                  child: const Icon(Icons.warning_rounded,
                      color: Color(0xFFFF9800), size: 18),
                ),
                title: Text(
                  p.policyNumber ?? 'Unknown',
                  style: const TextStyle(fontSize: 13),
                ),
                subtitle: Text(
                  '${p.companyName ?? ''} • Expires: ${p.endDate ?? ''}',
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: kNutral600, fontSize: 11),
                ),
                trailing: Text(
                  _formatCurrency(p.coverageAmount),
                  style: theme.textTheme.bodySmall
                      ?.copyWith(fontWeight: FontWeight.w600, fontSize: 12),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions(ThemeData theme) {
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
            'Quick Actions',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: kNutral800,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              _buildActionChip(
                theme,
                'Companies',
                Icons.business_outlined,
                kMainColor,
                () => Navigator.pushNamed(context, '/insurance-companies'),
              ),
              _buildActionChip(
                theme,
                'Policies',
                Icons.document_scanner_outlined,
                const Color(0xFF1565C0),
                () => Navigator.pushNamed(context, '/insurance-policies'),
              ),
              _buildActionChip(
                theme,
                'Claims',
                Icons.receipt_long_outlined,
                const Color(0xFFFF9800),
                () => Navigator.pushNamed(context, '/insurance-claims'),
              ),
              _buildActionChip(
                theme,
                'Coverage',
                Icons.shield_outlined,
                const Color(0xFF43A047),
                () => Navigator.pushNamed(context, '/insurance-coverages'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildActionChip(
    ThemeData theme,
    String label,
    IconData icon,
    Color color,
    VoidCallback onTap,
  ) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.3), width: 1),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(height: 4),
            Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(
                color: color,
                fontWeight: FontWeight.w600,
                fontSize: 11,
              ),
            ),
          ],
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
