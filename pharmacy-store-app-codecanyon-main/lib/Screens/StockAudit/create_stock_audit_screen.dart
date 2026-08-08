import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/StockAudit/repo/stock_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:shared_preferences/shared_preferences.dart';

class CreateStockAuditScreen extends StatefulWidget {
  const CreateStockAuditScreen({super.key});

  @override
  State<CreateStockAuditScreen> createState() => _CreateStockAuditScreenState();
}

class _CreateStockAuditScreenState extends State<CreateStockAuditScreen> {
  final StockAuditRepo _repo = StockAuditRepo();
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _notesController = TextEditingController();

  String? _selectedAuditType;
  int? _businessId;

  final List<Map<String, String>> _auditTypes = [
    {'value': 'full', 'label': 'Full Inventory'},
    {'value': 'partial', 'label': 'Partial'},
    {'value': 'cycle', 'label': 'Cycle Count'},
    {'value': 'spot_check', 'label': 'Spot Check'},
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

  Future<void> _createAudit() async {
    if (_formKey.currentState?.validate() != true) return;
    if (_selectedAuditType == null) {
      EasyLoading.showError('Please select an audit type');
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
          'Create Stock Audit',
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
                        _selectedAuditType =
                            selected ? type['value'] : null;
                      });
                    },
                    selectedColor: kMainColor,
                    backgroundColor: Colors.white,
                    side: BorderSide(
                      color: isSelected ? kMainColor : kOutlineColor,
                    ),
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  );
                }).toList(),
              ),
              const SizedBox(height: 24),

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
