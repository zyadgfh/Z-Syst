import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/FinancialAudit/repo/financial_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:shared_preferences/shared_preferences.dart';

class CreateFinancialAuditScreen extends StatefulWidget {
  const CreateFinancialAuditScreen({super.key});

  @override
  State<CreateFinancialAuditScreen> createState() =>
      _CreateFinancialAuditScreenState();
}

class _CreateFinancialAuditScreenState extends State<CreateFinancialAuditScreen> {
  final FinancialAuditRepo _repo = FinancialAuditRepo();
  final _formKey = GlobalKey<FormState>();

  final TextEditingController _startDateController = TextEditingController();
  final TextEditingController _endDateController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();

  String? _selectedAuditType;
  int? _businessId;
  DateTime? _startDate;
  DateTime? _endDate;

  final List<Map<String, String>> _auditTypes = [
    {'value': 'monthly', 'label': 'Monthly'},
    {'value': 'quarterly', 'label': 'Quarterly'},
    {'value': 'yearly', 'label': 'Yearly'},
    {'value': 'custom', 'label': 'Custom'},
  ];

  @override
  void initState() {
    super.initState();
    _loadBusinessId();
  }

  Future<void> _loadBusinessId() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _businessId = prefs.getInt('business_id');
    });
  }

  Future<void> _selectDate(BuildContext context, bool isStart) async {
    final initialDate = DateTime.now();
    final firstDate = DateTime(2020);
    final lastDate = DateTime(2030);

    final picked = await showDatePicker(
      context: context,
      initialDate: isStart ? (_startDate ?? initialDate) : (_endDate ?? initialDate),
      firstDate: firstDate,
      lastDate: lastDate,
    );

    if (picked != null) {
      setState(() {
        if (isStart) {
          _startDate = picked;
          _startDateController.text = DateFormat.yMMMd().format(picked);
        } else {
          _endDate = picked;
          _endDateController.text = DateFormat.yMMMd().format(picked);
        }
      });
    }
  }

  Future<void> _createAudit() async {
    if (_formKey.currentState?.validate() != true) return;
    if (_selectedAuditType == null) {
      EasyLoading.showError('Please select an audit type');
      return;
    }
    if (_startDate == null || _endDate == null) {
      EasyLoading.showError('Please select start and end dates');
      return;
    }
    if (_businessId == null) {
      EasyLoading.showError('Business ID not found');
      return;
    }

    EasyLoading.show(status: 'Creating audit...');

    final result = await _repo.createAudit(
      businessId: _businessId!,
      auditType: _selectedAuditType!,
      startDate: DateFormat('yyyy-MM-dd').format(_startDate!),
      endDate: DateFormat('yyyy-MM-dd').format(_endDate!),
      notes: _notesController.text.isNotEmpty ? _notesController.text : null,
    );

    EasyLoading.dismiss();

    if (result != null && result.audit != null) {
      EasyLoading.showSuccess(result.message ?? 'Audit created successfully');
      Navigator.pop(context, true);
    } else {
      EasyLoading.showError('Failed to create audit');
    }
  }

  @override
  void dispose() {
    _startDateController.dispose();
    _endDateController.dispose();
    _notesController.dispose();
    super.dispose();
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
          'Create Financial Audit',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Audit Type
              Text(
                'Audit Type',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: kNutral700,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: _auditTypes.map((type) {
                  final isSelected = _selectedAuditType == type['value'];
                  return ChoiceChip(
                    label: Text(
                      type['label']!,
                      style: TextStyle(
                        color: isSelected ? Colors.white : kNutral700,
                        fontSize: 13,
                        fontWeight:
                            isSelected ? FontWeight.w600 : FontWeight.normal,
                      ),
                    ),
                    selected: isSelected,
                    onSelected: (selected) {
                      setState(() {
                        _selectedAuditType = selected ? type['value'] : null;
                      });
                    },
                    selectedColor: kMainColor,
                    backgroundColor: Colors.white,
                    side: BorderSide(
                      color: isSelected ? kMainColor : kOutlineColor,
                    ),
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  );
                }),
              ),
              const SizedBox(height: 24),

              // Start Date
              Text(
                'Start Date',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: kNutral700,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _startDateController,
                readOnly: true,
                onTap: () => _selectDate(context, true),
                decoration: InputDecoration(
                  hintText: 'Select start date',
                  fillColor: Colors.white,
                  filled: true,
                  prefixIcon:
                      const Icon(Icons.calendar_today_outlined, color: kNutral700),
                  enabledBorder: OutlineInputBorder(
                    borderSide: BorderSide(color: kOutlineColor),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: BorderSide(color: kMainColor),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Start date is required';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 24),

              // End Date
              Text(
                'End Date',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: kNutral700,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _endDateController,
                readOnly: true,
                onTap: () => _selectDate(context, false),
                decoration: InputDecoration(
                  hintText: 'Select end date',
                  fillColor: Colors.white,
                  filled: true,
                  prefixIcon:
                      const Icon(Icons.calendar_today_outlined, color: kNutral700),
                  enabledBorder: OutlineInputBorder(
                    borderSide: BorderSide(color: kOutlineColor),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: BorderSide(color: kMainColor),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'End date is required';
                  }
                  if (_startDate != null &&
                      _endDate != null &&
                      _endDate!.isBefore(_startDate!)) {
                    return 'End date must be after start date';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 24),

              // Notes
              Text(
                'Notes',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: kNutral700,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _notesController,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: 'Enter audit notes (optional)',
                  fillColor: Colors.white,
                  filled: true,
                  enabledBorder: OutlineInputBorder(
                    borderSide: BorderSide(color: kOutlineColor),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: BorderSide(color: kMainColor),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
              const SizedBox(height: 32),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _createAudit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: kMainColor,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text(
                    'Create Audit',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
