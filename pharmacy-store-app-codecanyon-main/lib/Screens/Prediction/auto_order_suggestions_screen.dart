import 'package:flutter/material.dart';
import 'model/auto_order_model.dart';
import 'repo/prediction_repo.dart';

class AutoOrderSuggestionsScreen extends StatefulWidget {
  const AutoOrderSuggestionsScreen({super.key});

  @override
  State<AutoOrderSuggestionsScreen> createState() =>
      _AutoOrderSuggestionsScreenState();
}

class _AutoOrderSuggestionsScreenState
    extends State<AutoOrderSuggestionsScreen> {
  final PredictionRepo _repo = PredictionRepo();
  List<AutoOrderSuggestion> _suggestions = [];
  Map<String, dynamic>? _stats;
  bool _loading = true;
  bool _generating = false;
  String? _statusFilter;
  String? _priorityFilter;

  @override
  void initState() {
    super.initState();
    _loadSuggestions();
  }

  Future<void> _loadSuggestions() async {
    setState(() => _loading = true);
    final result = await _repo.getSuggestions(
      status: _statusFilter,
      priority: _priorityFilter,
    );
    if (mounted && result != null) {
      setState(() {
        if (result['data'] != null && result['data']['data'] != null) {
          _suggestions = (result['data']['data'] as List)
              .map((x) => AutoOrderSuggestion.fromJson(x))
              .toList();
        }
        _stats = result['stats'];
        _loading = false;
      });
    } else if (mounted) {
      setState(() {
        _suggestions = [];
        _loading = false;
      });
    }
  }

  Future<void> _generateSuggestions() async {
    setState(() => _generating = true);
    final result = await _repo.generateSuggestions();
    if (mounted) {
      setState(() => _generating = false);
      if (result != null && result['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                'تم إنشاء ${result['data']['suggestions_count'] ?? 0} اقتراح'),
            backgroundColor: Colors.green,
          ),
        );
        _loadSuggestions();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('فشل في إنشاء الاقتراحات'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _approveSuggestion(int id) async {
    final success = await _repo.approveSuggestion(id);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success ? 'تمت الموافقة' : 'فشل في الموافقة'),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
      if (success) _loadSuggestions();
    }
  }

  Future<void> _rejectSuggestion(int id) async {
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('سبب الرفض'),
        content: TextField(
          decoration: const InputDecoration(hintText: 'اختياري'),
          onChanged: (v) => reason = v,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('إلغاء'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, ''),
            child: const Text('رفض'),
          ),
        ],
      ),
    );
    if (reason != null) {
      final success = await _repo.rejectSuggestion(id, reason: reason);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(success ? 'تم الرفض' : 'فشل'),
            backgroundColor: success ? Colors.orange : Colors.red,
          ),
        );
        if (success) _loadSuggestions();
      }
    }
  }

  Future<void> _confirmSuggestion(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تأكيد تحويل الاقتراح'),
        content: const Text('سيتم إنشاء فاتورة شراء للمنتج'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('إلغاء'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('تأكيد', style: TextStyle(color: Colors.green)),
          ),
        ],
      ),
    );
    if (confirm == true) {
      final result = await _repo.confirmSuggestion(id);
      if (mounted) {
        if (result != null && result['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('تم إنشاء فاتورة شراء رقم: ${result['data']['invoice_number'] ?? ''}'),
              backgroundColor: Colors.green,
            ),
          );
          _loadSuggestions();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result?['message'] ?? 'فشل في التحويل'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('اقتراحات الطلب التلقائي'),
        actions: [
          if (_generating)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(16.0),
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                ),
              ),
            )
          else
            IconButton(
              icon: const Icon(Icons.auto_graph),
              onPressed: _generateSuggestions,
              tooltip: 'توليد الاقتراحات',
            ),
        ],
      ),
      body: Column(
        children: [
          // Stats bar
          if (_stats != null) _buildStatsBar(),
          // Filters
          _buildFilters(),
          // Content
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _suggestions.isEmpty
                    ? const Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.inventory_2_outlined,
                                size: 64, color: Colors.grey),
                            SizedBox(height: 16),
                            Text('لا توجد اقتراحات',
                                style: TextStyle(color: Colors.grey, fontSize: 16)),
                            SizedBox(height: 8),
                            Text('اضغط على زر التوليد لإنشاء الاقتراحات',
                                style: TextStyle(color: Colors.grey, fontSize: 12)),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadSuggestions,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(8),
                          itemCount: _suggestions.length,
                          itemBuilder: (context, index) {
                            final item = _suggestions[index];
                            return _buildSuggestionCard(item);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsBar() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      color: Colors.grey[50],
      child: Row(
        children: [
          _buildStatChip(
              'قيد الانتظار', '${_stats!['total_pending'] ?? 0}', Colors.orange),
          const SizedBox(width: 8),
          _buildStatChip(
              'تمت الموافقة', '${_stats!['total_approved'] ?? 0}', Colors.green),
          const SizedBox(width: 8),
          _buildStatChip(
              'تم التحويل', '${_stats!['total_converted'] ?? 0}', Colors.blue),
          const SizedBox(width: 8),
          _buildStatChip(
              'عالية الأولوية', '${_stats!['total_high_priority'] ?? 0}', Colors.red),
        ],
      ),
    );
  }

  Widget _buildStatChip(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          children: [
            Text(value,
                style: TextStyle(
                    color: color, fontWeight: FontWeight.bold, fontSize: 14)),
            Text(label,
                style: TextStyle(color: color, fontSize: 9),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildFilters() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      child: Row(
        children: [
          Expanded(
            child: DropdownButtonFormField<String?>(
              value: _statusFilter,
              decoration: const InputDecoration(
                labelText: 'الحالة',
                isDense: true,
                contentPadding:
                    EdgeInsets.symmetric(horizontal: 8, vertical: 8),
              ),
              items: const [
                DropdownMenuItem(value: null, child: Text('الكل')),
                DropdownMenuItem(value: 'pending', child: Text('قيد الانتظار')),
                DropdownMenuItem(value: 'approved', child: Text('تمت الموافقة')),
                DropdownMenuItem(value: 'converted', child: Text('تم التحويل')),
                DropdownMenuItem(value: 'rejected', child: Text('مرفوض')),
              ],
              onChanged: (v) {
                setState(() => _statusFilter = v);
                _loadSuggestions();
              },
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: DropdownButtonFormField<String?>(
              value: _priorityFilter,
              decoration: const InputDecoration(
                labelText: 'الأولوية',
                isDense: true,
                contentPadding:
                    EdgeInsets.symmetric(horizontal: 8, vertical: 8),
              ),
              items: const [
                DropdownMenuItem(value: null, child: Text('الكل')),
                DropdownMenuItem(value: 'high', child: Text('عالية')),
                DropdownMenuItem(value: 'medium', child: Text('متوسطة')),
                DropdownMenuItem(value: 'low', child: Text('منخفضة')),
              ],
              onChanged: (v) {
                setState(() => _priorityFilter = v);
                _loadSuggestions();
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSuggestionCard(AutoOrderSuggestion item) {
    Color priorityColor;
    switch (item.priority) {
      case 'high':
        priorityColor = Colors.red;
        break;
      case 'medium':
        priorityColor = Colors.orange;
        break;
      default:
        priorityColor = Colors.grey;
    }

    // Show action buttons based on status
    List<Widget> actions = [];
    if (item.status == 'pending') {
      actions = [
        IconButton(
          icon: const Icon(Icons.check_circle, color: Colors.green),
          onPressed: () => _approveSuggestion(item.id!),
          tooltip: 'موافقة',
        ),
        IconButton(
          icon: const Icon(Icons.cancel, color: Colors.red),
          onPressed: () => _rejectSuggestion(item.id!),
          tooltip: 'رفض',
        ),
      ];
    } else if (item.status == 'approved') {
      actions = [
        IconButton(
          icon: const Icon(Icons.shopping_cart, color: Colors.blue),
          onPressed: () => _confirmSuggestion(item.id!),
          tooltip: 'تحويل لفاتورة شراء',
        ),
      ];
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 8,
                  height: 48,
                  decoration: BoxDecoration(
                    color: priorityColor,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.productName ?? 'منتج',
                        style: const TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 15),
                      ),
                      if (item.productCode != null)
                        Text(
                          'كود: ${item.productCode}',
                          style: const TextStyle(
                              color: Colors.grey, fontSize: 12),
                        ),
                    ],
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: priorityColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: priorityColor.withOpacity(0.3)),
                  ),
                  child: Text(
                    item.priorityLabel,
                    style: TextStyle(
                      color: priorityColor,
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    item.statusLabel,
                    style: const TextStyle(fontSize: 11),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                _buildMiniInfo('المخزون', '${item.currentStock?.toStringAsFixed(0) ?? '0'}'),
                const SizedBox(width: 8),
                _buildMiniInfo('الطلب المتوقع', '${item.predictedDemand?.toStringAsFixed(0) ?? '0'}'),
                const SizedBox(width: 8),
                _buildMiniInfo('الكمية المقترحة', '${item.suggestedOrderQty?.toStringAsFixed(0) ?? '0'}',
                    isHighlight: true),
                if (item.confidenceScore != null) ...[
                  const SizedBox(width: 8),
                  _buildMiniInfo('الثقة', '${item.confidenceScore!.toStringAsFixed(0)}%'),
                ],
              ],
            ),
            if (actions.isNotEmpty) ...[
              const Divider(),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: actions,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildMiniInfo(String label, String value, {bool isHighlight = false}) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: isHighlight
              ? Colors.green.withOpacity(0.1)
              : Colors.grey[50],
          borderRadius: BorderRadius.circular(6),
          border: isHighlight
              ? Border.all(color: Colors.green.withOpacity(0.3))
              : null,
        ),
        child: Column(
          children: [
            Text(
              value,
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 14,
                color: isHighlight ? Colors.green : null,
              ),
            ),
            Text(
              label,
              style: const TextStyle(fontSize: 9, color: Colors.grey),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

