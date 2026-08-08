import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class InsuranceCompanyListScreen extends StatefulWidget {
  const InsuranceCompanyListScreen({super.key});

  @override
  State<InsuranceCompanyListScreen> createState() =>
      _InsuranceCompanyListScreenState();
}

class _InsuranceCompanyListScreenState
    extends State<InsuranceCompanyListScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  List<InsuranceCompanyModel> _companies = [];
  bool _isLoading = true;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _loadCompanies();
  }

  Future<void> _loadCompanies() async {
    setState(() => _isLoading = true);
    final result = await _repo.getCompanies();
    if (mounted) {
      setState(() {
        _companies = result?.companies ?? [];
        _isLoading = false;
      });
    }
  }

  Future<void> _deleteCompany(int id) async {
    EasyLoading.show(status: 'Deleting...');
    final result = await _repo.deleteCompany(id);
    EasyLoading.dismiss();

    if (result != null && mounted) {
      EasyLoading.showSuccess(result.message ?? 'Deleted');
      _loadCompanies();
    } else {
      EasyLoading.showError('Failed to delete');
    }
  }

  void _showDeleteConfirmation(InsuranceCompanyModel company) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Company'),
        content: Text(
            'Are you sure you want to delete "${company.name}"? This action cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _deleteCompany(company.id!);
            },
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  List<InsuranceCompanyModel> get _filteredCompanies {
    if (_search.isEmpty) return _companies;
    return _companies
        .where((c) =>
            (c.name ?? '').toLowerCase().contains(_search.toLowerCase()) ||
            (c.email ?? '')
                .toLowerCase()
                .contains(_search.toLowerCase()) ||
            (c.contactPerson ?? '')
                .toLowerCase()
                .contains(_search.toLowerCase()))
        .toList();
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
          'Insurance Companies',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: kWhite),
            onPressed: _loadCompanies,
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
                hintText: 'Search companies...',
                hintStyle: const TextStyle(color: kNutral600),
                fillColor: Colors.white,
                filled: true,
                prefixIcon: const Icon(Icons.search, color: kNutral600),
                enabledBorder: OutlineInputBorder(
                  borderSide: const BorderSide(color: kOutlineColor),
                  borderRadius: BorderRadius.circular(12),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: const BorderSide(color: kMainColor),
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: kMainColor))
                : _filteredCompanies.isEmpty
                    ? Center(child: Text('No companies found'))
                    : ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _filteredCompanies.length,
                        separatorBuilder: (context, index) =>
                            const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final company = _filteredCompanies[index];
                          return _buildCompanyCard(theme, company);
                        },
                      ),
          ),
        ],
      ),
      flotingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await Navigator.pushNamed(context, '/add-edit-insurance-company');
          _loadCompanies();
        },
        backgroundColor: kMainColor,
        icon: const Icon(Icons.add),
        label: const Text('Add Company'),
      ),
    );
  }

  Widget _buildCompanyCard(
      ThemeData theme, InsuranceCompanyModel company) {
    return Dismissible(
      key: Key(company.id.toString()),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        decoration: BoxDecoration(
          color: const Color(0xFFE53935),
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Icon(Icons.delete, color: Colors.white),
      ),
      onDismissed: (direction) => _deleteCompany(company.id!),
      child: Card(
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
            backgroundColor: kMainColor.withValues(alpha: 0.1),
            child: const Icon(Icons.business_outlined,
                color: kMainColor, size: 20),
          ),
          title: Text(
            company.name ?? 'Unknown',
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
          ),
          subtitle: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (company.email != null && company.email!.isNotEmpty)
                Text(
                  company.email!,
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: kNutral600, fontSize: 11),
                ),
              if (company.phoneNumber != null &&
                  company.phoneNumber!.isNotEmpty)
                Text(
                  company.phoneNumber!,
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: kNutral600, fontSize: 11),
                ),
            ],
          ),
          isThreeLine: true,
          trailing: IconButton(
            icon: const Icon(Icons.more_vert, color: kNutral600),
            onPressed: () {
              _showCompanyActions(context, company);
            },
          ),
        ),
      ),
    );
  }

  void _showCompanyActions(
      BuildContext context, InsuranceCompanyModel company) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: kOutlineColor,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 16),
            ListTile(
              leading: const Icon(Icons.edit, color: kMainColor),
              title: const Text('Edit'),
              onTap: () async {
                Navigator.pop(context);
                await Navigator.pushNamed(
                  context,
                  '/add-edit-insurance-company',
                  arguments: company,
                );
                _loadCompanies();
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete, color: Color(0xFFE53935)),
              title: const Text('Delete',
                  style: TextStyle(color: Color(0xFFE53935))),
              onTap: () {
                Navigator.pop(context);
                _showDeleteConfirmation(company);
              },
            ),
          ],
        ),
      ),
    );
  }
}
