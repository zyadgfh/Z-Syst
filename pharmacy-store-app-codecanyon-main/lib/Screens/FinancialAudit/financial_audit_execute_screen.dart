import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/FinancialAudit/repo/financial_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class FinancialAuditExecuteScreen extends StatefulWidget {
  final int auditId;

  const FinancialAuditExecuteScreen({super.key, required this.auditId});

  @override
  State<FinancialAuditExecuteScreen> createState() =>
      _FinancialAuditExecuteScreenState();
}

class _FinancialAuditExecuteScreenState
    extends State<FinancialAuditExecuteScreen> {
  final FinancialAuditRepo _repo = FinancialAuditRepo();
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _openingBalanceController = TextEditingController();
  final TextEditingController _closingBalanceController =
      TextEditingController();

  bool _isLoading = false;
  Map<String, dynamic>? _calculatedData;

  @override
  void initState() {
    super.initState();
    _loadCalculatedData();
  }

  Future<void> _loadCalculatedData() async {
    setState(() => _isLoading = true);
    // The backend auto-calculates revenue and expenses on execute,
    // but we can show the calculated data from the audit details.
    // For now, just fetch the audit to show existing data
    final result = await _repo.getAuditDetail(widget.auditId);
    if (mounted && result != null) {
      final audit = result.audit;
      if (audit != null) {
        setState(() {
          _openingBalanceController.text =
              audit.openingBalance?.toString() ?? '';
          _closingBalanceController.text =
              audit.closingBalance?.toString() ?? '';
          _calculatedData = {
            'total_revenue': audit.totalRevenue ?? 0,
            'total_expenses': audit.totalExpenses ?? 0,
            'audit_number': audit.auditNumber,
            'period': '${audit.startDate} - ${audit.endDate}',
          };
          _isLoading = false;
        });
      }
    }
    setState(() => _isLoading = false);
  }

  Future<void> _executeAudit() async {
    if (_formKey.currentState?.validate() != true) return;

    final openingBalance =
        double.tryParse(_openingBalanceController.text) ?? 0;
    final closingBalance =
        double.tryParse(_closingBalanceController.text) ?? 0;

    EasyLoading.show(status: 'Executing audit...');

    final result = await _repo.executeAudit(
      widget.auditId,
      openingBalance: openingBalance,
      closingBalance: closingBalance,
    );

    EasyLoading.dismiss();

    if (result != null && result.audit != null) {
      EasyLoading.showSuccess(result.message ?? 'Audit executed successfully');
      Navigator.pop(context, true);
    } else {
      EasyLoading.showError('Failed to execute audit');
    }
  }

  @override
  void dispose() {
    _openingBalanceController.dispose();
    _closingBalanceController.dispose();
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
          'Execute Audit',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Calculated Data Info Card
                    if (_calculatedData != null)
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFFE3F2FD).withValues(alpha: 0.3),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                              color: const Color(0xFF2196F3).withValues(alpha: 0.3)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Calculated Data',
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w600,
                                color: kNutral800,
                              ),
                            ),
                            const SizedBox(height: 8),
                            _buildInfoRow(theme, 'Audit',
                                _calculatedData!['audit_number']?.toString() ?? 'N/A'),
                            _buildInfoRow(
                                theme,
                                'Period',
                                _calculatedData!['period']?.toString() ?? 'N/A'),
                            _buildInfoRow(
                                theme,
                                'Total Revenue',
                                _formatCurrency(_calculatedData!['total_revenue'])),
                            _buildInfoRow(
                                theme,
                                'Total Expenses',
                                _formatCurrency(_calculatedData!['total_expenses'])),
                          ],
                        ),
                      ),
                    const SizedBox(height: 24),

                    // Opening Balance
                    Text(
                      'Opening Balance',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: kNutral700,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _openingBalanceController,
                      decoration: InputDecoration(
                        hintText: 'Enter opening balance',
                        fillColor: Colors.white,
                        filled: true,
                        prefixIcon:
                            const Icon(Icons.account_balance_wallet_outlined),
                        enabledBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kOutlineColor),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kMainColor),
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Opening balance is required';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Enter a valid number';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 24),

                    // Closing Balance
                    Text(
                      'Closing Balance',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: kNutral700,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _closingBalanceController,
                      decoration: InputDecoration(
                        hintText: 'Enter closing balance',
                        fillColor: Colors.white,
                        filled: true,
                        prefixIcon:
                            const Icon(Icons.account_balance_wallet_outlined),
                        enabledBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kOutlineColor),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderSide: BorderSide(color: kMainColor),
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Closing balance is required';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Enter a valid number';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 32),

                    // Info Text
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF3E0),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(
                            color: const Color(0xFFFF9800).withValues(alpha: 0.3)),
                      ),
                      child: Text(
                        'The system will automatically calculate total revenue and expenses from sales, purchases, income, and expense records within the audit period. Enter the actual opening and closing balances to generate the variance report.',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: const Color(0xFFE65100),
                          fontSize: 12,
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),

                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _executeAudit,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: kMainColor,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Text(
                          'Execute Audit',
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

  Widget _buildInfoRow(
      ThemeData theme, String label, String value) {
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
            style: theme.textTheme.bodySmall?.copyWith(
              color: kNutral700,
              fontWeight: FontWeight.w500,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }

  String _formatCurrency(dynamic value) {
    if (value == null) return '0.00';
    final numValue = double.tryParse(value.toString()) ?? 0;
    return numValue.toStringAsFixed(2);
  }
}
