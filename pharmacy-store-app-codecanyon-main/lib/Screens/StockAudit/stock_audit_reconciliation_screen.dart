import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/StockAudit/model/stock_audit_model.dart';
import 'package:mobile_pos/Screens/StockAudit/repo/stock_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';

class StockReconciliationScreen extends StatefulWidget {
  final int auditId;

  const StockReconciliationScreen({super.key, required this.auditId});

  @override
  State<StockReconciliationScreen> createState() =>
      _StockReconciliationScreenState();
}

class _StockReconciliationScreenState extends State<StockReconciliationScreen> {
  final StockAuditRepo _repo = StockAuditRepo();
  StockAuditModel? _audit;
  bool _isLoading = true;
  bool _isPostingAll = false;

  @override
  void initState() {
    super.initState();
    _loadAudit();
  }

  Future<void> _loadAudit() async {
    setState(() => _isLoading = true);
    final result = await _repo.getAuditDetail(widget.auditId);
    if (mounted && result != null) {
      setState(() {
        _audit = result.audit;
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _postAllReconciliations() async {
    final unpastedCount =
        (_audit?.reconciliations ?? []).where((r) => !(r.isPosted ?? false)).length;
    if (unpastedCount == 0) {
      EasyLoading.showInfo('No pending reconciliations to post');
      return;
    }

    EasyLoading.show(status: 'Posting $unpastedCount reconciliations...');
    final result = await _repo.postAllReconciliations(widget.auditId);
    EasyLoading.dismiss();

    if (result != null) {
      EasyLoading.showSuccess(
          '${result.reconciliationsCount ?? 0} reconciliations posted');
      _loadAudit();
    } else {
      EasyLoading.showError('Failed to post reconciliations');
    }
  }

  Future<void> _postSingleReconciliation(StockReconciliationModel recon) async {
    EasyLoading.show(status: 'Posting reconciliation...');
    final result = await _repo.postReconciliation(recon.id!);
    EasyLoading.dismiss();

    if (result != null) {
      EasyLoading.showSuccess('Reconciliation posted');
      _loadAudit();
    } else {
      EasyLoading.showError('Failed to post reconciliation');
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
          'Reconciliation',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: kWhite),
            onPressed: _loadAudit,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: kMainColor))
          : _audit?.reconciliations == null ||
                _audit!.reconciliations!.isEmpty
              ? ListView(
                  padding: const EdgeInsets.all(40),
                  children: [
                    EmptyListWidget(
                      title: 'No reconciliations found',
                    ),
                  ],
                )
              : Column(
                  children: [
                    // Summary
                    _buildSummary(theme),
                    const SizedBox(height: 16),

                    // Reconciliations List
                    Expanded(
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _audit!.reconciliations!.length,
                        separatorBuilder: (context, index) =>
                            const SizedBox(height: 4),
                        itemBuilder: (context, index) {
                          final recon = _audit!.reconciliations![index];
                          return _buildReconciliationCard(theme, recon);
                        },
                      ),
                    ),

                    // Post All Button
                    if ((_audit?.reconciliations ?? [])
                        .any((r) => !(r.isPosted ?? false)))
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: _isPostingAll ? null : _postAllReconciliations,
                            icon: _isPostingAll
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      color: Colors.white,
                                      strokeWidth: 2,
                                    ),
                                  )
                                : const Icon(Icons.post_add_rounded),
                            label: const Text('Post All Reconciliations'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: kMainColor,
                              padding:
                                  const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
    );
  }

  Widget _buildSummary(ThemeData theme) {
    final reconciliations = _audit?.reconciliations ?? [];
    final total = reconciliations.length;
    final posted = reconciliations.where((r) => r.isPosted ?? false).length;
    final pending = total - posted;

    return Container(
      padding: const EdgeInsets.all(16),
      margin: const EdgeInsets.symmetric(horizontal: 16),
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
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildSummaryItem(theme, 'Total', '$total', kMainColor),
          _buildSummaryItem(theme, 'Posted', '$posted', const Color(0xFF43A047)),
          _buildSummaryItem(theme, 'Pending', '$pending', const Color(0xFFFF6D00)),
        ],
      ),
    );
  }

  Widget _buildSummaryItem(
      ThemeData theme, String label, String value, Color color) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            color: color,
            fontWeight: FontWeight.bold,
            fontSize: 20,
          ),
        ),
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral600,
            fontSize: 11,
          ),
        ),
      ],
    );
  }

  Widget _buildReconciliationCard(
      ThemeData theme, StockReconciliationModel recon) {
    final isPosted = recon.isPosted ?? false;
    final adjValue = double.tryParse(recon.adjustmentValue?.toString() ?? '0') ?? 0;
    final adjColor = recon.adjustmentType == 'increase'
        ? const Color(0xFF43A047)
        : const Color(0xFFE53935);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isPosted
              ? const Color(0xFF43A047).withValues(alpha: 0.3)
              : kOutlineColor,
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: ExpansionTile(
        leading: CircleAvatar(
          radius: 16,
          backgroundColor: isPosted
              ? const Color(0xFF4CAF50).withValues(alpha: 0.15)
              : const Color(0xFFFF6D00).withValues(alpha: 0.15),
          child: Icon(
            isPosted ? Icons.check_circle_rounded : Icons.pending_rounded,
            color: isPosted ? const Color(0xFF4CAF50) : const Color(0xFFFF6D00),
            size: 18,
          ),
        ),
        title: Text(
          recon.product?.productName ?? 'Product',
          style: const TextStyle(fontSize: 13),
        ),
        subtitle: Text(
          '${recon.adjustmentType?.toUpperCase() ?? ''} • ${recon.product?.productCode ?? ''}',
          style: TextStyle(
            fontSize: 11,
            color: kNutral600,
          ),
        ),
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildReconRow('Adjustment',
                    '${recon.adjustmentQuantity ?? 0} units (${recon.adjustmentType})',
                    theme,
                    color: adjColor, isBold: true),
                _buildReconRow('Previous Qty',
                    '${recon.previousQuantity ?? 0}', theme),
                _buildReconRow('New Qty',
                    '${recon.newQuantity ?? 0}', theme),
                _buildReconRow('Adjustment Value',
                    '$adjValue',
                    theme,
                    color: adjColor, isBold: true),
                _buildReconRow('Reason',
                    recon.reason ?? 'N/A', theme),
                const SizedBox(height: 12),
                if (!isPosted)
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => _postSingleReconciliation(recon),
                      icon: const Icon(Icons.post_add_rounded),
                      label: const Text('Post'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: kMainColor,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReconRow(
      String label, String value, ThemeData theme,
      {Color? color, bool isBold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodySmall
                ?.copyWith(color: kNutral700, fontSize: 12),
          ),
          Text(
            value,
            style: theme.textTheme.bodySmall?.copyWith(
              color: color,
              fontWeight: isBold ? FontWeight.w600 : FontWeight.normal,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}
