import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/StockAudit/model/stock_audit_model.dart';
import 'package:mobile_pos/Screens/StockAudit/repo/stock_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';

class StockAuditAddDetailScreen extends StatefulWidget {
  final int auditId;

  const StockAuditAddDetailScreen({super.key, required this.auditId});

  @override
  State<StockAuditAddDetailScreen> createState() =>
      _StockAuditAddDetailScreenState();
}

class _StockAuditAddDetailScreenState extends State<StockAuditAddDetailScreen> {
  final StockAuditRepo _repo = StockAuditRepo();
  final PagingController<int, StockAuditDetailModel> _pagingController =
      PagingController(firstPageKey: 1);
  final TextEditingController _searchController = TextEditingController();

  bool _isSelectionMode = false;
  final Set<int> _selectedDetailIds = {};

  @override
  void initState() {
    super.initState();
    _pagingController.addPageRequestListener((pageKey) => _fetchDetails(pageKey));
  }

  Future<void> _fetchDetails(int pageKey) async {
    try {
      final result = await _repo.getAuditDetail(widget.auditId);
      if (mounted && result != null) {
        final items = result.audit?.details ?? [];
        if (pageKey == 1) {
          _pagingController.appendLastPage(items);
        } else {
          _pagingController.appendPage(items, pageKey + 1);
        }
      } else {
        _pagingController.appendLastPage([]);
      }
    } catch (error) {
      _pagingController.error = error;
    }
  }

  Future<void> _autoPopulate() async {
    EasyLoading.show(status: 'Auto-populating...');
    final result = await _repo.autoPopulate(widget.auditId);
    EasyLoading.dismiss();

    if (result != null) {
      EasyLoading.showSuccess(
          '${result.detailsCount ?? 0} items added');
      _pagingController.refresh();
    } else {
      EasyLoading.showError('Failed to auto-populate');
    }
  }

  void _toggleSelectionMode(bool value) {
    setState(() {
      _isSelectionMode = value;
      if (!value) _selectedDetailIds.clear();
    });
  }

  @override
  void dispose() {
    _pagingController.dispose();
    _searchController.dispose();
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
          'Add Items to Audit',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          if (_isSelectionMode)
            IconButton(
              icon: const Icon(Icons.done_all_rounded, color: kWhite),
              onPressed: _selectedDetailIds.isEmpty
                  ? null
                  : () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                            content: Text(
                                '${_selectedDetailIds.length} items selected')),
                      );
                    },
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: kWhite),
            onPressed: () => _pagingController.refresh(),
          ),
        ],
      ),
      body: Column(
        children: [
          // Search
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: TextFormField(
              controller: _searchController,
              onChanged: (v) => _pagingController.refresh(),
              decoration: InputDecoration(
                contentPadding: const EdgeInsets.all(10),
                prefixIcon: const Icon(Icons.search, color: kNutral700),
                hintText: 'Search items...',
                filled: true,
                fillColor: Colors.white,
                enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: kOutlineColor),
                  borderRadius: BorderRadius.circular(30),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: kMainColor),
                  borderRadius: BorderRadius.circular(30),
                ),
              ),
            ),
          ),
          const SizedBox(height: 4),

          // Action Buttons
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _autoPopulate,
                    icon: const Icon(Icons.auto_awesome_rounded),
                    label: const Text('Auto-Populate'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: kMainColor,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () async {
                      final result = await Navigator.pushNamed(
                        context,
                        '/add-audit-item',
                        arguments: widget.auditId,
                      );
                      if (result == true) _pagingController.refresh();
                    },
                    icon: const Icon(Icons.add_rounded),
                    label: const Text('Add Item'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1976D2),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 4),

          // Details List
          Expanded(
            child: PagedListView<int, StockAuditDetailModel>(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              pagingController: _pagingController,
              physics: const AlwaysScrollableScrollPhysics(),
              builderDelegate: PagedChildBuilderDelegate<StockAuditDetailModel>(
                newPageProgressIndicatorBuilder: (context) => const Padding(
                  padding: EdgeInsets.all(16),
                  child: CircularProgressIndicator(color: kMainColor),
                ),
                noItemsFoundIndicatorBuilder: (context) => Padding(
                  padding: const EdgeInsets.all(40),
                  child: EmptyListWidget(title: 'No items added yet'),
                ),
                itemBuilder: (context, item, index) {
                  final hasVariance = (item.variance ?? 0) != 0;
                  return CheckboxListTile(
                    value: _selectedDetailIds.contains(item.id),
                    onChanged: (value) {
                      setState(() {
                        if (value ?? false) {
                          _selectedDetailIds.add(item.id!);
                        } else {
                          _selectedDetailIds.remove(item.id);
                        }
                      });
                    },
                    secondary: CircleAvatar(
                      radius: 16,
                      backgroundColor: hasVariance
                          ? const Color(0xFFFFEBEE)
                          : kMainColor.withValues(alpha: 0.1),
                      child: Icon(
                        hasVariance
                            ? Icons.warning_rounded
                            : Icons.inventory_2_outlined,
                        color: hasVariance
                            ? const Color(0xFFE53935)
                            : kMainColor,
                        size: 18,
                      ),
                    ),
                    title: Text(
                      item.product?.productName ?? 'Unknown Product',
                      style: const TextStyle(fontSize: 13),
                    ),
                    subtitle: Text(
                      'Batch: ${item.batchNo ?? 'N/A'} • Sys: ${item.systemQuantity ?? 0} • Phys: ${item.physicalQuantity ?? 0}',
                      style: TextStyle(
                        fontSize: 11,
                        color: hasVariance
                            ? const Color(0xFFE53935)
                            : kNutral600,
                      ),
                    ),
                    controlAffinity: ListTileControlAffinity.leading,
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
