import 'package:flutter/material.dart';
import 'package:mobile_pos/Screens/Fefo/repo/fefo_repo.dart';
import 'package:mobile_pos/Screens/Fefo/model/fefo_model.dart';
import 'package:mobile_pos/Provider/language_provider.dart';
import 'package:provider/provider.dart';

class FefoLogsScreen extends StatefulWidget {
  const FefoLogsScreen({super.key});

  @override
  State<FefoLogsScreen> createState() => _FefoLogsScreenState();
}

class _FefoLogsScreenState extends State<FefoLogsScreen> {
  final FefoRepo _repo = FefoRepo();
  final ScrollController _scrollController = ScrollController();
  bool _loading = true;
  bool _loadingMore = false;
  List<FefoLogItem> _logs = [];
  int _page = 1;
  bool _hasMore = true;

  @override
  void initState() {
    super.initState();
    _loadLogs();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      _loadMore();
    }
  }

  Future<void> _loadLogs() async {
    setState(() => _loading = true);
    _page = 1;
    final logs = await _repo.getLogs(page: _page);
    setState(() {
      _logs = logs;
      _loading = false;
      _hasMore = logs.length >= 20;
    });
  }

  Future<void> _loadMore() async {
    if (_loadingMore || !_hasMore) return;
    setState(() => _loadingMore = true);
    _page++;
    final logs = await _repo.getLogs(page: _page);
    setState(() {
      _logs.addAll(logs);
      _loadingMore = false;
      _hasMore = logs.length >= 20;
    });
  }

  @override
  Widget build(BuildContext context) {
    final lang = Provider.of<LanguageProvider>(context).currentLang;
    return Scaffold(
      appBar: AppBar(
        title: Text(lang == 'ar' ? 'سجل FEFO' : 'FEFO Logs'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadLogs,
              child: _logs.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.history, size: 64, color: Colors.grey[300]),
                          const SizedBox(height: 16),
                          Text(
                            lang == 'ar' ? 'لا توجد سجلات FEFO بعد' : 'No FEFO logs yet',
                            style: TextStyle(color: Colors.grey[500], fontSize: 16),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.all(8),
                      itemCount: _logs.length + (_loadingMore ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index == _logs.length) {
                          return const Center(
                            child: Padding(
                              padding: EdgeInsets.all(16),
                              child: CircularProgressIndicator(),
                            ),
                          );
                        }
                        return _buildLogItem(_logs[index], lang);
                      },
                    ),
            ),
    );
  }

  Widget _buildLogItem(FefoLogItem log, String lang) {
    final isRestore = (log.quantityDeducted ?? 0) < 0;
    final isExpiredRemoval = log.actionType == 'expired_removal';
    final isSale = log.actionType == 'sale_deduction';

    IconData icon;
    Color color;
    String actionLabel;

    if (isRestore) {
      icon = Icons.restore;
      color = Colors.blue;
      actionLabel = lang == 'ar' ? 'استعادة مخزون' : 'Stock Restored';
    } else if (isExpiredRemoval) {
      icon = Icons.delete_forever;
      color = Colors.red;
      actionLabel = lang == 'ar' ? 'إزالة منتهي الصلاحية' : 'Expired Removed';
    } else if (isSale) {
      icon = Icons.shopping_cart;
      color = Colors.green;
      actionLabel = lang == 'ar' ? 'خصم مبيع' : 'Sale Deduction';
    } else {
      icon = Icons.settings;
      color = Colors.orange;
      actionLabel = log.actionType ?? 'N/A';
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.2),
          child: Icon(icon, color: color, size: 20),
        ),
        title: Row(
          children: [
            Expanded(
              child: Text(
                log.product?.productName ?? 'N/A',
                style: const TextStyle(fontWeight: FontWeight.bold),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                '${isRestore ? '+' : ''}${log.quantityDeducted ?? 0}',
                style: TextStyle(
                  color: color,
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text(
              '$actionLabel | ${lang == 'ar' ? 'الدفعة' : 'Batch'}: ${log.batchNo ?? 'N/A'}',
              style: TextStyle(color: Colors.grey[600], fontSize: 12),
            ),
            if (log.expireDate != null)
              Text(
                '${lang == 'ar' ? 'انتهاء' : 'Expiry'}: ${log.expireDate}',
                style: TextStyle(color: Colors.grey[500], fontSize: 11),
              ),
            if (log.sale?.invoiceNumber != null)
              Text(
                '${lang == 'ar' ? 'فاتورة' : 'Invoice'}: ${log.sale!.invoiceNumber}',
                style: TextStyle(color: Colors.grey[500], fontSize: 11),
              ),
            if (log.createdAt != null)
              Text(
                log.createdAt!,
                style: TextStyle(color: Colors.grey[400], fontSize: 10),
              ),
          ],
        ),
        isThreeLine: true,
        onTap: () {
          _showLogDetails(log, lang);
        },
      ),
    );
  }

  void _showLogDetails(FefoLogItem log, String lang) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(lang == 'ar' ? 'تفاصيل سجل FEFO' : 'FEFO Log Details'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              _detailRow(lang == 'ar' ? 'المنتج' : 'Product', log.product?.productName ?? 'N/A'),
              _detailRow(lang == 'ar' ? 'الدفعة' : 'Batch No', log.batchNo ?? 'N/A'),
              _detailRow(lang == 'ar' ? 'تاريخ الانتهاء' : 'Expiry Date', log.expireDate ?? 'N/A'),
              _detailRow(lang == 'ar' ? 'الكمية' : 'Quantity', '${log.quantityDeducted ?? 0}'),
              _detailRow(lang == 'ar' ? 'المتبقي' : 'Remaining', '${log.quantityRemainingAfter ?? 0}'),
              _detailRow(lang == 'ar' ? 'نوع العملية' : 'Action', log.actionType ?? 'N/A'),
              _detailRow(lang == 'ar' ? 'ملاحظات' : 'Notes', log.notes ?? 'N/A'),
              if (log.sale?.invoiceNumber != null)
                _detailRow(lang == 'ar' ? 'الفاتورة' : 'Invoice', log.sale!.invoiceNumber!),
              _detailRow(lang == 'ar' ? 'التاريخ' : 'Date', log.createdAt ?? 'N/A'),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(lang == 'ar' ? 'إغلاق' : 'Close'),
          ),
        ],
      ),
    );
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey[600], fontWeight: FontWeight.w500),
            ),
          ),
          Expanded(
            child: Text(value),
          ),
        ],
      ),
    );
  }
}

