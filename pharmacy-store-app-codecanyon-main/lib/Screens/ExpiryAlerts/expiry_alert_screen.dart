import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/ExpiryAlerts/model/expiry_alert_model.dart';
import 'package:mobile_pos/Screens/ExpiryAlerts/repo/expiry_alert_repo.dart';
import 'package:mobile_pos/Screens/Products/product_details.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

class ExpiryAlertScreen extends ConsumerStatefulWidget {
  const ExpiryAlertScreen({super.key});

  @override
  ConsumerState<ExpiryAlertScreen> createState() => _ExpiryAlertScreenState();
}

class _ExpiryAlertScreenState extends ConsumerState<ExpiryAlertScreen> {
  final ExpiryAlertRepo _repo = ExpiryAlertRepo();
  final PagingController<int, ExpiryAlertItem> _pagingController = PagingController(firstPageKey: 1);
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  ExpiryAlertStats? _stats;
  String? _selectedThreshold;
  bool _isLoadingStats = true;

  final List<ThresholdOption> _thresholds = [
    const ThresholdOption('all', 'الكل (All)', null),
    const ThresholdOption('expired', 'منتهي (Expired)', Color(0xFFE53935)),
    const ThresholdOption('today', 'اليوم (Today)', Color(0xFFFF6D00)),
    const ThresholdOption('7', '7 أيام (7 days)', Color(0xFFF9A825)),
    const ThresholdOption('30', '30 يوماً (30 days)', Color(0xFF43A047)),
    const ThresholdOption('60', '60 يوماً (60 days)', Color(0xFF1E88E5)),
    const ThresholdOption('90', '90 يوماً (90 days)', Color(0xFF5E35B1)),
    const ThresholdOption('365', '365 يوماً (365 days)', Color(0xFF6D4C41)),
  ];

  @override
  void initState() {
    super.initState();
    _pagingController.addPageRequestListener((pageKey) => _fetchExpiryAlerts(pageKey));
    _loadStats();
  }

  Future<void> _loadStats() async {
    setState(() => _isLoadingStats = true);
    final stats = await _repo.getStats();
    if (mounted) {
      setState(() {
        _stats = stats;
        _isLoadingStats = false;
      });
    }
  }

  Future<void> _fetchExpiryAlerts(int pageKey) async {
    try {
      final result = await _repo.getExpiryAlerts(
        threshold: _selectedThreshold,
        search: _searchController.text.isNotEmpty ? _searchController.text : null,
        page: pageKey,
      );

      if (result != null) {
        final newItems = result.data ?? [];
        final isLastPage = result.lastPage == result.currentPage;

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

  @override
  void dispose() {
    _pagingController.dispose();
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Color _getSeverityColor(String? severity) {
    switch (severity) {
      case 'critical':
        return const Color(0xFFE53935);
      case 'high':
        return const Color(0xFFFF6D00);
      case 'medium':
        return const Color(0xFFF9A825);
      case 'low':
        return const Color(0xFF43A047);
      case 'info':
        return const Color(0xFF1E88E5);
      default:
        return kNutral600;
    }
  }

  IconData _getSeverityIcon(String? severity) {
    switch (severity) {
      case 'critical':
        return Icons.warning_rounded;
      case 'high':
        return Icons.error_outline_rounded;
      case 'medium':
        return Icons.schedule_rounded;
      case 'low':
        return Icons.info_outline_rounded;
      case 'info':
        return Icons.info_outline_rounded;
      default:
        return Icons.info_outline;
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
          'تنبيهات انتهاء الصلاحية',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          // Refresh button
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: kWhite),
            onPressed: () {
              _loadStats();
              _pagingController.refresh();
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Stats Cards
          _buildStatsSection(theme, language),

          // Threshold Filter Chips
          _buildThresholdChips(theme),

          // Search Bar
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: TextFormField(
              controller: _searchController,
              onChanged: (value) {
                _pagingController.refresh();
              },
              decoration: InputDecoration(
                contentPadding: const EdgeInsets.all(10),
                prefixIcon: const Icon(Icons.search, color: kNutral700),
                hintText: language.searchH,
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

          // Product List
          Expanded(
            child: RefreshIndicator.adaptive(
              onRefresh: () async => Future.sync(() => _pagingController.refresh()),
              child: PagedListView<int, ExpiryAlertItem>(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                pagingController: _pagingController,
                scrollController: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                builderDelegate: PagedChildBuilderDelegate<ExpiryAlertItem>(
                  newPageProgressIndicatorBuilder: (context) =>
                      const Padding(
                        padding: EdgeInsets.all(16),
                        child: CircularProgressIndicator(color: kMainColor),
                      ),
                  noItemsFoundIndicatorBuilder: (context) => Padding(
                    padding: const EdgeInsets.all(40),
                    child: EmptyListWidget(
                      title: 'لا توجد منتجات منتهية الصلاحية',
                    ),
                  ),
                  itemBuilder: (context, item, index) => _buildExpiryAlertCard(theme, item, language),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsSection(ThemeData theme, lang.S language) {
    if (_isLoadingStats) {
      return const Padding(
        padding: EdgeInsets.all(16),
        child: Center(child: CircularProgressIndicator(color: kMainColor)),
      );
    }

    final stats = _stats;
    if (stats == null) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(12),
      margin: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFE53935), Color(0xFFFF6D00)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.red.withValues(alpha: 0.3),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 24),
              const SizedBox(width: 8),
              Text(
                'نظرة عامة على انتهاء الصلاحية',
                style: theme.textTheme.titleSmall?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildStatItem(
                icon: Icons.dangerous_rounded,
                label: 'منتهي',
                value: '${stats.expired ?? 0}',
                color: Colors.white,
              ),
              _buildStatItem(
                icon: Icons.access_time_rounded,
                label: 'اليوم',
                value: '${stats.expiringToday ?? 0}',
                color: Colors.white70,
              ),
              _buildStatItem(
                icon: Icons.calendar_view_week_rounded,
                label: '7 أيام',
                value: '${stats.expiring7Days ?? 0}',
                color: Colors.white60,
              ),
              _buildStatItem(
                icon: Icons.calendar_month_rounded,
                label: '30 يوماً',
                value: '${stats.expiring30Days ?? 0}',
                color: Colors.white60,
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildStatItem(
                icon: Icons.calendar_month_rounded,
                label: '60 يوماً',
                value: '${stats.expiring60Days ?? 0}',
                color: Colors.white60,
              ),
              _buildStatItem(
                icon: Icons.calendar_month_rounded,
                label: '90 يوماً',
                value: '${stats.expiring90Days ?? 0}',
                color: Colors.white60,
              ),
              _buildStatItem(
                icon: Icons.calendar_month_rounded,
                label: '365 يوماً',
                value: '${stats.expiring365Days ?? 0}',
                color: Colors.white60,
              ),
              _buildStatItem(
                icon: Icons.inventory_rounded,
                label: 'المجموع',
                value: '${stats.total ?? 0}',
                color: Colors.white,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, color: color, size: 18),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            color: color,
            fontWeight: FontWeight.bold,
            fontSize: 16,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            color: color.withValues(alpha: 0.8),
            fontSize: 10,
          ),
        ),
      ],
    );
  }

  Widget _buildThresholdChips(ThemeData theme) {
    return SizedBox(
      height: 40,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: _thresholds.length,
        itemBuilder: (context, index) {
          final threshold = _thresholds[index];
          final isSelected = _selectedThreshold == threshold.value ||
              (_selectedThreshold == null && threshold.value == null);

          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: FilterChip(
              label: Text(
                threshold.label,
                style: TextStyle(
                  fontSize: 12,
                  color: isSelected ? Colors.white : threshold.color ?? kNutral700,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                ),
              ),
              selected: isSelected,
              onSelected: (selected) {
                setState(() {
                  _selectedThreshold = selected ? threshold.value : null;
                });
                _pagingController.refresh();
              },
              selectedColor: threshold.color ?? kMainColor,
              checkmarkColor: Colors.white,
              backgroundColor: Colors.white,
              side: BorderSide(
                color: isSelected ? threshold.color ?? kMainColor : kOutlineColor,
              ),
              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
              padding: const EdgeInsets.symmetric(horizontal: 4),
            ),
          );
        },
      ),
    );
  }

  Widget _buildExpiryAlertCard(
    ThemeData theme,
    ExpiryAlertItem item,
    lang.S language,
  ) {
    final severityColor = _getSeverityColor(item.severity);
    final severityIcon = _getSeverityIcon(item.severity);
    final product = item.product;

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: severityColor.withValues(alpha: 0.3),
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: severityColor.withValues(alpha: 0.08),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          if (product?.id != null) {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ProductDetails(
                  id: product!.id!,
                ),
              ),
            );
          }
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Severity Badge + Product Name Row
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: severityColor.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(severityIcon, size: 14, color: severityColor),
                        const SizedBox(width: 4),
                        Text(
                          item.severityLabel ?? '',
                          style: TextStyle(
                            fontSize: 10,
                            color: severityColor,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      product?.productName ?? 'N/A',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // Details Row
              Row(
                children: [
                  // Days remaining
                  if (item.daysRemaining != null)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: item.daysRemaining! < 0
                            ? const Color(0xFFE53935).withValues(alpha: 0.1)
                            : const Color(0xFF43A047).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        item.daysRemaining! < 0
                            ? 'منذ ${item.daysRemaining!.abs()} يوم'
                            : item.daysRemaining == 0
                                ? 'ينتهي اليوم'
                                : 'متبقي ${item.daysRemaining} يوم',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: item.daysRemaining! < 0
                              ? const Color(0xFFE53935)
                              : const Color(0xFF43A047),
                        ),
                      ),
                    ),
                  const SizedBox(width: 8),

                  // Product Code
                  if (product?.productCode != null)
                    Text(
                      'كود: ${product!.productCode}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: kNutral600,
                        fontSize: 11,
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 6),

              // Batch, Stock, Price Row
              Row(
                children: [
                  // Batch No
                  if (item.batchNo != null && item.batchNo!.isNotEmpty)
                    Expanded(
                      child: Row(
                        children: [
                          const Icon(Icons.inventory_2_outlined, size: 14, color: kNutral600),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              'Batch: ${item.batchNo}',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: kNutral600,
                                fontSize: 11,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ),

                  // Stock Qty
                  Expanded(
                    child: Row(
                      children: [
                        const Icon(Icons.shopping_cart_outlined, size: 14, color: kNutral600),
                        const SizedBox(width: 4),
                        Text(
                          'الكمية: ${item.productStock ?? 0}',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: kNutral600,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Expire Date
                  if (item.expireDate != null)
                    Expanded(
                      child: Row(
                        children: [
                          const Icon(Icons.calendar_today_outlined, size: 14, color: kNutral600),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              DateFormat.yMMMd().format(
                                DateTime.parse(item.expireDate!),
                              ),
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: kNutral600,
                                fontSize: 11,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
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

class ThresholdOption {
  final String? value;
  final String label;
  final Color? color;

  const ThresholdOption(this.value, this.label, this.color);
}

