import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class AddEditInsurancePolicyScreen extends StatefulWidget {
  final InsurancePolicyModel? policy;

  const AddEditInsurancePolicyScreen({super.key, this.policy});

  @override
  State<AddEditInsurancePolicyScreen> createState() =>
      _AddEditInsurancePolicyScreenState();
}

class _AddEditInsurancePolicyScreenState
    extends State<AddEditInsurancePolicyScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  final _formKey = GlobalKey<FormState>();

  final _policyNumberController = TextEditingController();
  final _policyTypeController = TextEditingController();
  final _coverageTypeController = TextEditingController();
  final _coverageAmountController = TextEditingController();
  final _premiumAmountController = TextEditingController();
  final _startDateController = TextEditingController();
  final _endDateController = TextEditingController();
  final _notesController = TextEditingController();

  bool get isEditing => widget.policy != null;
  String _selectedStatus = 'active';
  int? _selectedCompanyId;

  List<InsuranceCompanyModel> _companies = [];
  bool _isLoadingCompanies = true;

  @override
  void initState() {
    super.initState();
    _loadCompanies();
    if (isEditing) {
      final p = widget.policy!;
      _policyNumberController.text = p.policyNumber ?? '';
      _policyTypeController.text = p.policyType ?? '';
      _coverageTypeController.text = p.coverageType ?? '';
      _coverageAmountController.text = p.coverageAmount?.toString() ?? '';
      _premiumAmountController.text = p.premiumAmount?.toString() ?? '';
      _startDateController.text = p.startDate ?? '';
      _endDateController.text = p.endDate ?? '';
      _notesController.text = p.notes ?? '';
      _selectedCompanyId = p.companyId;
      _selectedStatus = p.status ?? 'active';
    }
  }

  Future<void> _loadCompanies() async {
    setState(() => _isLoadingCompanies = true);
    final result = await _repo.getCompanies();
    if (mounted) {
      setState(() {
        _companies = result?.companies ?? [];
        _isLoadingCompanies = false;
      });
    }
  }

  Future<void> _selectDate(TextEditingController controller) async {
    final initialDate = controller.text.isNotEmpty
        ? DateTime.parse(controller.text)
        : DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      controller.text = DateFormat('yyyy-MM-dd').format(picked);
    }
  }

  Future<void> _savePolicy() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedCompanyId == null) {
      EasyLoading.showError('Please select a company');
      return;
    }

    EasyLoading.show(status: isEditing ? 'Updating...' : 'Creating...');

    final result = isEditing
        ? await _repo.updatePolicy(
            id: widget.policy!.id!,
            companyId: _selectedCompanyId!,
            policyNumber: _policyNumberController.text.trim(),
            policyType: _policyTypeController.text.trim(),
            coverageType: _coverageTypeController.text.trim(),
            coverageAmount:
                double.tryParse(_coverageAmountController.text) ?? 0,
            premiumAmount:
                double.tryParse(_premiumAmountController.text) ?? 0,
            startDate: _startDateController.text,
            endDate: _endDateController.text,
            status: _selectedStatus,
            notes: _notesController.text.trim(),
          )
        : await _repo.createPolicy(
            companyId: _selectedCompanyId!,
            policyNumber: _policyNumberController.text.trim(),
            policyType: _policyTypeController.text.trim(),
            coverageType: _coverageTypeController.text.trim(),
            coverageAmount:
                double.tryParse(_coverageAmountController.text) ?? 0,
            premiumAmount:
                double.tryParse(_premiumAmountController.text) ?? 0,
            startDate: _startDateController.text,
            endDate: _endDateController.text,
            status: _selectedStatus,
            notes: _notesController.text.trim(),
          );

    EasyLoading.dismiss();

    if (result != null && mounted) {
      EasyLoading.showSuccess(
        result.message ?? 'Policy ${isEditing ? 'updated' : 'created'}',
      );
      Navigator.pop(context, true);
    } else {
      EasyLoading.showError(
        'Failed to ${isEditing ? 'update' : 'create'} policy',
      );
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
          isEditing ? 'Edit Policy' : 'Add Policy',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          TextButton(
            onPressed: _savePolicy,
            child: const Text('Save', style: TextStyle(color: kWhite)),
          ),
        ],
      ),
      body: _isLoadingCompanies
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildCompanySelector(theme),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Policy Number',
                      'Enter policy number',
                      _policyNumberController,
                      Icons.numbers,
                    ),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Policy Type',
                      'e.g., Comprehensive, Third-party',
                      _policyTypeController,
                      Icons.category_outlined,
                    ),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Coverage Type',
                      'e.g., Pharmacy, Medical, Dental',
                      _coverageTypeController,
                      Icons.medical_services_outlined,
                    ),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Coverage Amount',
                      'Enter coverage amount',
                      _coverageAmountController,
                      Icons.account_balance_wallet_outlined,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      prefix: const Text('\$ '),
                    ),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Premium Amount',
                      'Enter premium amount',
                      _premiumAmountController,
                      Icons.payments_outlined,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      prefix: const Text('\$ '),
                    ),
                    const SizedBox(height: 16),
                    _buildDatePicker(
                      theme,
                      'Start Date',
                      _startDateController,
                    ),
                    const SizedBox(height: 16),
                    _buildDatePicker(
                      theme,
                      'End Date',
                      _endDateController,
                    ),
                    const SizedBox(height: 16),
                    _buildStatusSelector(theme),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Notes',
                      'Enter notes (optional)',
                      _notesController,
                      Icons.note_outlined,
                      maxLines: 3,
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildCompanySelector(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Insurance Company',
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        DropdownButtonFormField<int>(
          value: _selectedCompanyId,
          decoration: InputDecoration(
            hintText: 'Select company',
            prefixIcon:
                const Icon(Icons.business_outlined, color: kNutral700),
            border: OutlineInputBorder(
              borderSide: const BorderSide(color: kOutlineColor),
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          items: _companies
              .map((c) => DropdownMenuItem(
                    value: c.id,
                    child: Text(c.name ?? 'Unknown'),
                  ))
              .toList(),
          onChanged: (v) => setState(() => _selectedCompanyId = v),
          validator: (v) => v == null ? 'Company is required' : null,
        ),
      ],
    );
  }

  Widget _buildTextField(
    ThemeData theme,
    String label,
    String hint,
    TextEditingController controller,
    IconData icon, {
    TextInputType? keyboardType,
    int maxLines = 1,
    Widget? prefix,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          maxLines: maxLines,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: kNutral600, fontSize: 13),
            fillColor: Colors.white,
            filled: true,
            prefixIcon: Icon(icon, color: kNutral700, size: 18),
            prefix: prefix,
            enabledBorder: OutlineInputBorder(
              borderSide: const BorderSide(color: kOutlineColor),
              borderRadius: BorderRadius.circular(12),
            ),
            focusedBorder: OutlineInputBorder(
              borderSide: const BorderSide(color: kMainColor),
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          validator: (value) {
            if (value == null || value.trim().isEmpty) {
              return '$label is required';
            }
            return null;
          },
        ),
      ],
    );
  }

  Widget _buildDatePicker(
    ThemeData theme,
    String label,
    TextEditingController controller,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        TextFormField(
          controller: controller,
          decoration: InputDecoration(
            hintText: 'Select date',
            hintStyle: const TextStyle(color: kNutral600, fontSize: 13),
            fillColor: Colors.white,
            filled: true,
            prefixIcon:
                const Icon(Icons.calendar_today_outlined, color: kNutral700),
            enabledBorder: OutlineInputBorder(
              borderSide: const BorderSide(color: kOutlineColor),
              borderRadius: BorderRadius.circular(12),
            ),
            focusedBorder: OutlineInputBorder(
              borderSide: const BorderSide(color: kMainColor),
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          readOnly: true,
          onTap: () => _selectDate(controller),
        ),
      ],
    );
  }

  Widget _buildStatusSelector(ThemeData theme) {
    final statuses = ['active', 'pending', 'expired'];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Status',
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        DropdownButtonFormField<String>(
          value: _selectedStatus,
          decoration: InputDecoration(
            hintText: 'Select status',
            border: OutlineInputBorder(
              borderSide: const BorderSide(color: kOutlineColor),
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          items: statuses
              .map((s) => DropdownMenuItem(
                    value: s,
                    child: Text(
                      s[0].toUpperCase() + s.substring(1),
                      style: TextStyle(
                        color: _getStatusColor(s),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ))
              .toList(),
          onChanged: (v) => setState(() => _selectedStatus = v ?? 'active'),
        ),
      ],
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
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
}
