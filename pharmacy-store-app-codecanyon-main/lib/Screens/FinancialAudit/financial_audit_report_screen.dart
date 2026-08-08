import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/FinancialAudit/model/financial_audit_model.dart';
import 'package:mobile_pos/Screens/FinancialAudit/repo/financial_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';

class FinancialAuditReportScreen extends StatefulWidget {
  final int auditId;

  const FinancialAuditReportScreen({super.key, required this.auditId});

  @override
  State<FinancialAuditReportScreen> createState() =>
      _FinancialAuditReportScreenState();
}

class _FinancialAuditReportScreenState
    extends State<FinancialAuditReportScreen> {
  final FinancialAuditRepo _repo = FinancialAuditRepo();
  FinancialAuditReportData? _report;
  bool _isLoading = true;
  String? _selectedTransactionType;
  TransactionDetailsResponse? _transactionDetails;
  bool _isLoadingTransactions = false;

  final List<String> _transactionTypes = [
    'sales',
    'purchases',
    'income',
    'expenses',
  ];

  @override
  void initState() {
    super.initState();
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() => _isLoading = true);
    final result = await _repo.getReport(widget.auditId);
    if (mounted && result != null) {
      setState(() {
        _report = result.report;
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _loadTransactionDetails(String type) async {
    setState(() {
      _isLoadingTransactions = true;
      _selectedTransactionType = type;
    });

    final result = await _repo.getTransactionDetails(widget.auditId, type);
    if (mounted) {
      setState(() {
        _transactionDetails = result;
        _isLoadingTransactions = false;
      });
    }
  }

  String _getTypeLabel(String type) {
    switch (type) {
      case 'sales':
        return 'Sales';
      case 'purchases':
        return 'Purchases';
      case 'income':
        return 'Income';
      case 'expenses':
        return 'Expenses';
      default:
        return type;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isVarianceNegative =
        (_report?.financialSummary?.variance ?? 0) < 0;

    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: kWhite),
        title: Text(
          'Financial Audit Report',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: kWhite),
            onPressed: _loadReport,
          ),
          IconButton(
            icon: const Icon(Icons.copy_rounded, color: kWhite),
            onPressed: () {
              Clipboard.setData(ClipboardText(
                  _buildReportText()));
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Report copied to clipboard')),
              );
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : _report == null
              ? const Center(child: Text('Failed to load report'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Audit Info Header
                      _buildHeaderCard(theme),
                      const SizedBox(height: 16),

                      // Financial Summary
                      _buildFinancialSummary(theme, isVarianceNegative),
                      const SizedBox(height: 16),

                      // Variance Analysis
                      _buildVarianceAnalysis(theme),
                      const SizedBox(height: 16),

                      // Transaction Type Selector
                      Text(
                        'Transaction Details',
                        style: theme.textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w600,
                          color: kNutral800,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: _transactionTypes.map((type) {
                          final isSelected = _selectedTransactionType == type;
                          return FilterChip(
                            label: Text(
                              _getTypeLabel(type),
                              style: TextStyle(
                                fontSize: 12,
                                color: isSelected
                                    ? Colors.white
                                    : kNutral700,
                                fontWeight: isSelected
                                    ? FontWeight.w600
                                    : FontWeight.normal,
                              ),
                            ),
                            selected: isSelected,
                            onSelected: (s) {
                              if (s) _loadTransactionDetails(type);
                            },
                            selectedColor: kMainColor,
                            backgroundColor: Colors.white,
                            side: BorderSide(
                              color: isSelected ? kMainColor : kOutlineColor,
                            ),
                            materialTapTargetSize:
                                MaterialTapTargetSize.shrinkWrap,
                          );
                        }),
                      ),

                      // Transaction Details
                      if (_selectedTransactionType != null)
                        _buildTransactionDetails(theme),
                    ],
                  ),
                ),
    );
  }

  String _buildReportText() {
    final buffer = StringBuffer();
    final report = _report;
    if (report == null) return '';

    buffer.writeln('Financial Audit Report');
    buffer.writeln('');

    if (report.auditInfo != null) {
      buffer.writeln('Audit Number: ${report.auditInfo!.auditNumber}');
      buffer.writeln('Audit Type: ${report.auditInfo!.auditType}');
      if (report.auditInfo!.period != null) {
        buffer.writeln(
            'Period: ${report.auditInfo!.period!.startDate} - ${report.auditInfo!.period!.endDate}');
      }
      buffer.writeln('Status: ${report.auditInfo!.status}');
      buffer.writeln('');
    }

    if (report.financialSummary != null) {
      final fs = report.financialSummary!;
      buffer.writeln('Opening Balance: ${fs.openingBalance}');
      buffer.writeln('Total Sales: ${fs.totalSales}');
      buffer.writeln('Total Other Income: ${fs.totalOtherIncome}');
      buffer.writeln('Total Revenue: ${fs.totalRevenue}');
      buffer.writeln('Total Expenses: ${fs.totalExpenses}');
      buffer.writeln('Total Purchases: ${fs.totalPurchases}');
      buffer.writeln('Closing Balance: ${fs.closingBalance}');
      buffer.writeln('Expected Closing Balance: ${fs.expectedClosingBalance}');
      buffer.writeln('Variance: ${fs.variance}');
      buffer.writeln('');
    }

    if (report.varianceAnalysis != null) {
      final va = report.varianceAnalysis!;
      buffer.writeln(
          'Is Balanced: ${va.isBalanced ? 'Yes' : 'No'}');
      buffer.writeln('Variance Percentage: ${va.variancePercentage}%');
      buffer.writeln(
          'Requires Investigation: ${va.requiresInvestigation ? 'Yes' : 'No'}');
    }

    return buffer.toString();
  }

  Widget _buildHeaderCard(ThemeData theme) {
    final report = _report!;
    final auditInfo = report.auditInfo;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF00987F), Color(0xFF14B8A6)],
        ),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            auditInfo?.auditNumber ?? 'Unknown Audit',
            style: theme.textTheme.titleMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            '${auditInfo?.auditType ?? ''} • ${auditInfo?.status ?? ''}',
            style: theme.textTheme.bodySmall?.copyWith(
              color: Colors.white70,
              fontSize: 12,
            ),
          ),
          if (auditInfo?.period != null)
            Text(
              '${auditInfo!.period!.startDate} - ${auditInfo.period!.endDate}',
              style: theme.textTheme.bodySmall?.copyWith(
                color: Colors.white70,
                fontSize: 12,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildFinancialSummary(ThemeData theme, bool isVarianceNegative) {
    final fs = _report!.financialSummary!;
    final varianceColor =
        isVarianceNegative ? const Color(0xFFE53935) : const Color(0xFF43A047);

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
            'Financial Summary',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: kNutral800,
            ),
          ),
          const SizedBox(height: 16),
          _buildSummaryRow('Opening Balance', fs.openingBalance, theme),
          _buildSummaryRow('Total Sales', fs.totalSales, theme),
          _buildSummaryRow('Other Income', fs.totalOtherIncome, theme),
          _buildSummaryRow('Total Revenue', fs.totalRevenue, theme,
              isTotal: true),
          _buildSummaryRow('Total Expenses', fs.totalExpenses, theme),
          _buildSummaryRow('Total Purchases', fs.totalPurchases, theme),
          const SizedBox(height: 8),
          Container(
            height: 1,
            color: kOutlineColor,
          ),
          const SizedBox(height: 8),
          _buildSummaryRow('Closing Balance', fs.closingBalance, theme,
              isTotal: true),
          _buildSummaryRow('Expected Closing', fs.expectedClosingBalance,
              theme),
          _buildSummaryRow(
            'Variance',
            fs.variance,
            theme,
            color: varianceColor,
            isBold: true,
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(
    String label,
    dynamic value,
    ThemeData theme, {
    Color? color,
    bool isTotal = false,
    bool isBold = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: kNutral600,
              fontSize: 12,
              fontWeight: isTotal ? FontWeight.w600 : FontWeight.normal,
            ),
          ),
          Text(
            _formatCurrency(value),
            style: theme.textTheme.bodySmall?.copyWith(
              color: color ?? (isTotal ? kNutral800 : kNutral700),
              fontWeight: isBold || isTotal ? FontWeight.w600 : FontWeight.normal,
              fontSize: 12,
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

  Widget _buildVarianceAnalysis(ThemeData theme) {
    final va = _report!.varianceAnalysis!;
    final isBalanced = va.isBalanced ?? true;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isBalanced
            ? const Color(0xFFE8F5E9)
            : const Color(0xFFFFEBEE),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isBalanced
              ? const Color(0xFF43A047).withValues(alpha: 0.3)
              : const Color(0xFFE53935).withValues(alpha: 0.3),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                isBalanced
                    ? Icons.check_circle_rounded
                    : Icons.warning_rounded,
                color: isBalanced
                    ? const Color(0xFF43A047)
                    : const Color(0xFFE53935),
                size: 20,
              ),
              const SizedBox(width: 8),
              Text(
                isBalanced ? 'Audit is Balanced' : 'Variance Detected',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: isBalanced
                      ? const Color(0xFF2E7D32)
                      : const Color(0xFFC62828),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Variance Percentage: ${va.variancePercentage?.toStringAsFixed(2) ?? '0'}%',
            style: theme.textTheme.bodySmall?.copyWith(
              color: kNutral700,
              fontSize: 12,
            ),
          ),
          if (va.requiresInvestigation ?? false)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                'This audit requires further investigation.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: const Color(0xFFE53935),
                  fontSize: 12,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildTransactionDetails(ThemeData theme) {
    if (_isLoadingTransactions) {
      return const Padding(
        padding: EdgeInsets.all(16),
        child: Center(child: CircularProgressIndicator(color: kMainColor)),
      );
    }

    if (_transactionDetails == null) {
      return const SizedBox.shrink();
    }

    final details = _transactionDetails!.details;
    final typeLabel = _getTypeLabel(_selectedTransactionType ?? '');

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 8),
        Text(
          '$typeLabel Transactions',
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontWeight: FontWeight.w600,
            fontSize: 13,
          ),
        ),
        const SizedBox(height: 4),
        if (details != null && details.isNotEmpty)
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: details.length,
            separatorBuilder: (context, index) => const SizedBox(height: 4),
            itemBuilder: (context, index) {
              final item = details[index];
              return Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: kOutlineColor, width: 1),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${item.invoiceNumber ?? 'N/A'}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        fontWeight: FontWeight.w600,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '${item.date ?? ''}',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: kNutral600,
                            fontSize: 11,
                          ),
                        ),
                        Text(
                          _formatCurrency(item.amount),
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: kNutral800,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                    if (item.customer != null)
                      Text(
                        'Customer: ${item.customer}',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: kNutral600,
                          fontSize: 11,
                        ),
                      ),
                    if (item.paymentStatus != null)
                      Text(
                        'Payment: ${item.paymentStatus}',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: (item.paymentStatus == 'Paid')
                              ? const Color(0xFF43A047)
                              : const Color(0xFFE53935),
                          fontSize: 11,
                        ),
                      ),
                  ],
                ),
              );
            },
          )
        else
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 20),
            child: EmptyListWidget(title: 'No transactions found'),
          ),
      ],
    );
  }
}
