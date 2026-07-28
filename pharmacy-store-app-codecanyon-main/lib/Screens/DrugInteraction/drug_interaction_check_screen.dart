import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:mobile_pos/Screens/DrugInteraction/model/drug_interaction_model.dart';
import 'package:mobile_pos/Screens/DrugInteraction/repo/drug_interaction_repo.dart';
import 'package:mobile_pos/constant.dart';
import '../../generated/l10n.dart' as lang;
import '../Products/Model/datum_product_model.dart';
import '../Products/Repo/product_repo.dart';
import '../widget/empty_widgets.dart';

class DrugInteractionCheckScreen extends StatefulWidget {
  /// Optional: Pre-selected product IDs to check (e.g., from a sale cart)
  final List<int>? preselectedProductIds;

  const DrugInteractionCheckScreen({super.key, this.preselectedProductIds});

  @override
  State<DrugInteractionCheckScreen> createState() => _DrugInteractionCheckScreenState();
}

class _DrugInteractionCheckScreenState extends State<DrugInteractionCheckScreen> {
  final DrugInteractionRepo _interactionRepo = DrugInteractionRepo();
  final ProductRepo _productRepo = ProductRepo();
  final PagingController<int, Datum> _productPageController = PagingController(firstPageKey: 1);
  final TextEditingController _searchController = TextEditingController();

  final Set<int> _selectedProductIds = {};
  DrugInteractionCheckResponse? _checkResult;
  bool _isChecking = false;
  bool _hasChecked = false;

  @override
  void initState() {
    _productPageController.addPageRequestListener(_fetchProducts);

    // Pre-select products if provided
    if (widget.preselectedProductIds != null && widget.preselectedProductIds!.isNotEmpty) {
      _selectedProductIds.addAll(widget.preselectedProductIds!);
    }

    super.initState();
  }

  @override
  void dispose() {
    _productPageController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchProducts(int pageKey) async {
    try {
      final list = await _productRepo.getProductList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
      );
      if (list != null) {
        final newItems = list.data?.data ?? [];
        final isLastPage = list.data?.lastPage == list.data?.currentPage;

        if (isLastPage) {
          _productPageController.appendLastPage(newItems);
        } else {
          _productPageController.appendPage(newItems, pageKey + 1);
        }
      }
    } catch (error) {
      _productPageController.error = error;
    }
  }

  Future<void> _checkInteractions() async {
    if (_selectedProductIds.length < 2) {
      EasyLoading.showInfo('Please select at least 2 products to check interactions.');
      return;
    }

    setState(() {
      _isChecking = true;
      _hasChecked = false;
      _checkResult = null;
    });

    try {
      final result = await _interactionRepo.checkInteractions(
        productIds: _selectedProductIds.toList(),
      );
      setState(() {
        _checkResult = result;
        _isChecking = false;
        _hasChecked = true;
      });
    } catch (e) {
      setState(() {
        _isChecking = false;
      });
      EasyLoading.showError('Failed to check interactions: $e');
    }
  }

  void _toggleProductSelection(int productId) {
    setState(() {
      if (_selectedProductIds.contains(productId)) {
        _selectedProductIds.remove(productId);
      } else {
        _selectedProductIds.add(productId);
      }
      // Reset results when selection changes
      _checkResult = null;
      _hasChecked = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final language = lang.S.of(context);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: Text(
          'Drug Interaction Check',
          style: theme.textTheme.titleLarge?.copyWith(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
        actions: [
          // Clear selection
          if (_selectedProductIds.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.clear_all, color: Colors.white),
              onPressed: () {
                setState(() {
                  _selectedProductIds.clear();
                  _checkResult = null;
                  _hasChecked = false;
                });
              },
              tooltip: 'Clear selection',
            ),
        ],
      ),
      body: Column(
        children: [
          // Search & selected count
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, top: 16),
            child: Column(
              children: [
                SizedBox(
                  height: 48,
                  child: TextFormField(
                    controller: _searchController,
                    onChanged: (value) {
                      if (value.isEmpty) {
                        _productPageController.refresh();
                      } else {
                        _productPageController.refresh();
                      }
                    },
                    decoration: InputDecoration(
                      contentPadding: const EdgeInsets.all(10),
                      prefixIcon: const Icon(FeatherIcons.search, color: kNutral700),
                      hintText: '${language.searchH} products...',
                      enabledBorder: OutlineInputBorder(
                        borderSide: const BorderSide(color: kOutlineColor),
                        borderRadius: BorderRadius.circular(30),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderSide: const BorderSide(color: kMainColor),
                        borderRadius: BorderRadius.circular(30),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                // Selected count bar
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: kMainColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.checklist, size: 18, color: kMainColor),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '${_selectedProductIds.length} product(s) selected',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: kMainColor,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                      if (_selectedProductIds.length >= 2)
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: kMainColor,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                            minimumSize: Size.zero,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(20),
                            ),
                          ),
                          icon: _isChecking
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Icon(Icons.medical_services_outlined, size: 16),
                          label: Text(
                            _isChecking ? 'Checking...' : 'Check Interactions',
                            style: const TextStyle(fontSize: 12),
                          ),
                          onPressed: _isChecking ? null : _checkInteractions,
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          const Divider(color: kOutlineBorder, thickness: 1.0),

          // Results or product list
          Expanded(
            child: _hasChecked && _checkResult != null
                ? _buildResults(theme, language)
                : _buildProductList(theme, language),
          ),
        ],
      ),
    );
  }

  Widget _buildProductList(ThemeData theme, lang.S language) {
    return RefreshIndicator.adaptive(
      onRefresh: () async => _productPageController.refresh(),
      child: PagedListView(
        padding: EdgeInsets.zero,
        pagingController: _productPageController,
        physics: const AlwaysScrollableScrollPhysics(),
        builderDelegate: PagedChildBuilderDelegate<Datum>(
          newPageProgressIndicatorBuilder: (context) => const Center(
            child: CircularProgressIndicator(color: kMainColor),
          ),
          noItemsFoundIndicatorBuilder: (context) => Padding(
            padding: const EdgeInsets.all(20.0),
            child: Center(
              child: EmptyListWidget(title: 'No products found'),
            ),
          ),
          itemBuilder: (context, item, index) => InkWell(
            onTap: () => _toggleProductSelection(item.id ?? 0),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 6.0),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: _selectedProductIds.contains(item.id) ? kMainColor.withValues(alpha: 0.08) : Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: _selectedProductIds.contains(item.id) ? kMainColor : kOutlineColor.withValues(alpha: 0.3),
                    width: _selectedProductIds.contains(item.id) ? 1.5 : 1.0,
                  ),
                ),
                child: Row(
                  children: [
                    // Checkbox
                    Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: _selectedProductIds.contains(item.id) ? kMainColor : Colors.transparent,
                        border: Border.all(
                          color: _selectedProductIds.contains(item.id) ? kMainColor : kGreyTextColor,
                        ),
                      ),
                      child: _selectedProductIds.contains(item.id)
                          ? const Icon(Icons.check, size: 16, color: Colors.white)
                          : null,
                    ),
                    const SizedBox(width: 12),
                    // Product info
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.productName ?? 'N/A',
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          if (item.productCode != null)
                            Text(
                              'Code: ${item.productCode}',
                              style: theme.textTheme.bodySmall?.copyWith(color: kGreyTextColor),
                            ),
                        ],
                      ),
                    ),
                    // Stock
                    Text(
                      'Stock: ${item.stocksSumProductStock ?? '0'}',
                      style: theme.textTheme.bodySmall?.copyWith(color: kMainColor),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildResults(ThemeData theme, lang.S language) {
    final data = _checkResult!.data;
    final total = data?.totalInteractions ?? 0;
    final hasCritical = data?.hasCritical ?? false;

    if (total == 0) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.check_circle_outline, size: 80, color: Colors.green),
            const SizedBox(height: 16),
            Text(
              'No Drug Interactions Found',
              style: theme.textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w600,
                color: Colors.green,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'The selected products appear to be safe to use together.',
              style: theme.textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: kMainColor,
                foregroundColor: Colors.white,
              ),
              icon: const Icon(Icons.arrow_back),
              label: const Text('Back to selection'),
              onPressed: () {
                setState(() {
                  _hasChecked = false;
                  _checkResult = null;
                });
              },
            ),
          ],
        ),
      );
    }

    final grouped = data!.grouped;
    final contraindicated = grouped?.contraindicated ?? [];
    final severe = grouped?.severe ?? [];
    final moderate = grouped?.moderate ?? [];
    final minor = grouped?.minor ?? [];
    final allInteractions = data.interactions ?? [];

    return Column(
      children: [
        // Summary warning bar
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          color: hasCritical ? Colors.red.withValues(alpha: 0.1) : Colors.orange.withValues(alpha: 0.1),
          child: Row(
            children: [
              Icon(
                hasCritical ? Icons.warning_rounded : Icons.info_outline,
                color: hasCritical ? Colors.red : Colors.orange,
                size: 28,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Found $total Interaction(s)',
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                        color: hasCritical ? Colors.red : Colors.orange.shade800,
                      ),
                    ),
                    if (contraindicated.isNotEmpty)
                      Text(
                        '${contraindicated.length} contraindicated',
                        style: theme.textTheme.bodySmall?.copyWith(color: Colors.red),
                      ),
                    if (severe.isNotEmpty)
                      Text(
                        '${severe.length} severe',
                        style: theme.textTheme.bodySmall?.copyWith(color: Colors.orange),
                      ),
                  ],
                ),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: kMainColor,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  minimumSize: Size.zero,
                ),
                child: const Text('Back', style: TextStyle(fontSize: 12)),
                onPressed: () {
                  setState(() {
                    _hasChecked = false;
                    _checkResult = null;
                  });
                },
              ),
            ],
          ),
        ),

        // Interaction list
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Contraindicated
              if (contraindicated.isNotEmpty) ...[
                _buildSeverityHeader('Contraindicated', contraindicated.length, Colors.red),
                ...contraindicated.map((interaction) => _buildInteractionCard(interaction, theme)),
              ],

              // Severe
              if (severe.isNotEmpty) ...[
                _buildSeverityHeader('Severe', severe.length, const Color(0xFFEA580C)),
                ...severe.map((interaction) => _buildInteractionCard(interaction, theme)),
              ],

              // Moderate
              if (moderate.isNotEmpty) ...[
                _buildSeverityHeader('Moderate', moderate.length, const Color(0xFFEAB308)),
                ...moderate.map((interaction) => _buildInteractionCard(interaction, theme)),
              ],

              // Minor
              if (minor.isNotEmpty) ...[
                _buildSeverityHeader('Minor', minor.length, const Color(0xFF22C55E)),
                ...minor.map((interaction) => _buildInteractionCard(interaction, theme)),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildSeverityHeader(String label, int count, Color color) {
    return Padding(
      padding: const EdgeInsets.only(top: 12, bottom: 8),
      child: Row(
        children: [
          Container(
            width: 12,
            height: 12,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 8),
          Text(
            '$label ($count)',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInteractionCard(DrugInteractionResult interaction, ThemeData theme) {
    final severityColor = Color(
      int.parse(interaction.severityColor?.replaceFirst('#', '0xFF') ?? '0xFF6B7280'),
    );

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: severityColor.withValues(alpha: 0.3),
        ),
        boxShadow: [
          BoxShadow(
            color: severityColor.withValues(alpha: 0.08),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header: Drug pair + severity badge
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: severityColor.withValues(alpha: 0.08),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.medication, size: 16, color: kGreyTextColor),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              interaction.drugA?.productName ?? interaction.drugA?.matchedName ?? 'Unknown',
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          const Icon(Icons.medication, size: 16, color: kGreyTextColor),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              interaction.drugB?.productName ?? interaction.drugB?.matchedName ?? 'Unknown',
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: severityColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Text(
                    interaction.severityLabel ?? interaction.severity ?? 'N/A',
                    style: TextStyle(
                      color: severityColor,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Description
          Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  interaction.description ?? 'No description available.',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: kNutrals900,
                    height: 1.5,
                  ),
                ),
                if (interaction.mechanism != null && interaction.mechanism!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    'Mechanism: ${interaction.mechanism}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: kGreyTextColor,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
                if (interaction.recommendation != null && interaction.recommendation!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.blue.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.lightbulb_outline, size: 16, color: Colors.blue),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            interaction.recommendation!,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: Colors.blue.shade800,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
                if (interaction.source != null && interaction.source!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Source: ${interaction.source}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: kGreyTextColor,
                      fontSize: 11,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

