import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:infinite_scroll_paging/infinite_scroll_paging.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/StockAudit/model/stock_audit_model.dart';
import 'package:mobile_pos/Screens/StockAudit/repo/stock_audit_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

class StockAuditListScreen extends StatefulWidget {
  const StockAuditListScreen({super.key});

  @override
  State<StockAuditListScreen> createState() => _StockAuditListScreenState();
}

class _StockAuditListScreenState extends State<StockAuditListScreen> {
  final StockAuditRepo _repo = StockAuditRepo();
  final PagingController<int, StockAuditModel> _pagingController =
      PagingController(firstPageKey: 1);
  final TextEditingController _searchController = TextEditingController();

  String? _selectedStatus;
  String? _selectedType;

  final List<String> _statusOptions = [
    'all',
    'pending',
    'in_progress',
    'completed',
    'cancelled',
  ];

  final List<String> _typeOptions = [
    'all',
    'full',
    'partial',
    'cycle',
    'spot_check',
  ];

  @override
  void initState() {
    super.initState();
    _pagingController.addPageRequestListener((pageKey) => _fetchAudits(pageKey));
  }

  Future<void> _fetchAudits(int pageKey) async {
    try {
      final result = await _repo.getStockAudits(
        status: _selectedStatus != null && _selectedStatus != 'all'
            ? _selectedStatus
            : null,
        auditType: _selectedType != null && _selectedType != 'all'
            ? _selectedType
            : null,
        search:
            _searchController.text.isNotEmpty ? _searchController.text : null,
        page: pageKey,
        perPage: 10,
      );

      if (result != null) {
        final newItems = result.audits ?? [];
        final meta = result.meta;
        final isLastPage =
            meta != null && (meta.currentPage ?? 1) >= (meta.lastPage ?? 1);

        if (isLastPage) {
          _pagingController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          _pagingController.appendPage(newItems, nextPageKey);
        }
      } else {
        _pagingController.appendLastPage([]);
      }
    } catch (error) {
      _pagingController.error = error;
    }
  }

  void _refresh() {
    _pagingController.refresh();
  }

  @override
  void dispose() {
    _pagingController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Color _getStatusColor(String? status) {
    switch (status) {
      case 'completed':
        return const Color(0xFF43A047);
      case 'in_progress':
        return const Color(0xFFFF6D00);
      case 'cancelled':
        return const Color(0xFFE53935);
      case 'pending':
      default:
        return const Color(0xFF1E88E5);
    }
  }

  IconData _getStatusIcon(String? status) {
    switch (status) {
      case 'completed':
        return Icons.check_circle_rounded;
      case 'in_progress':
        return Icons.pending_rounded;
      case 'cancelled':
        return Icons.cancel_rounded;
      case 'pending':
      default:
        return Icons.access_time_rounded;
    }
  }

  IconData _getTypeIcon(String? type) {
    switch (type) {
      case 'full':
        return Icons.inventory_2_rounded;
      case 'partial':
        return Icons.hourglass_full_rounded;
      case 'cycle':
        return Icons.sync_rounded;
      case 'spot_check':
        return Icons.search_rounded;
      default:
        return Icons.document_scanner_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final language = lang.S.of(context);

    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: kWhite),
        title: Text(
          'Stock Audit',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: kWhite),
            onPressed: _refresh,
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: TextFormField(
              controller: _searchController,
              onChanged: (value) => _refresh(),
              decoration: InputDecoration(
                contentPadding: const EdgeInsets.all(10),
                prefixIcon: const Icon(Icons.search, color: kNutral700),
                hintText: language.search,
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

          // Filter Chips
          _buildFilterChips(theme),

          const SizedBox(height: 4),

          // Audit List
          Expanded(
            child: RefreshIndicator.adaptive(
              onRefresh: () async => Future.sync(() => _refresh()),
              child: PagedListView<int, StockAuditModel>(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                pagingController: _pagingController,
                physics: const AlwaysScrollableScrollPhysics(),
                builderDelegate: PagedChildBuilderDelegate<StockAuditModel>(
                  newPageProgressIndicatorBuilder: (context) => const Padding(
                    padding: EdgeInsets.all(16),
                    child: CircularProgressIndicator(color: kMainColor),
                  ),
                  noItemsFoundIndicatorBuilder: (context) => Padding(
                    padding: const EdgeInsets.all(40),
                    child: EmptyListWidget(
                      title: 'No stock audits found',
                    ),
                  ),
                  itemBuilder: (context, item, index) => Dismissible(
                    key: Key(item.id.toString()),
                    direction: DismissDirection.endToStart,
                    background: Container(
                      alignment: Alignment.centerRight,
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      color: Colors.red,
                      child: const Icon(Icons.delete, color: Colors.white),
                    ),
                    onDismissed: (direction) {
                      // Optionally delete audit
                    },
                    child: _buildAuditCard(theme, item),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
      flotingActionButton: FloatingActionButton(
        onPressed: () async {
          await Navigator.pushNamed(context, '/create-stock-audit');
          _refresh();
        },
        backgroundColor: kMainColor,
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }

  Widget _buildFilterChips(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildChipRow(
          'Status',
          _statusOptions,
          _selectedStatus,
          (value) {
            setState(() {
              _selectedStatus = value == 'all' ? null : value;
            });
            _refresh();
          },
        ),
        const SizedBox(height: 8),
        _buildChipRow(
          'Type',
          _typeOptions,
          _selectedType,
          (value) {
            setState(() {
              _selectedType = value == 'all' ? null : value;
            });
            _refresh();
          },
        ),
      ],
    );
  }

  Widget _buildChipRow(
    String label,
    List<String> options,
    String? selected,
    Function(String?) onSelected,
  ) {
    return SizedBox(
      height: 40,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: options.length,
        itemBuilder: (context, index) {
          final option = options[index];
          final isSelected = selected == option ||
              (selected == null && option == 'all');

          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: FilterChip(
              label: Text(
                option.toUpperCase(),
                style: TextStyle(
                  fontSize: 12,
                  color: isSelected ? Colors.white : kNutral700,
                  fontWeight:
                      isSelected ? FontWeight.w600 : FontWeight.normal,
                ),
              ),
              selected: isSelected,
              onSelected: (s) => onSelected(option),
              selectedColor: kMainColor,
              checkmarkColor: Colors.white,
              backgroundColor: Colors.white,
              side: BorderSide(
                color: isSelected ? kMainColor : kOutlineColor,
              ),
              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
              padding: const EdgeInsets.symmetric(horizontal: 4),
            ),
          );
        },
      ),
    );
  }

  Widget _buildAuditCard(ThemeData theme, StockAuditModel audit) {
    final statusColor = _getStatusColor(audit.status);
    final statusIcon = _getStatusIcon(audit.status);
    final typeIcon = _getTypeIcon(audit.auditType);

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
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
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/stock-audit-detail',
            arguments: audit.id,
          );
          _refresh();
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header: Audit Number + Status
              Row(
                children: [
                  CircleAvatar(
                    radius: 16,
                    backgroundColor: statusColor.withValues(alpha: 0.15),
                    child: Icon(statusIcon, color: statusColor, size: 18),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      audit.auditNumber ?? 'N/A',
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      audit.status ?? '',
                      style: TextStyle(
                        fontSize: 10,
                        color: statusColor,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // Type + Date Row
              Row(
                children: [
                  Icon(typeIcon, size: 14, color: kNutral600),
                  const SizedBox(width: 4),
                  Text(
                    '${audit.auditType ?? 'N/A'}',
                    style: theme.textTheme.bodySmall
                        ?.copyWith(color: kNutral600, fontSize: 11),
                  ),
                  const SizedBox(width: 12),
                  const Icon(Icons.calendar_today_outlined,
                      size: 14, color: kNutral600),
                  const SizedBox(width: 4),
                  Text(
                    audit.auditDate != null
                        ? DateFormat.yMMMd().format(
                            DateTime.parse(audit.auditDate!),
                          )
                        : 'Not started',
                    style: theme.textTheme.bodySmall
                        ?.copyWith(color: kNutral600, fontSize: 11),
                  ),
                ],
              ),
              const SizedBox(height: 6),

              // Items Count + User
              Row(
                children: [
                  Icon(Icons.inventory_2_outlined,
                      size: 14, color: kNutral600),
                  const SizedBox(width: 4),
                  Text(
                    '${audit.detailsCount ?? 0} items',
                    style: theme.textTheme.bodySmall
                        ?.copyWith(color: kNutral600, fontSize: 11),
                  ),
                  const SizedBox(width: 12),
                  Icon(Icons.person_outline, size: 14, color: kNutral600),
                  const SizedBox(width: 4),
                  Text(
                    audit.user?.name ?? 'N/A',
                    style: theme.textTheme.bodySmall
                        ?.copyWith(color: kNutral600, fontSize: 11),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

extension on lang.S {
  String get stockAudit => 'Stock Audit';
}
