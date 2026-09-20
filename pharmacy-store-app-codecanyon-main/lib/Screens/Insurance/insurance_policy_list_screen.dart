import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class InsurancePolicyListScreen extends StatefulWidget {
  const InsurancePolicyListScreen({super.key});

  @override
  State<InsurancePolicyListScreen> createState() =>
      _InsurancePolicyListScreenState();
}

class _InsurancePolicyListScreenState extends State<InsurancePolicyListScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  List<InsurancePolicyModel> _policies = [];
  bool _isLoading = true;
  String _search = '';
  String? _selectedStatus;

  final List<String> _statuses = ['active', 'expired', 'pending'];

  @override
  void initState() {
    super.initState();
    _loadPolicies();
  }

  Future<void> _loadPolicies() async {
    setState(() => _isLoading = true);
    final result = await _repo.getPolicies(status: _selectedStatus);
    if (mounted) {
      setState(() {
        _policies = result?.policies ?? [];
        _isLoading = false;
      });
    }
  }

  List<InsurancePolicyModel> get _filteredPolicies {
    if (_search.isEmpty && _selectedStatus == null) return _policies;
    return _policies.where((p) {
      final matchesSearch = _search.isEmpty ||
          (p.policyNumber ?? '')
              .toLowerCase()
              .contains(_search.toLowerCase()) ||
          (p.company?.name ?? '')
              .toLowerCase()
              .contains(_search.toLowerCase());
      return matchesSearch;
    }).toList();
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

  bool _isExpired(InsurancePolicyModel policy) {
    if (policy.endDate == null) return false;
    try {
      final end = DateTime.parse(policy.endDate!);
      return end.isBefore(DateTime.now());
    } catch (e) {
      return false;
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
          'Insurance Policies',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: kWhite),
            onPressed: _loadPolicies,
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
                    hintText: 'Search policies...',
                    hintStyle: const TextStyle(color: kNutral600),
                    fillColor: Colors.white,
                    filled: true,
                    prefixIcon: const Icon(Icons.search, color: kNutral600),
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
                            _loadPolicies();
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
                : _filteredPolicies.isEmpty
                    ? const Center(child: Text('No policies found'))
                    : ListView.separated(
                        padding:
                            const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _filteredPolicies.length,
                        separatorBuilder: (context, index) =>
                            const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final policy = _filteredPolicies[index];
                          return _buildPolicyCard(theme, policy);
                        },
                      ),
          ),
        ],
      ),
      flotingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await Navigator.pushNamed(context, '/add-edit-insurance-policy');
          _loadPolicies();
        },
        backgroundColor: kMainColor,
        icon: const Icon(Icons.add),
        label: const Text('Add Policy'),
      ),
    );
  }

  Widget _buildPolicyCard(
      ThemeData theme, InsurancePolicyModel policy) {
    final now = DateTime.now();
    final isExpired = policy.endDate != null
        ? DateTime.tryParse(policy.endDate!)?.isBefore(now) ?? false
        : false;
    final expiryDate = policy.endDate != null
        ? DateTime.tryParse(policy.endDate!)
        : null;
    final daysUntilExpiry =
        expiryDate != null ? expiryDate.difference(now).inDays : null;

    Color statusColor = _getStatusColor(policy.status);
    if (isExpired) statusColor = const Color(0xFFE53935);

    return Card(
      elevation: 1,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: kOutlineColor, width: 1),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.all(12),
        leading: CircleAvatar(
          radius: 20,
          backgroundColor: statusColor.withValues(alpha: 0.1),
          child: Icon(Icons.document_scanner_outlined,
              color: statusColor, size: 20),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              policy.policyNumber ?? 'Unknown',
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              policy.company?.name ?? 'No company',
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: kNutral600, fontSize: 11),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (daysUntilExpiry != null && !isExpired)
              Text(
                'Expires in $daysUntilExpiry days',
                style: TextStyle(
                  color: daysUntilExpiry! <= 30
                      ? const Color(0xFFFF9800)
                      : kNutral600,
                  fontSize: 11,
                  fontWeight:
                      daysUntilExpiry! <= 30 ? FontWeight.w600 : null,
                ),
              ),
            Text(
              '${policy.coverageType ?? ''} • ${DateFormat('MMM dd, yyyy').format(DateTime.parse(policy.startDate ?? DateTime.now().toString()))} - ${policy.endDate ?? ''}',
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: kNutral600, fontSize: 11),
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
            isExpired ? 'EXPIRED' : _getStatusText(policy.status),
            style: TextStyle(
              color: statusColor,
              fontSize: 10,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/insurance-policy-detail',
            arguments: policy.id,
          );
          _loadPolicies();
        },
      ),
    );
  }
}
