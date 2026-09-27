import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/StockAudit/repo/stock_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class StockAuditItemFormScreen extends StatefulWidget {
  final int auditId;

  const StockAuditItemFormScreen({super.key, required this.auditId});

  @override
  State<StockAuditItemFormScreen> createState() =>
      _StockAuditItemFormScreenState();
}

class _StockAuditItemFormScreenState extends State<StockAuditItemFormScreen> {
  final StockAuditRepo _repo = StockAuditRepo();
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _productIdController = TextEditingController();
  final TextEditingController _physicalQuantityController =
      TextEditingController();
  final TextEditingController _notesController = TextEditingController();

  int? _selectedProductId;
  String _productName = '';
  int? _selectedStockId;
  String _batchNo = '';
  String _expireDate = '';
  int _systemQuantity = 0;
  String _unitCost = '';

  bool _isLoadingProduct = false;

  @override
  void dispose() {
    _productIdController.dispose();
    _physicalQuantityController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _saveItem() async {
    if (_formKey.currentState?.validate() != true) return;
    if (_selectedProductId == null) {
      EasyLoading.showError('Please select a product');
      return;
    }

    EasyLoading.show(status: 'Saving item...');

    final result = await _repo.addDetail(
      stockAuditId: widget.auditId,
      productId: _selectedProductId!,
      stockId: _selectedStockId,
      physicalQuantity: int.parse(_physicalQuantityController.text),
      notes: _notesController.text.isNotEmpty ? _notesController.text : null,
    );

    EasyLoading.dismiss();

    if (result != null && result.detail != null) {
      EasyLoading.showSuccess('Item added');
      Navigator.pop(context, true);
    } else {
      EasyLoading.showError('Failed to add item');
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
          'Add Audit Item',
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
                'Product',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: kNutral700,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _productIdController,
                decoration: InputDecoration(
                  hintText: 'Enter product ID',
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
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Product ID is required';
                  }
                  return null;
                },
                onChanged: (value) {
                  if (value.isNotEmpty) {
                    final id = int.tryParse(value);
                    if (id != null) {
                      setState(() {
                        _selectedProductId = id;
                        _productName = 'Product #$id';
                      });
                    }
                  }
                },
              ),
              const SizedBox(height: 24),

              // System Info (read-only, populated from backend)
              _buildReadOnlyField('Product Name', _productName, theme),
              const SizedBox(height: 16),
              _buildReadOnlyField('Batch No', _batchNo.isNotEmpty ? _batchNo : 'Auto', theme),
              const SizedBox(height: 16),
              _buildReadOnlyField('System Quantity',
                  _systemQuantity > 0 ? '$_systemQuantity' : 'Auto', theme),
              const SizedBox(height: 16),
              _buildReadOnlyField('Unit Cost',
                  _unitCost.isNotEmpty ? _unitCost : 'Auto', theme),
              const SizedBox(height: 16),

              // Physical Quantity
              Text(
                'Physical Quantity',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: kNutral700,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _physicalQuantityController,
                decoration: InputDecoration(
                  hintText: 'Enter physical quantity',
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
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Physical quantity is required';
                  }
                  final qty = int.tryParse(value);
                  if (qty == null || qty < 0) {
                    return 'Enter a valid quantity';
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
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: 'Enter notes (optional)',
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
                  onPressed: _saveItem,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: kMainColor,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text(
                    'Save Item',
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

  Widget _buildReadOnlyField(
      String label, String value, ThemeData theme) {
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
        const SizedBox(height: 8),
        TextFormField(
          controller: TextEditingController(text: value),
          readOnly: true,
          decoration: InputDecoration(
            fillColor: Colors.grey.shade100,
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
          style: TextStyle(color: kNutral700, fontSize: 14),
        ),
      ],
    );
  }
}
