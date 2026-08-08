import 'package:flutter/material.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/FinancialAudit/model/financial_audit_model.dart';
import 'package:mobile_pos/Screens/FinancialAudit/repo/financial_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';

class FinancialAuditTransactionsScreen extends StatefulWidget {
  final int auditId;

  const FinancialAuditTransactionsScreen({super.key, required this.auditId});

  @override
  State<FinancialAuditTransactionsScreen> createState() =>
      _FinancialAuditTransactionsScreenState();
}

class _FinancialAuditTransactionsScreenState
    extends State<FinancialAuditTransactionsScreen>
    with SingleTickerProviderStateMixin {
  final FinancialAuditRepo _repo = FinancialAuditRepo();
  late TabController _tabController;
  final Map<String, TransactionDetailsResponse?> _transactionsByType = {};
  final Map<String, bool> _isLoadingByType = {};

  final List<_TransactionTab> _tabs = [
    _TransactionTab('sales', 'Sales', Icons.point_of_sale_rounded,
        Color(0xFF00987F)),
    _TransactionTab('purchases', 'Purchases', Icons.shopping_cart_rounded,
        Color(0xFF6A1B9A)),
    _TransactionTab('income', 'Income', Icons.account_balance_rounded,
        Color(0xFF1565C0)),
    _TransactionTab('expenses', 'Expenses', Icons.money_off_rounded,
        Color(0xFFE53935)),
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _tabs.length, vsync: this);
    _loadTransactions(_tabs.first.type);
    _tabController.addListener(() {
      final index = _tabController.index;
      final type = _tabs[index].type;
      if (_transactionsByType[type] == null && !_isLoadingByType[type]!) {
        _loadTransactions(type);
      }
    });
  }

  Future<void> _loadTransactions(String type) async {
    setState(() => _isLoadingByType[type] = true);
    final result = await _repo.getTransactionDetails(widget.auditId, type);
    if (mounted) {
      setState(() {
        _transactionsByType[type] = result;
        _isLoadingByType[type] = false;
      });
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
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
          'Transaction Details',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: kWhite,
          labelColor: kWhite,
          unselectedLabelColor: Colors.white70,
          isScrollable: true,
          labelStyle: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
          unselectedLabelStyle: const TextStyle(fontSize: 13),
          tabs: _tabs
              .map((tab) => Tab(
                    child: Row(
                      children: [
                        Icon(tab.icon, size: 16),
                        const SizedBox(width: 4),
                        Text(tab.label),
                      ],
                    ),
                  ))
              .toList(),
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: _tabs.map((tab) => _buildTabView(theme, tab)).toList(),
      ),
    );
  }

  Widget _buildTabView(ThemeData theme, _TransactionTab tab) {
    final isLoading = _isLoadingByType[tab.type] ?? false;
    final transactions = _transactionsByType[tab.type];

    if (isLoading) {
      return const Center(child: CircularProgressIndicator(color: kMainColor));
    }

    if (transactions == null) {
      return Center(
        child: TextButton.icon(
          onPressed: () => _loadTransactions(tab.type),
          icon: const Icon(Icons.refresh, color: kMainColor),
          label: const Text('Load transactions', style: TextStyle(color: kMainColor)),
        ),
      );
    }

    final details = transactions.details;
    if (details == null || details.isEmpty) {
      return const Center(child: EmptyListWidget(title: 'No transactions found'));
    }

    return RefreshIndicator(
      onRefresh: () => _loadTransactions(tab.type),
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: details.length,
        separatorBuilder: (context, index) => const SizedBox(height: 8),
        itemBuilder: (context, index) {
          final item = details[index];
          return Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: kOutlineColor, width: 1),
            ),
            child: ListTile(
              contentPadding: const EdgeInsets.all(12),
              leading: CircleAvatar(
                radius: 16,
                backgroundColor: tab.color.withValues(alpha: 0.15),
                child: Icon(tab.icon, color: tab.color, size: 18),
              ),
              title: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.invoiceNumber ?? 'N/A',
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    item.date ?? '',
                    style: TextStyle(
                      color: kNutral600,
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (item.customer != null && item.customer!.isNotEmpty)
                    Text(
                      'Customer: ${item.customer}',
                      style: TextStyle(color: kNutral700, fontSize: 11),
                    ),
                  if (item.category != null && item.category!.isNotEmpty)
                    Text(
                      'Category: ${item.category}',
                      style: TextStyle(color: kNutral700, fontSize: 11),
                    ),
                ],
              ),
              trailing: Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    _formatCurrency(item.amount),
                    style: TextStyle(
                      color: item.paymentStatus == 'Paid'
                          ? const Color(0xFF43A047)
                          : item.paymentStatus == 'Pending'
                              ? const Color(0xFFFF9800)
                              : const Color(0xFFE53935),
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: _getStatusColor(item.paymentStatus ?? '')
                          .withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      item.paymentStatus ?? 'N/A',
                      style: TextStyle(
                        color: _getStatusColor(item.paymentStatus ?? ''),
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
              onTap: () {
                _showTransactionDetails(theme, item);
              },
            ),
          );
        },
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'paid':
        return const Color(0xFF43A047);
      case 'pending':
        return const Color(0xFFFF9800);
      case 'unpaid':
        return const Color(0xFFE53935);
      default:
        return kNutral600;
    }
  }

  String _formatCurrency(dynamic value) {
    if (value == null) return '0.00';
    final numValue = double.tryParse(value.toString()) ?? 0;
    return numValue.toStringAsFixed(2);
  }

  void _showTransactionDetails(ThemeData theme, TransactionDetail item) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        margin: const EdgeInsets.all(16),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Transaction Details',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
                color: kNutral800,
              ),
            ),
            const SizedBox(height: 16),
            _buildDetailRow(theme, 'Invoice', item.invoiceNumber ?? 'N/A'),
            _buildDetailRow(theme, 'Date', item.date ?? 'N/A'),
            _buildDetailRow(theme, 'Amount', _formatCurrency(item.amount)),
            if (item.customer != null && item.customer!.isNotEmpty)
              _buildDetailRow(theme, 'Customer', item.customer!),
            if (item.category != null && item.category!.isNotEmpty)
              _buildDetailRow(theme, 'Category', item.category!),
            _buildDetailRow(theme, 'Payment Status', item.paymentStatus ?? 'N/A'),
            if (item.description != null && item.description!.isNotEmpty)
              _buildDetailRow(theme, 'Description', item.description!),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: kMainColor,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Close',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(ThemeData theme, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(
                color: kNutral600,
                fontSize: 12,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: theme.textTheme.bodySmall?.copyWith(
                color: kNutral800,
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TransactionTab {
  final String type;
  final String label;
  final IconData icon;
  final Color color;

  _TransactionTab(this.type, this.label, this.icon, this.color);
}
