import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class InsuranceCoverageListScreen extends StatefulWidget {
  const InsuranceCoverageListScreen({super.key});

  @override
  State<InsuranceCoverageListScreen> createState() =>
      _InsuranceCoverageListScreenState();
}

class _InsuranceCoverageListScreenState
    extends State<InsuranceCoverageListScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  List<InsuranceCoverageModel> _coverages = [];
  bool _isLoading = true;
  String _search = '';
  String? _selectedType;

  final List<String> _coverageTypes = [
    'pharmacy',
    'medical',
    'dental',
    'vision',
    'mental_health',
  ];

  @override
  void initState() {
    super.initState();
    _loadCoverages();
  }

  Future<void> _loadCoverages() async {
    setState(() => _isLoading = true);
    final result = await _repo.getCoverages();
    if (mounted) {
      setState(() {
        _coverages = result?.coverages ?? [];
        _isLoading = false;
      });
    }
  }

  List<InsuranceCoverageModel> get _filteredCoverages {
    var result = _coverages;
    if (_search.isNotEmpty) {
      result = result
          .where((c) =>
              (c.coverageType ?? '')
                  .toLowerCase()
                  .contains(_search.toLowerCase()) ||
              (c.policy?.policyNumber ?? '')
                  .toLowerCase()
                  .contains(_search.toLowerCase()))
          .toList();
    }
    return result;
  }

  String _getTypeLabel(String? type) {
    if (type == null) return 'Unknown';
    return type
        .replaceAll('_', ' ')
        .split(' ')
        .map((w) => w.isNotEmpty
            ? '${w[0].toUpperCase()}${w.substring(1)}'
            : '')
        .join(' ');
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
          'Insurance Coverage',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: kWhite),
            onPressed: _loadCoverages,
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              onChanged: (v) => setState(() => _search = v),
              decoration: InputDecoration(
                hintText: 'Search coverages...',
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
          ),
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: kMainColor))
                : _filteredCoverages.isEmpty
                    ? const Center(child: Text('No coverages found'))
                    : ListView.separated(
                        padding:
                            const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _filteredCoverages.length,
                        separatorBuilder: (context, index) =>
                            const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final coverage = _filteredCoverages[index];
                          return _buildCoverageCard(theme, coverage);
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildCoverageCard(
      ThemeData theme, InsuranceCoverageModel coverage) {
    return Card(
      elevation: 1,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: kOutlineColor, width: 1),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.all(16),
        leading: CircleAvatar(
          radius: 20,
          backgroundColor: kMainColor.withValues(alpha: 0.1),
          child: const Icon(Icons.shield_outlined,
              color: kMainColor, size: 20),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _getTypeLabel(coverage.coverageType),
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              'Policy: ${coverage.policy?.policyNumber ?? 'N/A'}',
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: kNutral600, fontSize: 11),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            _buildCoverageDetail(theme, 'Limit',
                _formatCurrency(coverage.coverageLimit)),
            _buildCoverageDetail(theme, 'Deductible',
                _formatCurrency(coverage.deductible)),
            _buildCoverageDetail(theme, 'Copay',
                _formatCurrency(coverage.copay)),
          ],
        ),
      ),
    );
  }

  Widget _buildCoverageDetail(ThemeData theme, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            '$label:',
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral600, fontSize: 11),
          ),
          Text(
            value,
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral800, fontSize: 11, fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }

  String _formatCurrency(dynamic value) {
    if (value == null) return 'N/A';
    final numValue = double.tryParse(value.toString()) ?? 0;
    return '\$${numValue.toStringAsFixed(2)}';
  }
}
