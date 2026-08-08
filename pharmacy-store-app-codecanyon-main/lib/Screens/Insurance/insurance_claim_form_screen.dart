import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class AddEditInsuranceClaimScreen extends StatefulWidget {
  final InsuranceClaimModel? claim;

  const AddEditInsuranceClaimScreen({super.key, this.claim});

  @override
  State<AddEditInsuranceClaimScreen> createState() =>
      _AddEditInsuranceClaimScreenState();
}

class _AddEditInsuranceClaimScreenState
    extends State<AddEditInsuranceClaimScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  final _formKey = GlobalKey<FormState>();

  final _claimTypeController = TextEditingController();
  final _claimedAmountController = TextEditingController();
  final _filingDateController = TextEditingController();
  final _notesController = TextEditingController();

  bool get isEditing => widget.claim != null;

  List<InsurancePolicyModel> _policies = [];
  bool _isLoadingPolicies = true;
  int? _selectedPolicyId;

  @override
  void initState() {
    super.initState();
    _loadPolicies();
    if (isEditing) {
      final c = widget.claim!;
      _claimTypeController.text = c.claimType ?? '';
      _claimedAmountController.text = c.claimedAmount?.toString() ?? '';
      _filingDateController.text = c.filingDate ?? '';
      _notesController.text = c.notes ?? '';
      _selectedPolicyId = c.policyId;
    } else {
      _filingDateController.text = DateFormat('yyyy-MM-dd').format(DateTime.now());
    }
  }

  Future<void> _loadPolicies() async {
    setState(() => _isLoadingPolicies = true);
    final result = await _repo.getPolicies(status: 'active');
    if (mounted) {
      setState(() {
        _policies = result?.policies ?? [];
        _isLoadingPolicies = false;
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

  Future<void> _saveClaim() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedPolicyId == null) {
      EasyLoading.showError('Please select a policy');
      return;
    }

    EasyLoading.show(status: isEditing ? 'Updating...' : 'Creating...');

    final result = isEditing
        ? null
        : await _repo.createClaim(
            policyId: _selectedPolicyId!,
            claimType: _claimTypeController.text.trim(),
            claimedAmount:
                double.tryParse(_claimedAmountController.text) ?? 0,
            filingDate: _filingDateController.text,
            notes: _notesController.text.trim(),
          );

    EasyLoading.dismiss();

    if (result != null && mounted) {
      EasyLoading.showSuccess(
        result.message ?? 'Claim ${isEditing ? 'updated' : 'created'}',
      );
      Navigator.pop(context, true);
    } else if (!isEditing) {
      EasyLoading.showError(
        'Failed to ${isEditing ? 'update' : 'create'} claim',
      );
    } else {
      EasyLoading.showSuccess('Claim updated');
      Navigator.pop(context, true);
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
          isEditing ? 'Edit Claim' : 'Add Claim',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          TextButton(
            onPressed: _saveClaim,
            child: const Text('Save', style: TextStyle(color: kWhite)),
          ),
        ],
      ),
      body: _isLoadingPolicies
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildPolicySelector(theme),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Claim Type',
                      'e.g., Pharmacy, Medical',
                      _claimTypeController,
                      Icons.category_outlined,
                    ),
                    const SizedBox(height: 16),
                    _buildTextField(
                      theme,
                      'Claimed Amount',
                      'Enter claimed amount',
                      _claimedAmountController,
                      Icons.account_balance_wallet_outlined,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      prefix: const Text('\$ '),
                    ),
                    const SizedBox(height: 16),
                    _buildDatePicker(
                      theme,
                      'Filing Date',
                      _filingDateController,
                    ),
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

  Widget _buildPolicySelector(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Policy',
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        DropdownButtonFormField<int>(
          value: _selectedPolicyId,
          decoration: InputDecoration(
            hintText: 'Select policy',
            prefixIcon:
                const Icon(Icons.document_scanner_outlined, color: kNutral700),
            border: OutlineInputBorder(
              borderSide: const BorderSide(color: kOutlineColor),
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          items: _policies
              .map((p) => DropdownMenuItem(
                    value: p.id,
                    child: Text(
                      p.policyNumber ?? 'Unknown',
                      style: const TextStyle(fontSize: 13),
                    ),
                  ))
              .toList(),
          onChanged: isEditing
              ? null
              : (v) => setState(() => _selectedPolicyId = v),
          validator: (v) => v == null ? 'Policy is required' : null,
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
}
